<?php
namespace WPMapasCulturais;

use MapasSDK\MapasSDK;

class ApiWrapper{
    const ENTITY_ID_META_KEY = 'MAPAS:entity_id';

    /**
     * MapasSDK object
     *
     * @var \MapasSDK\MapasSDK
     */
    public $mapasApi;

    /**
     * WPDB Object
     *
     * @var \wpdb
     */
    public $wpdb;

    /**
     * Instancia do wrapper da api
     *
     * @var \WPMapasCulturais\ApiWrapper
     */
    protected static $_instance = null;

    /**
     * Descrição das entidades e seus metadados
     *
     * @var array
     */
    public $entityDescriptions = [];

    /**
     * Tipos das entidades
     *
     * @var array
     */
    public $entityTypes = [];

    /**
     * Cache
     *
     * @var \WPMapasCulturais\Cache
     */
    public $cache = null;


    /**
     * Retorna a instância da wrapper da api
     *
     * @return \WPMapasCulturais\ApiWrapper
     */
    static function instance(){
        if(is_null(self::$_instance)){
            $class = get_called_class();
            self::$_instance = new $class;
        }

        return self::$_instance;
    }

    /**
     * Inicializa o wrapper da API do Mapas Culturais
     */
    protected function __construct() {
        $url = $this->getOption('url');
        $private_key = $this->getOption('private_key');
        $public_key = $this->getOption('public_key');

        $this->cache = new Cache(__CLASS__);
        $this->mapasApi = new MapasSDK($url, $public_key, $private_key);

        global $wpdb;
        $this->wpdb = $wpdb;

        $this->_populateDescriptions();

    }

    /**
     * Alias para o get_option do wordpress acrescentando o prefixo MAPAS: ao nome da opção
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getOption($name, $default = null){
        return Plugin::getOption($name, $default);
    }

    /**
     * Transforma um objeto de data da api do mapas para o formato `Y-m-d H:i:s`
     *
     * @param object $date_object
     * @return string
     */
    function parseDateFromMapas($date_object){
        $date = new \DateTime($date_object->date . ' ' . $date_object->timezone);
        $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));

        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Prepara uma string de data para o Mapas Culturais
     *
     * @param string $date_string string de datetime no formato "Y-m-d H:i:s"
     * @return string
     */
    function parseDateToMapas($date_string){
        return $date_string;
    }

    /**
     * Popula o objeto entityDescriptions com as descrições obtidas pelo endpoint /api/{$class}/describe
     *
     * @return void
     */
    protected function _populateDescriptions(){
        $cache_id = 'MAPAS:entity_descriptions';

        if($this->cache->exists($cache_id)){
            $descriptions = $this->cache->get($cache_id);
        } else {
            $descriptions = [];
            foreach(PLugin::POST_TYPES as $class){
                $descriptions[$class] = $this->mapasApi->getEntityDescription($class);
            }
            $this->cache->add($cache_id, $descriptions, Cache::DAY );
        }

        $this->entityDescriptions = $descriptions;
    }


    /**
     * Retorna os ids dos posts e das entidades 'linkadas'
     *
     * @param string $class (event|agent|space)
     * @param bool $only_entity_ids retornar somente os ids das entidades, sem os ids dos posts? default: false
     * @return array
     */
    function getLinkedEntitiesIds($class, $only_entity_ids = false){
        // @TODO: utilizar cache e apagar sempre que uma nova entidade for criada ou importada
        if($only_entity_ids){
            $result = $this->wpdb->get_col("
                SELECT
                    meta_value AS entity_id
                FROM
                    {$this->wpdb->postmeta}
                WHERE
                    meta_key = 'MAPAS:entity_id' AND
                    post_id IN (
                        SELECT
                            ID
                        FROM
                            {$this->wpdb->posts}
                        WHERE
                            post_type = '{$class}' AND
                            post_status IN ('publish', 'draft')
                    )");
        } else {
            $result = $this->wpdb->get_results("
                SELECT
                    m.post_id,
                    m.meta_value AS entity_id,
                    p.post_modified as update_timestamp
                FROM
                    {$this->wpdb->postmeta} m,
                    {$this->wpdb->posts} p

                WHERE
                    p.ID = m.post_id AND
                    m.meta_key = 'MAPAS:entity_id' AND
                    m.post_id IN (
                        SELECT
                            ID
                        FROM
                            {$this->wpdb->posts}
                        WHERE
                            post_type = '{$class}' AND
                            post_status IN ('publish', 'draft')
                    )");
        }

        return $result;
    }

    /**
     * Retorna os IDs dos posts dados os ids das entidades
     *
     * @param string $class classe da entidade (event|agent|space)
     * @param array $entity_id
     * @return array
     */
    function getPostIdsByEntityIds($class, array $entity_ids){
        $ids = implode("','", $entity_ids);

        $cache_id = __METHOD__ . ":$class:$ids";

        if($this->cache->exists($cache_id)){
            return $this->cache->get($cache_id);
        }

        $_result = $this->wpdb->get_results("
            SELECT
                post_id, meta_value AS entity_id
            FROM
                {$this->wpdb->postmeta}
            WHERE
                meta_value IN ('$ids') AND
                meta_key = 'MAPAS:entity_id' AND
                post_id IN (
                    SELECT
                        ID
                    FROM
                        {$this->wpdb->posts}
                    WHERE
                        post_type = '{$class}' AND
                        post_status IN ('publish', 'draft')
                )");

        $result = [];

        foreach($_result as $r){
            $result[$r->entity_id] = $r->post_id;
        }

        if(count($entity_ids) && $result){
            $this->cache->add($cache_id,$result,Cache::DAY);
        }

        return $result;
    }

    /**
     * Retorna objeto contendo a classe e id da entidade dado o post_id
     * ex: {class: 'agent', entity_id: 332}
     *
     * @param int $post_id
     * @return object
     */
    function getEntityClassAndIdByPostId($post_id){
        $post_id = (int) $post_id;
        $cache_id = __METHOD__ . ':' . $post_id;
        if($this->cache->exists($cache_id)){
            $result = $this->cache->get($cache_id);
        } else {
            $result = (object) $this->wpdb->get_row("
                SELECT
                    p.post_type as class,
                    m.meta_value as entity_id
                FROM
                    wp_postmeta m,
                    wp_posts p
                WHERE
                    p.ID = m.post_id AND
                    m.meta_key = 'MAPAS:entity_id' AND
                    m.post_id = '$post_id'");

            if($result){
                $this->cache->add($cache_id, $result, Cache::DAY);
            }
        }
        return $result;
    }

    /**
     * Retorna o id da entidade
     *
     * @param string $class classe da entidade (event|agent|space)
     * @param int $entity_id
     * @return int
     */
    function getPostIdByEntityId($class, $entity_id){
        $post_id = $this->getPostIdsByEntityIds($class, [$entity_id]);

        return isset($post_id[$entity_id]) ? $post_id[$entity_id] : null;
    }

    /**
     * Retorna os campos básicos comuns entre todas as entidades (agent|space|event)
     *
     * @return array
     */
    public function getBaseEntityFields(){
        return [
            'id', 'type', 'name', 'status', 'shortDescription', 'longDescription',
            'createTimestamp', 'updateTimestamp'
        ];
    }

    /**
     * Retorna uma lista de campos extras das entidades
     *
     * @return array
     */
    public function getExtraEntityFields(){
        return ['terms', 'permissionTo.modify'];
    }

    protected $_updating = [];

    function updating($post_id){
        return isset($this->_updating[$post_id]);
    }

    /**
     * Importa entidades da classe informada trazendo os campos informados
     *
     * @param string $class (event|agent|space)
     * @param array $entity_fields
     * @return void
     */
    protected function importEntities($class, array $entity_fields){
        $params = [];
        $import_datetime = $this->parseDateToMapas($this->getOption("{$class}:import_timestamp"));

        if($import_datetime){
            $params['updateTimestamp'] = "OR(NULL(),GT({$import_datetime}))";
        }

        $entity_ids = [];
        $exclude_ids = [];
        foreach($this->getLinkedEntitiesIds($class) as $link){
            $entity_ids[$link->entity_id] = $link->post_id;

            if($link->update_timestamp >= $import_datetime){
                $exclude_ids[] = $link->entity_id;
            }
        }

        if($exclude_ids){
            $exclude_ids = implode(',', $exclude_ids);
            $params['id'] = "!IN({$exclude_ids})";
        }

        $params['@files'] = '(avatar,header,gallery):name,description,url';

        $_fields = array_merge(
            $this->getBaseEntityFields(),
            $this->getExtraEntityFields()
        );

        $fields = $entity_fields;
        foreach($_fields as $f){
            if(!in_array($f, $fields)){
                $fields[] = $f;
            }
        }

        $entities = $this->find($class, $params, $fields, false);

        $status = [
            '-10' => 'trash',
            '0' => 'draft',
            '1' => 'publish'
        ];

        $created = 0;
        $updated = 0;

        foreach($entities as $entity){
            if(is_null($entity->updateTimestamp) && $entity->createTimestamp < $import_datetime){
                continue;
            }

            $args = [
                'post_type' => $class,
                'post_title' => $entity->name,
                'post_excerpt' => $entity->shortDescription ?: '',
                'post_content' => $entity->longDescription ?: '',
                'post_status' => $status[$entity->status]
            ];

            if(isset($entity_ids[$entity->id])){
                $updated++;
                $args['ID'] = $entity_ids[$entity->id];

                $this->_updating[$args['ID']] = true;
            } else {
                $created++;
            }

            $post_id = wp_insert_post($args);

            if(is_int($post_id) && $post_id > 0){
                update_post_meta($post_id, 'MAPAS:entity_id', $entity->id);
                update_post_meta($post_id, 'MAPAS:permission_to_modify', $entity->permissionTo->modify);
                delete_post_meta($post_id, 'MAPAS:__new_post');
                delete_post_meta($post_id, 'MAPAS:__validation_errors');

                if(in_array($class, ['agent', 'space'])){
                    wp_set_post_terms($post_id, [$entity->type->name], $class . '_type');
                }

                foreach($entity->terms as $taxonomy => $terms){
                    if($taxonomy == 'tag'){
                        wp_set_post_tags($post_id, $terms);
                    } else {
                        wp_set_post_terms($post_id, $terms, $taxonomy);
                    }
                }

                foreach($entity_fields as $field){
                    update_post_meta($post_id, $field, $entity->$field);
                }
            }

            $this->importEntityImages($entity, $post_id);

            $this->cache->delete($class . ':parse:' . $post_id);
        }

        update_option("MAPAS:{$class}:import_timestamp", date('Y-m-d H:i:s'), false);

        return (object) ['created' => $created, 'updated' => $updated];
    }

    /**
     * Importa as imagens da entidade do mapas culturais
     *
     * @param object $entity
     * @param int $post_id
     * @return void
     */
    function importEntityImages($entity, $post_id){
        $attachments = get_posts( [
            'post_type' => 'attachment',
            'posts_per_page' => -1,
            'post_parent' => $post_id
        ]);

        if(isset($entity->{'@files:avatar'})){
            $f = $entity->{'@files:avatar'};
            $attachment_id = $this->insertAttachmentFromUrl($post_id, $f->url, $f->description);
            if($attachment_id){
                set_post_thumbnail($post_id, $attachment_id);
                update_post_meta($post_id, 'MAPAS:entity_avatar_attachment_id', $attachment_id);
            }
        }

        if(isset($entity->{'@files:header'})){
            $f = $entity->{'@files:header'};
            $attachment_id = $this->insertAttachmentFromUrl($post_id, $f->url, $f->description);
            if($attachment_id){
                add_post_meta($post_id, $entity->entityClass . '_header-image_thumbnail_id', $attachment_id);
                update_post_meta($post_id, 'MAPAS:entity_header_attachment_id', $attachment_id);
            }
        }

        if(isset($entity->{'@files:gallery'})){
            $fs = $entity->{'@files:gallery'};

            foreach($fs as $f){
                $attachment_id = $this->insertAttachmentFromUrl($post_id, $f->url, $f->description);
            }
        }
    }

    /**
     * Anexa o arquivo da url informada ao post de id informado
     *
     * @param [type] $post_id
     * @param [type] $url
     * @param [type] $description label do anexo
     * @return void
     */
    function insertAttachmentFromUrl($post_id, $url, $description) {
        if($attach_id = $this->wpdb->get_var("SELECT post_id FROM {$this->wpdb->postmeta} WHERE meta_key = 'MAPAS:original_file_url' AND meta_value = '$url'")){
            return $attach_id;
        }

        $file = wp_remote_get($url, array('timeout' => 120));
        $response = wp_remote_retrieve_response_code($file);
        $body = wp_remote_retrieve_body($file);


        if ($response != 200) {
            return false;
        }
        $upload = wp_upload_bits(basename($url), null, $body);
        if (!empty($upload['error'])) {
            return false;
        }
        $file_path = $upload['file'];
        $file_name = basename($file_path);
        $file_type = wp_check_filetype($file_name, null);
        $attachment_title = sanitize_file_name(pathinfo($file_name, PATHINFO_FILENAME));
        $wp_upload_dir = wp_upload_dir();
        $post_info = [
            'guid' => $wp_upload_dir['url'] . '/' . $file_name,
            'post_mime_type' => $file_type['type'],
            'post_title' => $attachment_title,
            'post_content' => '',
            'post_status' => 'inherit',
        ];

        // Create the attachment
        $attach_id = wp_insert_attachment($post_info, $file_path, $post_id);
        // Include image.php
        require_once ABSPATH . 'wp-admin/includes/image.php' ;

        // Define attachment metadata
        $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);

        $attach_data['image_meta']['caption'] = $description;
        $attach_data['image_meta']['title'] = $description;

        // Assign metadata to attachment
        wp_update_attachment_metadata($attach_id, $attach_data);

        add_post_meta($attach_id, 'MAPAS:original_file_url', $url);

        return $attach_id;
    }

    /**
     * Envia a entidade para o mapas culturais
     *
     * @param string $class
     * @param int $post_id
     * @param array $fields
     * @return void
     */
    function pushEntity($class, $post_id, array $fields){
        $post = get_post($post_id);
        $entity_id = get_post_meta($post_id, 'MAPAS:entity_id', true);
        $is_new = get_post_meta($post_id, 'MAPAS:__new_post', true);
        $type = $class == 'space' ? 10 : 1;

        $terms = [];
        foreach(['area', 'linguagem', 'post_tag'] as $taxonomy_slug){
            $_terms = wp_get_post_terms($post_id, $taxonomy_slug);
            if($taxonomy_slug == 'post_tag'){
                $taxonomy_slug = 'tag';
            }
            $terms[$taxonomy_slug] = array_map(function($e) { return $e->name; }, $_terms);
        }

        if(in_array($class, ['agent', 'space'])){
            $_types = wp_get_post_terms($post_id, $class . '_type');
            if(!empty($_types) && is_array($_types)){
                $types = $this->getEntityTypes($class);
                $type = array_search($_types[0]->name, $types);
            }
        }

        $data = [
            'name' => $post->post_title,
            'type' => $type,
            'shortDescription' => $post->post_excerpt,
            'longDescription' => $post->post_content,
            'terms' => $terms
        ];

        foreach($fields as $field){
            $def = $this->entityDescriptions[$class]->{$field};
            $val = get_post_meta($post_id, $field, true);
            if($def->type == 'boolean'){
                $val = (bool) $val;
            } else if ($def->type == 'point'){
                if(is_array($val)){
                    $val = [floatval($val['lng']), floatval($val['lat'])];
                } else {
                    continue;
                }
            }
            $data[$field] = $val;
        }

        try{
            if($is_new){
                $result = $this->mapasApi->createEntity($class, $data);
                delete_post_meta($post_id, 'MAPAS:__new_post');
                add_post_meta($post_id, 'MAPAS:entity_id', $result->id);
            } else {
                $result = $this->mapasApi->patchEntity($class, $entity_id, $data);

                $avatar_attachment_id = get_post_thumbnail_id($post_id);
                $header_attachment_id = get_post_meta($post_id, 'agent_header-image_thumbnail_id', true);

                if(get_post_meta($post_id, 'MAPAS:entity_avatar_attachment_id', true) != $avatar_attachment_id){
                    $filename = get_attached_file($avatar_attachment_id);
                    if (!empty($filename)) {
                        $this->mapasApi->uploadFile($class, $result->id, 'avatar', $filename);
                        add_post_meta($post_id, 'MAPAS:entity_avatar_attachment_id', $avatar_attachment_id);
                    }
                }

                if(get_post_meta($post_id, 'MAPAS:entity_header_attachment_id', true) != $header_attachment_id){
                    $filename = get_attached_file($header_attachment_id);
                    if (!empty($filename)) {
                        $this->mapasApi->uploadFile($class, $result->id, 'header', $filename);
                        add_post_meta($post_id, 'MAPAS:entity_header_attachment_id', $header_attachment_id);
                    }
                }
            }

            delete_post_meta($post_id, 'MAPAS:__validation_errors');

        } catch (\Exception $e){
            http_response_code(400);
            $_SESSION['MAPAS:error:' . $post_id] = $e;
            $messages = [];
            if (!empty($e->curl->response->data)) {
                foreach ($e->curl->response->data as $field => $msgs) {
                    $messages = array_merge($messages, $msgs);
                }
            }
            update_post_meta($post_id, 'MAPAS:__validation_errors', $messages);
            echo json_encode([ 'error' => true, 'message' => implode('; ', $messages) ]);
            die();
        }
    }

    /**
     * Importa novos agentes da instalação do mapas culturais como posts
     *
     * @return int número de agentes importados
     */
    function importAgents(){
        $fields = Plugin::instance()->getEntityFields('agent');
        return $this->importEntities('agent', $fields);
    }

    /**
     * Envia o agente para o mapas culturais dado o post_id
     *
     * @param int $post_id
     * @return void
     */
    function pushAgent($post_id){
        $fields = Plugin::instance()->getEntityFields('agent');
        $this->pushEntity('agent', $post_id, $fields);
    }

    /**
     * Importa novos espaços da instalação do mapas culturais como posts
     *
     * @return int número de espaços importados
     */
    function importSpaces(){
        $fields = Plugin::instance()->getEntityFields('space');
        return $this->importEntities('space', $fields);
    }

    /**
     * Envia o espaço para o mapas culturais dado o post_id
     *
     * @param int $post_id
     * @return void
     */
    function pushSpace($post_id){
        $fields = Plugin::instance()->getEntityFields('space');
        $this->pushEntity('space', $post_id, $fields);
    }

    /**
     * Importa novos eventos da instalação do mapas culturais como posts
     *
     * @return int número de eventos importados
     */
    function importEvents(){
        $fields = Plugin::instance()->getEntityFields('event');
        return $this->importEntities('event', $fields);
    }

    /**
     * Envia o evento para o mapas culturais dado o post_id
     *
     * @param int $post_id
     * @return void
     */
    function pushEvent($post_id){
        $fields = Plugin::instance()->getEntityFields('event');
        $this->pushEntity('event', $post_id, $fields);
    }

    /**
     * Retorna os termos da taxonomia informada
     *
     * @param string $taxonomy_slug
     * @return array
     */
    function getTaxonomyTerms($taxonomy_slug){
        $cache_id = __METHOD__ . ':' . $taxonomy_slug;
        if($this->cache->exists($cache_id)){
            return $this->cache->get($cache_id);
        }

        $terms = $this->mapasApi->getTaxonomyTerms($taxonomy_slug);

        $this->cache->add($cache_id, $terms, Cache::DAY);

        return $terms;

    }

    /**
     * Retorna os tipos da entidade da classe informada
     *
     * @param string $class classe da entidade (event|agent|space)
     * @return array
     */
    function getEntityTypes($class){
        $cache_id = __METHOD__ . ':' . $class;

        if($this->cache->exists($cache_id)){
            return $this->cache->get($cache_id);
        }

        $_types = $this->mapasApi->getEntityTypes($class);
        $types = [];
        foreach($_types as $type){
            $types[$type->id] = $type->name;
        }

        $this->cache->add($cache_id, $types, Cache::DAY);

        return $types;
    }

    protected function prepareInParam(array $array){
        $array = array_map(function($el){
            return str_replace(',', '\\,', $el);
        }, $array);
        $param = 'IN(' . implode(',', $array) . ')';
        return $param;
    }

    protected function addParam(array &$params, $key, $value){
        if(isset($params[$key])){
            $params[$key] = 'AND(' . $value . ',' . $params[$key] . ')';
        } else {
            $params[$key] = $value;
        }
    }


    /**
     * Prepara os parâmetros para a api de eventos adicionando os filtros configurados
     *
     * @param array $params
     * @return array
     */
    function prepareEventParams(array $params){
        $_import = $this->getOption('event:import', 'mine');

        if($_import == 'mine'){
            $params['user'] = 'EQ(@me)';
        } else if($_import == 'control'){
            $params['@permissions'] = '@control';
        } else if($_import == 'agents'){
            $agents = $this->getLinkedEntitiesIds('agent', true);
            $params['owner'] = 'IN(' . implode(',', $agents) . ')';
        }

        if($_terms = $this->getOption('event:languages')){
            $_terms = $this->prepareInParam($_terms);
            $this->addParam($params, 'term:linguagem', $_terms);
        }

        if($_age_ratings = $this->getOption('event:age_ratings')){
            $_age_ratings = $this->prepareInParam($_age_ratings);
            $this->addParam($params, 'classificacaoEtaria', $_age_ratings);
        }

        if($this->getOption('event:verified')){
            $params['@verified'] = 1;
        }

        return $params;
    }

    /**
     * Prepara os parâmetros para a api de agentes adicionando os filtros configurados
     *
     * @param array $params
     * @return array
     */
    function prepareAgentParams(array $params){

        // @TODO: implementar os filtros no admin
        $_import = $this->getOption('agent:import', 'mine');

        if($_import == 'mine'){
            $params['user'] = 'EQ(@me)';
        } else if($_import == 'control'){
            $params['@permissions'] = '@control';
        }

        if($_terms = $this->getOption('agent:area')){
            $_terms = $this->prepareInParam($_terms);
            $this->addParam($params, 'term:area', $_terms);
        }

        if($_types = $this->getOption('agent:types')){
            $_types = $this->prepareInParam($_types);
            $this->addParam($params, 'type', $_types);
        }

        if($this->getOption('agent:verified')){
            $params['@verified'] = 1;
        }

        return $params;
    }

    /**
     * Prepara os parâmetros para a api de espaços adicionando os filtros configurados
     *
     * @param array $params
     * @return array
     */
    function prepareSpaceParams(array $params){
        // @TODO: implementar os filtros no admin
        $_import = $this->getOption('space:import', 'mine');

        if($_import == 'mine'){
            $params['user'] = 'EQ(@me)';
        } else if($_import == 'control'){
            $params['@permissions'] = '@control';
        } else if($_import == 'agents'){
            $agents = $this->getLinkedEntitiesIds('agent', true);
            $params['owner'] = 'IN(' . implode(',', $agents) . ')';
        }

        if($_terms = $this->getOption('space:areas')){
            $_terms = $this->prepareInParam($_terms);
            $this->addParam($params, 'term:area', $_terms);
        }

        if($_types = $this->getOption('space:types')){
            $_types = $this->prepareInParam($_types);
            $this->addParam($params, 'type', $_types);
        }

        if($this->getOption('space:verified')){
            $params['@verified'] = 1;
        }

        return $params;
    }

    /**
     * Prepara os parâmetros para a api de ocurrências de eventos adicionando os filtros configurados
     *
     * @param array $params
     * @return array
     */
    function prepareEventOccurrenceParams(array $params){
        $event_params = [];
        $space_params = [];

        foreach($params as $key => $param){
            if(strpos($key, 'space:') === 0){
                $key = substr($key, 6);
                $space_params[$key] = $param;
            } else {
                $event_params[$key] = $param;
            }
        }

        $event_params = $this->prepareEventParams($event_params);
        $space_params = $this->prepareSpaceParams($space_params);

        $result = $event_params;

        foreach($space_params as $key => $param){
            $result['space:' . $key] = $param;
        }

        return $result;
    }

    function findOne($class, $entity_id){
        $fields = $fields = Plugin::instance()->getEntityFields($class, true, true, ['permissionTo.modify', 'longDescription']);
        $entity = $this->mapasApi->findEntity($class, $entity_id, $fields);

        $this->parseEntity($class, $entity);

        return $entity;
    }

    /**
     * Busca entidades da classe informada na api do mapas culturais
     * aos parâmetros informados serão adicionados os filtros configurados
     *
     * @param string $class classe da entidade (event|agent|space)
     * @param array $params
     * @return array
     */
    function find($class, array $params, array $fields = [], $use_post_values = true){

        switch($class){
            case 'event':
                $params = $this->prepareEventParams($params);
                return $this->findEntities($class, $params, $fields, $use_post_values);
                break;
            case 'agent':
                $params = $this->prepareAgentParams($params);
                return $this->findEntities($class, $params, $fields, $use_post_values);
                break;
            case 'space':
                $params = $this->prepareSpaceParams($params);
                return $this->findEntities($class, $params, $fields, $use_post_values);
                break;
            case 'eventOccurrence':
                $params = $this->prepareEventOccurrenceParams($params);
                return $this->findEventOccurrences($params, $use_post_values);
                break;
            default:
                throw new \Exception(__('Classe inválida: ') . $class);
                break;
        }
    }

    /**
     * Busca entidades da classe informada na api do mapas culturais
     *
     * @param string $class classe da entidade (event|agent|space)
     * @param array $params
     * @param array $fields
     * @return void
     */
    protected function findEntities($class, array $params, array $fields = [], $use_post_values = true){
        if(empty($fields)){
            $fields = Plugin::instance()->getEntityFields($class, true, true, ['permissionTo.modify', 'longDescription']);
        }
        $entities = $this->mapasApi->findEntities($class, $fields, $params);
        foreach($entities as &$entity){
            $this->parseEntity($class, $entity, $use_post_values);
        }
        return $entities;
    }

    /**
     * Busca as ocorrências de eventos
     *
     * @param array $params exemplo: ['from' => '2019-01-01', 'to]
     * @return void
     */
    protected function findEventOccurrences($params, $use_post_values = true){

        $from = isset($params['from']) && $params['from'] ? $params['from'] : date('Y-m-d');
        $to = isset($params['to']) && $params['to'] ? $params['to'] : date('Y-m-d', strtotime('+1 month', strtotime($from)));
        $groupBy = isset($params['groupBy']);

        $params['@attendanceUser'] = isset($params['token']) ? $params['token'] : '-1';

        unset($params['to'], $params['from'], $params['groupBy'], $params['token']);

        $space_fields = Plugin::instance()->getEntityFields('space', true, true, ['permissionTo.modify', 'longDescription']);
        $event_fields = Plugin::instance()->getEntityFields('event', true, true, ['permissionTo.modify', 'longDescription']);

        $params['space:@select'] = implode(',', $space_fields);
        $params['@select'] = implode(',', $event_fields);


        $result = $this->mapasApi->findEventOccurrences($from, $to, $params);
        foreach($result as &$event){
            $this->parseEntity('eventOccurrence', $event, $use_post_values);
            $this->parseEntity('space', $event->space, $use_post_values);
        }

        if($groupBy == 'event'){
            $_result = [];
            foreach($result as $event){

                if(!isset($_result[$event->id])){
                    $_event = clone $event;
                    $_event->occurrences = [];
                    unset($_event->occurrence, $_event->space);
                    $_result[$event->id] = $_event;
                }
                $event->occurrence->space = $event->space;
                $_result[$event->id]->occurrences[] = $event->occurrence;
            }

            return array_values($_result);
        } else {
            return $result;
        }

    }

    /**
     * Retorna o conteúdo do post após aplicar o filtro `the_content`
     *
     * @param int $post_id
     * @return string
     */
    function get_the_content($post_id){
        $post = get_post($post_id);
        $content = $post->post_content;
        $content = apply_filters('the_content', $content);
        $content = str_replace(']]>', ']]&gt;', $content);
        return $content;
    }

    /**
     * Parseia o a entidade da classe informada
     *
     * @param string $class classe da entidade (event|agent|space)
     * @param object &$entity
     * @return void
     */
    function parseEntity($class, &$entity, $use_post_values = true){
        $is_event_occurrence = false;

        $entity->entityClass = $class;

        if($class == 'eventOccurrence'){
            $class = 'event';
            $is_event_occurrence = true;
        }
        $entity_post_id = $this->getPostIdByEntityId($class, $entity->id);
        $entity->post_id = $entity_post_id;
        if($entity_post_id){
            if($use_post_values){
                $entity->name = get_the_title($entity_post_id);
                $entity->shortDescription = get_the_excerpt($entity_post_id);
                $entity->longDescription = $this->get_the_content($entity_post_id);
            }

            $cache_id = $class . ':parse:' . $entity_post_id;

            if($this->cache->exists($cache_id, false)){
                $entity_data = $this->cache->get($cache_id, false);
            } else {
                $entity_data = (object)[
                    'permalink' => get_permalink($entity_post_id),
                    'avatar' => [
                        'original' => get_the_post_thumbnail_url($entity_post_id),
                        'small' => get_the_post_thumbnail_url($entity_post_id, 'thumbnail'),
                        'medium' => get_the_post_thumbnail_url($entity_post_id, 'medium'),
                        'large' => get_the_post_thumbnail_url($entity_post_id, 'large'),
                    ]
                ];

                $this->cache->add($cache_id, $entity_data, false);
            }
            $entity->permalink = $entity_data->permalink;
            $entity->avatar = $entity_data->avatar;
        }

        if($is_event_occurrence){
            $rule = $entity->rule;
            unset($entity->rule);
            $ends_on = $entity->ends_on ?: $entity->starts_on;
            $entity->occurrence = (object) [
                'id' => $entity->occurrence_id,
                'description' => $rule->description,
                'price' => $rule->price,
                'duration' => $rule->duration,
                'starts' => $entity->starts_on . ' ' . $entity->starts_at,
                'starts_on' => $entity->starts_on,
                'starts_at' => $entity->starts_at,

                'ends' => $ends_on . ' ' . $entity->ends_at,
                'ends_on' => $ends_on,
                'ends_at' => $entity->ends_at,

                'attendance' => $entity->attendance,
                'reccurrence_string' => $entity->_reccurrence_string
            ];

            unset($entity->occurrence_id, $entity->event_id, $entity->starts_at,
                  $entity->starts_on, $entity->ends_at, $entity->ends_on,
                  $entity->attendance, $entity->event_attendance, $entity->_reccurrence_string);
        }

        if(isset($entity->createTimestamp)){
            $entity->createTimestamp = $this->parseDateFromMapas($entity->createTimestamp);
        }
        if(isset($entity->updateTimestamp)){
            $entity->updateTimestamp = $this->parseDateFromMapas($entity->updateTimestamp);
        }

        if(isset($entity->location) && is_object($entity->location)){
            $entity->location = ['lat' => $entity->location->latitude, 'lng' => $entity->location->longitude];
        }
    }
}