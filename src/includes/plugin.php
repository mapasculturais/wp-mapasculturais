<?php
namespace WPMapasCulturais;

class Plugin{
    
    /**
     * @var \WPMapasCulturais\ApiWrapper
     */
    public $api;

    /**
     * inicializa o plugin adicionando as ações e filtros
     *
     * @return void
     */
    function __construct() {
        // instancia o wrapper
        $this->api = ApiWrapper::instance();
        
        add_filter('query_vars', [$this, 'filter__query_vars'] );
        
        add_action('init', [$this, 'action__rewrite_rules']);
        
        add_action('template_redirect', [$this, 'action__template_redirects']);
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

        wp_die();
    }

    public function filter__query_vars( $qvars ) {
        $qvars[] = 'mcaction';
        return $qvars;
    }

    public function action__rewrite_rules() {
        add_rewrite_rule('mcapi/([^/]+)/?$', 'index.php?action=wp_mapasculturais_actions&mcaction=$matches[1]', 'top');
    }

    public function action__template_redirects(){
        global $wp_query;
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


}

global $wp_mapasculturais;

$wp_mapasculturais = new Plugin;