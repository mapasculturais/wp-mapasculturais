<?php
namespace WPMapasCulturais;

class Plugin{
    const POST_TYPES = ['agent', 'space', 'event'];

    /**
     * Instancia do plugin
     *
     * @var \WPMapasCulturais\Plugin
     */
    protected static $_instance = null;
    
    /**
     * @var \WPMapasCulturais\ApiWrapper
     */
    public $api;

    /**
     * Retorna a instância do plugin
     *
     * @return \WPMapasCulturais\Plugin
     */
    static function instance(){
        if(is_null(self::$_instance)){
            $class = get_called_class();
            self::$_instance = new $class;
        }

        return self::$_instance;
    }

    /**
     * inicializa o plugin adicionando as ações e filtros
     *
     * @return void
     */
    protected function __construct() {
        // instancia o wrapper
        try{
            $this->api = ApiWrapper::instance();
        } catch(\Exception $e){
            return;
        }

        add_action('wp', [$this, '_import_terms']);
        
        add_filter('query_vars', [$this, 'filter__query_vars'] );
        
        add_action('init', [$this, 'action__rewrite_rules']);
        
        add_action('template_redirect', [$this, 'action__template_redirects']);

        add_action('save_post', [$this, 'action__save_post'], 1000);

        add_action('load-post.php', [$this, 'action__edit_post']);

        add_action('wp_insert_post', [$this, 'action__wp_insert_post'], 10, 3);
    }

    function getEntityMetadataDescription($class){
        if(!isset($this->api->entityDescriptions[$class])){
            return [];
        }

        $class_description = $this->api->entityDescriptions[$class];

        $to_remove = [
            'id', 'name', 'shortDescription', 'longDescription', 'status', 'createTimestamp', 'updateTimestamp',
            'sentNotification', 'subsite', '_subsiteId', 'user', 'userId', '@file', '_children',
            'opportunityTabName', 'useOpportunityTab', 'singleUrl',

            'localizacao', // @TODO: tem que tirar esse campo do mapas, ele não é usado.

            'parent', 'location', '_type', // @TODO: implementar esses campos
        ];

        $result = [];

        foreach($class_description as $key => $description){
            // if(!isset($description->isEntityRelation)) die(var_dump($description));
            if( strpos($key, 'geo') === 0 || 
                strpos($key, '__') === 0 || 
                in_array($key, $to_remove) ||
                (isset($description->private) && $description->private)|| 
                ($description->isEntityRelation && !$description->isOwningSide)
                ){
                continue;
            }

            if(!isset($description->type)){
                if($description->isEntityRelation){
                    $description->type = 'entityRelation';
                }
            }

            $result[$key] = $description;
        }

        return $result;
    }

    function getEntityFields($class){
        return array_keys($this->getEntityMetadataDescription($class));
    }

    function output_error($data, $http_status_code = 400){
        $this->output($data, $http_status_code);
    }


    function output_success($data, $http_status_code = 200){
        $this->output($data, $http_status_code);
    }

    function output($data, $http_status_code){
        http_response_code($http_status_code);

        echo json_encode($data);

        die;
    }

    function admin_message($message, $type = 'success'){
        add_action( 'admin_notices',  function () use($message, $type) {
            ?>
            <div class="notice notice-<?php echo $type ?> is-dismissible">
                <p><?php echo $message; ?></p>
            </div>
            <?php
        } );
    }

    function _import_terms(){
        
        if(!get_option('MAPAS:terms_imported')){
            try{
                foreach(['linguagem', 'area'] as $taxonomy_slug){
                    $terms = $this->api->getTaxonomyTerms($taxonomy_slug);
                    foreach($terms as $term){
                        wp_insert_term($term, $taxonomy_slug);
                    }
                }
                add_option('MAPAS:terms_imported', true);
            } catch(\Exception $e){ }
        }

        if(!get_option('MAPAS:types_imported')){
            try{
                foreach(['agent', 'space'] as $class){
                    $taxonomy_slug = $class . '_type';
                    $terms = $this->api->getEntityTypes($class);
                    
                    foreach($terms as $term){
                        wp_insert_term($term, $taxonomy_slug);
                    }
                }
                add_option('MAPAS:types_imported', true);
            } catch(\Exception $e){ }
        }
    }

    public function filter__query_vars( $qvars ) {
        $qvars[] = 'mcaction';
        return $qvars;
    }

    public function action__rewrite_rules() {
        add_rewrite_rule('mcapi/([^/]+)/?$', 'index.php?action=wp_mapasculturais_actions&mcaction=$matches[1]', 'top');
        if(!get_option('MAPAS:permalinks_flushed')){
            flush_rewrite_rules();
            add_option('MAPAS:permalinks_flushed', 1);
        }
    }

    public function action__template_redirects(){
        $action = get_query_var('mcaction');
        if(!$action){
            return;
        }

        $result = [];

        $api = $this->api;
        switch($action){
            case 'import-new-entities':
                $agents = $api->importNewAgents();
                $spaces = $api->importNewSpaces();
                $events = $api->importNewEvents();
                $result['agents'] = $agents;
                $result['spaces'] = $spaces;
                $result['events'] = $events;
                $this->output_success($result);
                break;
        }
        

    }

    public function action__save_post($post_id){
        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        $post_type = get_post_type($post_id);

        if(!in_array($post_type, self::POST_TYPES)){
            return;
        }
        try{
            if(get_post_meta($post_id, 'MAPAS:permission_to_modify', true)){
                switch($post_type){
                    case 'agent':
                        $this->api->pushAgent($post_id);
                        break;
                    case 'space':
                        $this->api->pushSpace($post_id);
                        break;
                    case 'event':
                        $this->api->pushEvent($post_id);
                        break;
                        
                }

                delete_post_meta($post_id, 'MAPAS:__push_failed');
            }
        } catch (\Exception $e){
            $this->admin_message(__('Erro ao sincronizar com o Mapas Culturais', 'wp-mapas'), 'error');
            add_post_meta($post_id, 'MAPAS:__push_failed', 1, true);
        }
    }

    public function action__edit_post(){
        add_action('posts_selection', [$this, 'action__edit_post__post_selection']);
    }

    public function action__edit_post__post_selection(){
        $post_type = get_post_type();
        if(in_array($post_type, self::POST_TYPES)){
            
            $post_id = get_the_ID();
            $entity_id = get_post_meta($post_id, 'MAPAS:entity_id', true);
            if($entity_id){
                // $this->api->
            }
        }
    }

    function action__wp_insert_post($post_id, $post, $update){
        if($update){
            return;
        }

        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        $post_type = get_post_type($post_id);

        if(!in_array($post_type, self::POST_TYPES)){
            return;
        }

        add_post_meta($post_id, 'MAPAS:__new_post', 1);
        add_post_meta($post_id, 'MAPAS:permission_to_modify', 1);
        
    }

    function action__activate(){
        
    }

    function action__deactivate(){
        delete_option('MAPAS:permalinks_flushed');
    }
}

global $wp_mapasculturais;

$wp_mapasculturais = Plugin::instance();