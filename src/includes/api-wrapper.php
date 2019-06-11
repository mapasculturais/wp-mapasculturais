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
     * Undocumented variable
     *
     * @var array
     */
    protected static $_instance = null;

    public $agentDescription;
    public $spaceDescription;
    public $eventDescription;


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

        if($url && $private_key && $public_key){
            $this->mapasApi = new MapasSDK($url, $public_key, $private_key);
        }

        global $wpdb;
        $this->wpdb = $wpdb;

        // $this->_populateDescriptions();
    }

    public function getOption($name, $default = null){
        $option_name = 'MAPAS:' . $name;

        return get_option($option_name, $default);
    }

    protected function _populateDescriptions(){
        if(!($descriptions = get_transient('MAPAS:entity_descriptions'))){
            $descriptions = [
                'agent' => $this->mapasApi->getEntityDescription('agent'),
                'space' => $this->mapasApi->getEntityDescription('space'),
                'event' => $this->mapasApi->getEntityDescription('event'),
            ];

            set_transient('MAPAS:entity_descriptions', $descriptions, 10 * MINUTE_IN_SECONDS);
        }

        $this->agentDescription = $descriptions['agent']; 
        $this->spaceDescription = $descriptions['space']; 
        $this->eventDescription = $descriptions['event'];
    }

    /**
     * Undocumented function
     *
     * @param [type] $entity_class
     * @return void
     */
    function getLinkedEntitiesIds($entity_class){
        $cache_id = __METHOD__ . ':' . $entity_class;

        if(!isset($this->_cache[$cache_id])){
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
                            post_type = '{$entity_class}' AND 
                            post_status IN ('publish', 'draft')
                    )");

            $this->_cache[$cache_id] = $result;

        }

        return $this->_cache[$cache_id];
    }

    protected function importNewEntities($class, array $params){
        $import_datetime = get_option("MAPAS:{$class}_import_timestamp:");
        
        $this->mapasApi->findEntities('agent', '*', $params);

        add_option("MAPAS:{$class}_import_timestamp:", date('Y-m-d'),'', false);
    }

    function importNewAgents(){
        $params = [];
        $_import = $this->getOption('agent:import');
        
        if($_import == 'mine'){
            $params['user'] = 'EQ(@me)';
        } else if($_import == 'control'){
            $params['@permissions'] = '@control';
        }

        $this->importNewEntities('agent', $params);
    }

    function importNewSpaces(){
        return $this->spaceDescription;
    }

    function importNewEvents(){
        return $this->eventDescription;
    }
}

ApiWrapper::instance();