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

    public $entityDescriptions = [];

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

    protected function __construct() {
        $url = get_option('MAPAS:url');
        $private_key = get_option('MAPAS:private_key');
        $public_key = get_option('MAPAS:public_key');

        $this->cache = new Cache(__CLASS__);

        $this->mapasApi = new MapasSDK($url, $public_key, $private_key);

        global $wpdb;
        $this->wpdb = $wpdb;

        $this->_populateDescriptions();

    }

    public function getOption($name, $default = null){
        $option_name = 'MAPAS:' . $name;

        return get_option($option_name, $default);
    }

    function parseDateFromMapas($date_object){
        $date = new \DateTime($date_object->date . ' ' . $date_object->timezone);
        $date->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        
        return $date->format('Y-m-d H:i:s');
    }

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
     * @param [type] $class
     * @return array
     */
    function getLinkedEntitiesIds($class){
        // @TODO: utilizar cache e apagar sempre que uma nova entidade for criada ou importada

        $result = $this->wpdb->get_results("
            SELECT 
                post_id, meta_value AS entity_id
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
        return $result;
    }

    protected function importNewEntities($class, array $entity_fields, array $params){
        
        $import_datetime = get_option("MAPAS:{$class}:import_timestamp");

        if($import_datetime){
            $params['createTimestamp'] = "GTE({$import_datetime})";
        }

        if($entities_ids = $this->getLinkedEntitiesIds($class)){
            $ids = implode(',', array_map(function($obj) { return $obj->entity_id; }, $entities_ids));
            $params['id'] = "!IN($ids)";
        }
        
        $params['@files'] = '(avatar,header,gallery):name,description,url';

        $required_fields = [
            'id', 'type', 'name', 'status', 'shortDescription', 'longDescription', 
            'createTimestamp', 'updateTimestamp', 
            'terms', 'permissionTo.modify'
        ];

        $fields = $entity_fields;
        foreach($required_fields as $f){
            if(!in_array($f, $fields)){
                $fields[] = $f;
            }
        }

        
        $entities = $this->mapasApi->findEntities($class, $fields, $params);
        // var_dump($entities) ; die;
        $status = [
            '-10' => 'trash',
            '0' => 'draft',
            '1' => 'publish'
        ];

        foreach($entities as $entity){

            $post_id = wp_insert_post([
                'post_type' => $class,
                'post_title' => $entity->name,
                'post_excerpt' => $entity->shortDescription ?: '',
                'post_content' => $entity->longDescription ?: '',
                'post_status' => $status[$entity->status]
            ]);

            if(is_int($post_id) && $post_id > 0){
                add_post_meta($post_id, 'MAPAS:entity_id', $entity->id);
                add_post_meta($post_id, 'MAPAS:permission_to_modify', $entity->permissionTo->modify);
                delete_post_meta($post_id, 'MAPAS:__new_post');

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
                    add_post_meta($post_id, $field, $entity->$field);
                }
            }

            $this->importEntityImages($entity, $post_id);
        }

        add_option("MAPAS:{$class}:import_timestamp", date('Y-m-d H:i:s'),'', false);
        $this->importing = false;
    }

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
                add_post_meta($post_id, 'agent_header-image_thumbnail_id', $attachment_id);
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

    function pushEntity($class, $post_id, $fields){
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
            if(is_array($_types)){
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
                    $val = [$val['lng'],$val['lat']];
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
                    $this->mapasApi->uploadFile($class, $result->id, 'avatar', $filename);
                    add_post_meta($post_id, 'MAPAS:entity_avatar_attachment_id', $avatar_attachment_id);
                }

                if(get_post_meta($post_id, 'MAPAS:entity_header_attachment_id', true) != $header_attachment_id){
                    $filename = get_attached_file($header_attachment_id);
                    $this->mapasApi->uploadFile($class, $result->id, 'header', $filename);
                    add_post_meta($post_id, 'MAPAS:entity_header_attachment_id', $header_attachment_id);
                }

            }

        } catch (\Exception $e){
            $_SESSION['MAPAS:error:' . $post_id] = $e;
        }
    }

    function importNewAgents(){
        $params = [];
        $_import = $this->getOption('agent:import');
        
        if($_import == 'mine'){
            $params['user'] = 'EQ(@me)';
        } else if($_import == 'control'){
            $params['@permissions'] = '@control';
        }

        $fields = Plugin::instance()->getEntityFields('agent');

        $this->importNewEntities('agent', $fields, $params);
    }

    function pushAgent($post_id){
        $fields = Plugin::instance()->getEntityFields('agent');
        $this->pushEntity('agent', $post_id, $fields);
    }

    function importNewSpaces(){
        $params = [];
        $_import = $this->getOption('space:import');
        
        if($_import == 'mine'){
            $params['user'] = 'EQ(@me)';
        } else if($_import == 'control'){
            $params['@permissions'] = '@control';
        }

        $fields = Plugin::instance()->getEntityFields('space');

        $this->importNewEntities('space', $fields, $params);
    }

    function pushSpace($post_id){
        $fields = Plugin::instance()->getEntityFields('space');
        $this->pushEntity('space', $post_id, $fields);
    }

    function importNewEvents(){
        $params = [];
        $_import = $this->getOption('event:import');
        
        if($_import == 'mine'){
            $params['user'] = 'EQ(@me)';
        } else if($_import == 'control'){
            $params['@permissions'] = '@control';
        }

        $fields = Plugin::instance()->getEntityFields('event');

        $this->importNewEntities('event', $fields, $params);
    }

    function pushEvent($post_id){
        $fields = Plugin::instance()->getEntityFields('event');
        $this->pushEntity('event', $post_id, $fields);
    }

    function getTaxonomyTerms($taxonomy_slug){
        $cache_id = __METHOD__ . ':' . $taxonomy_slug;
        if($this->cache->exists($cache_id)){
            return $this->cache->get($cache_id);
        }

        $terms = $this->mapasApi->getTaxonomyTerms($taxonomy_slug);
        
        $this->cache->add($cache_id, $terms, Cache::DAY);

        return $terms;
        
    }

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
}