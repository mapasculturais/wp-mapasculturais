<?php
namespace WPMapasCulturais;

use Exception;

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

        add_action('wp', [$this, 'import_terms']);

        add_action('admin_init', [$this, 'action__admin_init']);

        add_action('init', [$this, 'action__rewrite_rules']);

        add_action('template_redirect', [$this, 'action__template_redirects']);

        add_action('save_post', [$this, 'action__save_post'], 1000);

        add_action('wp_insert_post', [$this, 'action__wp_insert_post'], 10, 3);

        add_filter('query_vars', [$this, 'filter__query_vars'] );

        add_filter('single_template', [$this, 'filter__single_template']);

        $interval = $this->getOption('import-entities-interval', 60);
        $this->cron('import-entities', $interval, [$this, 'cron_importEntities']);
    }

    function cron($name, $interval, $function){
        if(isset($_GET['skip-cron'])){
            return;
        }
        $name = 'cron:' . $name;
        $last_executed_time = $this->getOption($name);
        $current_time = time();
        if(!$last_executed_time || $current_time - $last_executed_time > $interval){
            $this->setOption($name, $current_time);
            $function();
        }
    }

    function cron_importEntities(){
        wp_remote_get(get_bloginfo('url') . '/mcapi/import-entities/?skip-cron&mapas-cron=1');
    }

    /**
     * Alias para o get_option do wordpress acrescentando o prefixo MAPAS: ao nome da opção
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    static function getOption($name, $default = null){
        $option_name = 'MAPAS:' . $name;

        return get_option($option_name, $default);
    }

    static function setOption($name, $value){
        $option_name = 'MAPAS:' . $name;

        update_option($option_name, $value);
    }

    /**
     * Retorna as opções de um metadado de seleção única ou seleção múltipla
     *
     * @param string $class (agent|space|event)
     * @param string $metadata_key
     * @return array|null
     */
    function getEntityMetadataOptions($class, $metadata_key){
        $metadata_description = $this->getEntityMetadataDescription($class, $metadata_key);
        if($metadata_description && isset($metadata_description->options)){
            return (array) $metadata_description->options;
        } else {
            return null;
        }
    }

    /**
     * Retorna a descrição dos metadados da entidade da classe informada.
     * Caso informado o metadata_key será retornado somente a descrição do metadado da chave informada.
     *
     * @param string $class (agent|space|event)
     * @param string $metadata_key
     * @return mixed
     */
    function getEntityMetadataDescription($class, $metadata_key = null){
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

        if($metadata_key){
            if(isset($result[$metadata_key])){
                return $result[$metadata_key];
            } else {
                return null;
            }
        } else {
            return $result;
        }
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

    /**
     * Imprime um json de resposta de erro
     *
     * @param mixed $data informações sobre o erro
     * @param integer $http_status_code status da resposta http. default: 400
     * @return void
     */
    function output_error($data, $http_status_code = 400){
        $this->output($data, $http_status_code);
    }

    /**
     * Imprime um json de resposta de sucesso
     *
     * @param mixed $data informações a serem retornadas no json
     * @param integer $http_status_code status da resposta http. default: 200
     * @return void
     */
    function output_success($data, $http_status_code = 200){
        $this->output($data, $http_status_code);
    }

    /**
     * Imprime um json de resposta com o status http infomado.
     *
     * @param mixed $data informações a serem retornadas no json
     * @param integer $http_status_code
     * @return void
     */
    function output($data, $http_status_code){
        http_response_code($http_status_code);

        echo json_encode($data);

        die;
    }

    /**
     * Importa os termos das taxonomias (area, linguagem e tipos de agente e espaço) do mapas para o wordpress
     *
     * @return void
     */
    function import_terms(){

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

    function importEntities(){
        $api = $this->api;
        $result = [];

        if($this->getOption('agent:auto_import') || !isset($_GET['cron'])){
            $agents = $api->importAgents();
            $result['agents'] = $agents;
        }

        if($this->getOption('space:auto_import') || !isset($_GET['cron'])){
            $spaces = $api->importSpaces();
            $result['spaces'] = $spaces;
        }

        if($this->getOption('space:auto_import') || !isset($_GET['cron'])){
            $events = $api->importEvents();
            $result['events'] = $events;
        }

        return $result;
    }

    /**
     * Define o arquivo de template das singles dos post types registrados pelo plugin
     *
     * @param string $single arquivo de template padrão
     * @return string arquivo de template
     */
    function filter__single_template($single){

        global $post;

        foreach(self::POST_TYPES as $post_type){
            if ( $post->post_type == $post_type ) {
                $single_template_filename =  WP_MAPAS__SINGLES_PATH . "{$post_type}.php";

                if ( file_exists( $single_template_filename ) ) {
                    $single = $single_template_filename;
                }
            }
        }

        return $single;
    }

    /**
     * Adiciona à query do wordpress variáveis que serão utilizadas pela api
     *
     * @param array $qvars
     * @return array
     */
    function filter__query_vars( array $qvars ) {
        $qvars[] = 'mcaction';
        $qvars[] = 'mcarg1';
        $qvars[] = 'mcarg2';
        return $qvars;
    }

    function action__admin_init() {
        global $pagenow;

        if ($pagenow === 'post.php' && isset($_GET['post'])) {
            $validationErrors = get_post_meta((int) $_GET['post'], 'MAPAS:__validation_errors', true);

            if (!empty($validationErrors)) {
                wp_enqueue_script('wp-mapasculturais-admin-notices', plugin_dir_url(__FILE__) . '../dist/admin-notices.js', ['wp-block-editor', 'wp-data']);

                wp_localize_script('wp-mapasculturais-admin-notices', 'MapasNotices', [
                    'text' => implode('. ', $validationErrors),
                ]);
            }
        }
    }

    /**
     * Adiciona as rotas da API
     *
     * @return void
     */
    function action__rewrite_rules() {
        add_rewrite_rule('mcapi/([^/]+)/?$', 'index.php?action=wp_mapasculturais_actions&mcaction=$matches[1]', 'top');
        add_rewrite_rule('mcapi/([^/]+)/([^/]+)/?$', 'index.php?action=wp_mapasculturais_actions&mcaction=$matches[1]&mcarg1=$matches[2]', 'top');
        add_rewrite_rule('mcapi/([^/]+)/([^/]+)/([^/]+)/?$', 'index.php?action=wp_mapasculturais_actions&mcaction=$matches[1]&mcarg1=$matches[2]&mcarg2=$matches[3]', 'top');
        if(!get_option('MAPAS:permalinks_flushed')){
            flush_rewrite_rules();
            add_option('MAPAS:permalinks_flushed', 1);
        }
    }

    /**
     * Recebe as rotas da API
     *
     * @return void
     */
    function action__template_redirects(){
        $action = get_query_var('mcaction');
        if(!$action){
            return;
        }

        $result = [];

        $api = $this->api;
        switch($action){
            case 'import-entities':
                if(isset($_GET['mapas-cron'])){
                    header("Connection: close");
                }
                $result = $this->importEntities();
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

            case 'eventRule':
                $api = $this->api->mapasApi;
                $action = get_query_var('mcarg1');
                switch($action){
                    case 'find':
                        $event_id = get_query_var('mcarg2');
                        if(!$event_id){
                            $this->output_error(__('id do evento não informado', 'wp-mapas'));
                        }
                        $result = $api->findEntities('eventOccurrence',['*'],['event' => "EQ({$event_id})"]);
                        $this->output_success($result);
                        break;

                    case 'findOne':
                        $occurrence_id = get_query_var('mcarg2');
                        if(!$occurrence_id){
                            $this->output_error(__('id da ocurrência não informado', 'wp-mapas'));
                        }
                        $result = $api->findEntity('eventOccurrence', $occurrence_id, ['*']);
                        $this->output_success($result);
                        break;

                    case 'create':
                        try{
                            $result = $api->createEntity('eventOccurrence', $_POST);
                            $this->output_success($result);
                        } catch(\MapasSDK\Exceptions\ValidationError $e) {
                            $this->output_error($e->curl->response->data);
                        }
                        break;

                    case 'update':
                        try{
                            $occurrence_id = get_query_var('mcarg2');
                            $result = $api->updateEntity('eventOccurrence', $occurrence_id, $_POST);
                            $this->output_success($result);
                        } catch(\MapasSDK\Exceptions\ValidationError $e) {
                            $this->output_error($e->curl->response->data);
                        }
                        break;

                    case 'delete':
                        $occurrence_id = get_query_var('mcarg2');
                        $response = $api->deleteEntity('eventOccurrence', $occurrence_id);
                        $this->output_success($response);
                        break;
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
                if ($_POST) {
                    $result = (object)[];
                } else {
                    $result = $this->api->find($action, $_GET);
                }
                $this->output_success($result);
                break;

            case 'eventAttendance':
                switch(get_query_var('mcarg1')){
                    case 'create':
                        if(!isset($_POST['token'])){
                            $this->output_error(__("O parâmetro 'token' é obrigatório"));
                        }
                        if(!isset($_POST['reccurrenceString'])){
                            $this->output_error(__("O parâmetro 'reccurrenceString' é obrigatório"));
                        }
                        if(!isset($_POST['type'])){
                            $this->output_error(__("O parâmetro 'type' é obrigatório"));
                        }
                        if(!in_array($_POST['type'], ['confirmation', 'interested'])){
                            $this->output_error(__("O valor do parâmetro 'type' deve ser 'confirmation' ou 'interested'"));
                        }
                        $data = [
                            'user' => $_POST['token'],
                            'type' => $_POST['type'],
                            'reccurrenceString' => $_POST['reccurrenceString']
                        ];
                        try{
                            $result = $this->api->mapasApi->createEntity('eventAttendance',$data);
                            $this->output_success($result);
                        } catch(\Exception $e){
                            $this->output_error($e->toString());
                        }
                        break;
                    case 'delete':
                        if(!isset($_POST['event_attendance_id'])){
                            $this->output_error(__("O parâmetro 'event_attendance_id' é obrigatório"));
                        }
                        $result = $this->api->mapasApi->deleteEntity('eventAttendance',$_POST['event_attendance_id']);
                        break;
                }

                $this->output_success($_POST);
                break;

        }
    }

    /**
     * Envia as entidades para o Mapas Culturais
     *
     * @param integer $post_id
     * @return void
     */
    function action__save_post($post_id){
        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }

        $post_type = get_post_type($post_id);

        if(!in_array($post_type, self::POST_TYPES)){
            return;
        }
        try{
            if(!$this->api->updating($post_id) && get_post_meta($post_id, 'MAPAS:permission_to_modify', true)){
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
            add_post_meta($post_id, 'MAPAS:__push_failed', 1, true);
        }
    }

    /**
     * Prepara um novo post para ser enviado ao mapas culturais
     *
     * @param int $post_id
     * @param object $post
     * @param bool $update
     * @return void
     */
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

    /**
     * Executado na ativação do plugin
     *
     * @return void
     */
    function action__activate(){

    }

    /**
     * Executado na desativação do plugin
     *
     * @return void
     */
    function action__deactivate(){
        delete_option('MAPAS:permalinks_flushed');
    }
}

global $wp_mapasculturais;

$wp_mapasculturais = Plugin::instance();