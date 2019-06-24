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

    protected $_cache = []; 

    /**
     * Instancia do wrapper da api
     *
     * @var \WPMapasCulturais\ApiWrapper
     */
    protected static $_instance = null;

    public $entityDescriptions = [];

    public $entityTypes = [];


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

        $this->mapasApi = new MapasSDK($url, $public_key, $private_key);

        global $wpdb;
        $this->wpdb = $wpdb;

        $this->_populateDescriptions();
        $this->_populateTypes();
    }

    public function getOption($name, $default = null){
        $option_name = 'MAPAS:' . $name;

        return get_option($option_name, $default);
    }

    function parseDateFromMapas($date_object){
        $date = new \DateTime($date_object->date . ' ' . $date_object->timezone);
        $date->setTimezone(date_default_timezone_get());

        return $date->format('Y-m-d H:i:s');
    }

    protected function _populateDescriptions(){
        $cache_id = 'MAPAS:entity_descriptions';
        if(!($descriptions = get_transient($cache_id))){
            $descriptions = [];
            foreach(PLugin::POST_TYPES as $class){
                $descriptions[$class] = $this->mapasApi->getEntityDescription($class);
            }

            set_transient($cache_id, $descriptions, 10 * MINUTE_IN_SECONDS);
        }

        $this->entityDescriptions = $descriptions;
    }

    protected function _populateTypes(){
        $cache_id = 'MAPAS:entity_types';
        if(!($types = get_transient($cache_id))){
            $types = [];

            foreach(PLugin::POST_TYPES as $class){
                $types[$class] = $this->mapasApi->getEntityTypes($class);
            }
        }
        
        $this->entityTypes = $types;
    }


    /**
     * Undocumented function
     *
     * @param [type] $class
     * @return void
     */
    function getLinkedEntitiesIds($class, $regenerate_cache = false){
        $cache_id = "MAPAS:$class:entity_ids";

        if($regenerate_cache || !($result = get_transient($cache_id))){
            $meta_key = self::ENTITY_ID_META_KEY;
            $result = $this->wpdb->get_results("
                SELECT 
                    post_id, meta_value 
                FROM 
                    {$this->wpdb->postmeta} 
                WHERE 
                    meta_key = '{$meta_key}' AND 
                    post_id IN (
                        SELECT 
                            ID 
                        FROM 
                            {$this->wpdb->posts} 
                        WHERE
                            post_type = '{$class}' AND 
                            post_status IN ('publish', 'draft')
                    )");

            set_transient($cache_id, $result, 10 * MINUTE_IN_SECONDS);
        }

        return $this->_cache[$cache_id];
    }

    protected function importNewEntities($class, array $fields, array $params){
        $import_datetime = get_option("MAPAS:{$class}:import_timestamp");

        if($import_datetime){
            $params['createTimestamp'] = "GTE({$import_datetime})";
        }

        $required_fields = [
            'id', 'type', 'name', 'shortDescription', 'longDescription', 
            'createTimestamp', 'updateTimestamp', 'status', 'permissionTo.modify'
        ];

        foreach($required_fields as $f){
            if(!in_array($f, $fields)){
                $fields[] = $f;
            }
        }
        
        $entities = $this->mapasApi->findEntities($class, $fields, $params);
        
        $status = [
            '-10' => 'trash',
            '0' => 'draft',
            '1' => 'publish'
        ];
        // die(var_dump($entities));
        foreach($entities as $entity){
            $post_id = wp_insert_post([
                'post_type' => $class,
                'post_title' => $entity->name,
                'post_excerpt' => $entity->shortDescription ?: '',
                'post_content' => $entity->longDescription ?: '',
                'post_date' => $this->parseDateFromMapas($entity->createTimestamp),
                'post_modified' => $this->parseDateFromMapas($entity->updateTimestamp ? 
                                            $entity->updateTimestamp : $entity->createTimestamp),
                'post_status' => $status[$entity->status]
            ]);

            if(is_int($post_id) && $post_id > 0){
                add_post_meta($post_id, 'MAPAS:entity_id', $entity->id);
                add_post_meta($post_id, 'MAPAS:permission_to_modify', $entity->permissionTo->modify);
                delete_post_meta($post_id, 'MAPAS:__new_post');
            }
        }

        add_option("MAPAS:{$class}:import_timestamp", date('Y-m-d H:i:s'),'', false);
    }

    function pushEntity($class, $post_id, $fields){
        $post = get_post($post_id);
        $entity_id = get_post_meta($post_id, 'MAPAS:entity_id', true);
        $is_new = get_post_meta($post_id, 'MAPAS:__new_post', true);
        
        $terms = [];
        foreach(['area', 'linguagem', 'post_tag'] as $taxonomy_slug){           
            $_terms = wp_get_post_terms($post_id, $taxonomy_slug);
            if($taxonomy_slug == 'post_tag'){
                $taxonomy_slug = 'tag';
            }
            $terms[$taxonomy_slug] = array_map(function($e) { return $e->name; }, $_terms);
        }
        
        $data = [
            'name' => $post->post_title,
            'type' => $class == 'agent' ? 1 : 10, // @TODO: implementar tipo
            'shortDescription' => $post->post_excerpt,
            'longDescription' => $post->post_content,
            'terms' => $terms
        ];

        // die(Var_dump($fields));

        foreach($fields as $field){
            $data[$field] = get_post_meta($post_id, $field, true);
        }
        try{
            if($is_new){
                $result = $this->mapasApi->createEntity($class, $data);
                delete_post_meta($post_id, 'MAPAS:__new_post');
                add_post_meta($post_id, 'MAPAS:entity_id', $result->id);
            } else {
                $result = $this->mapasApi->patchEntity($class, $entity_id, $data);
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

        $this->importNewEntities('agent', $fields, $params);
    }

    function pushSpace($post_id){
        $fields = Plugin::instance()->getEntityFields('space');
        $this->pushEntity('space', $post_id, $fields);
    }

    function importNewEvents(){

    }

    function getTaxonomyTerms($taxonomy_slug){
        $cache_id = __METHOD__ . ':' . $taxonomy_slug;
        if(!isset($this->_cache[$cache_id])){
            $terms = $this->mapasApi->getTaxonomyTerms($taxonomy_slug);
            $this->_cache[$cache_id] = $terms;
        }

        return $this->_cache[$cache_id];
        
    }
}