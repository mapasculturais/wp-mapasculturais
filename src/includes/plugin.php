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
        
        add_action('init', [$this, 'action__rewrite_rules']);
        
        add_action('template_redirect', [$this, 'action__template_redirects']);
        
        add_action('save_post', [$this, 'action__save_post'], 1000);
        
        add_action('load-post.php', [$this, 'action__edit_post']);
        
        add_action('wp_insert_post', [$this, 'action__wp_insert_post'], 10, 3);
        
        add_filter('query_vars', [$this, 'filter__query_vars'] );

        add_filter('single_template', [$this, 'filter__single_template']);
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

            'rules', 'price', 'project', // verificar
            'localizacao', 'googleplus', // @TODO: tem que tirar esse campo do mapas, ele não é usado.

            'owner', 'parent', '_type', // @TODO: implementar esses campos
        ];

        $result = [];


        foreach($class_description as $key => $description){
            // if(!isset($description->isEntityRelation)) die(var_dump($description));
            if( strpos($key, 'geo') === 0 || 
                strpos($key, '__') === 0 || 
                in_array($key, $to_remove) ||
                (isset($description->private) && is_bool($description->private) && $description->private)|| 
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

    /**
     * Retorna um array com os campos da entidade da classe informada
     *
     * @param string $class (agent|space|event)
     * @param boolean $include_base_fields incluir os campos básicos da entidade? default: false
     * @param boolean $include_extra_fields includer os campos extras da entidade? default: false
     * @return array
     */
    function getEntityFields($class, $include_base_fields = false, $include_extra_fields = false, $exclude_fields = []){
        $result = array_keys($this->getEntityMetadataDescription($class));
        if($include_base_fields){
            $result = array_merge($this->api->getBaseEntityFields(), $result);
        }

        if($include_extra_fields){
            $result = array_merge($result, $this->api->getExtraEntityFields());
        }

        return array_diff($result, $exclude_fields);
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

    public function filter__single_template($single){

        global $post;

        foreach(self::POST_TYPES as $post_type){
            if ( $post->post_type == $post_type ) {
                $single_template_filename =  WP_MAPAS__SINGLES_PATH . "{$post_type}.php";
                
                if ( file_exists( $single_template_filename ) ) {
                    return $single_template_filename;
                }
            }
        }

        return $single;
    }

    public function filter__query_vars( $qvars ) {
        $qvars[] = 'mcaction';
        $qvars[] = 'mcarg1';
        $qvars[] = 'mcarg2';
        return $qvars;
    }

    public function action__rewrite_rules() {
        add_rewrite_rule('mcapi/([^/]+)/?$', 'index.php?action=wp_mapasculturais_actions&mcaction=$matches[1]', 'top');
        add_rewrite_rule('mcapi/([^/]+)/([^/]+)/?$', 'index.php?action=wp_mapasculturais_actions&mcaction=$matches[1]&mcarg1=$matches[2]', 'top');
        add_rewrite_rule('mcapi/([^/]+)/([^/]+)/([^/]+)/?$', 'index.php?action=wp_mapasculturais_actions&mcaction=$matches[1]&mcarg1=$matches[2]&mcarg2=$matches[3]', 'top');
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
            case 'entity':
                $post_id = get_query_var('mcarg1');
                $entity = $this->api->getEntityClassAndIdByPostId($post_id);
                if(isset($entity->class) && isset($entity->entity_id)){
                    $result = $this->api->findOne($entity->class, $entity->entity_id);
                    $this->output_success($result);
                } else {
                    $this->output_error(__('Entidade não encontrada', 'wp-mapas'), 404);
                }
                break;
            
            case 'agent':
            case 'space':
            case 'event':
                $arg1 = get_query_var('mcarg1');
                $result = [];
                if(empty($arg1)){
                    $result = $this->api->find($action, $_GET);
                } else if(is_numeric($arg1)){
                    $result = $this->api->findOne($action, $arg1);
                    if(!$result){
                        $this->output_error(__('Entidade não encontrada', 'wp-mapas'), 404);
                    }
                }
                $this->output_success($result);

            case 'eventOccurrence':
                $result = $this->api->findEventOccurrences($_GET);
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