<?php
namespace WPMapasCulturais;

require_once WP_MAPAS__VENDOR_PATH . 'advanced_taxonomy_metabox/class.taxonomy-single-term.php';
require_once WP_MAPAS__VENDOR_PATH . 'CMB2/init.php';
require_once WP_MAPAS__VENDOR_PATH . 'CMB2-field-Leaflet-Geocoder/cmb-field-leaflet-map.php';
require_once WP_MAPAS__VENDOR_PATH . 'multi-post-thumbnails/multi-post-thumbnails.php';


$space_type = new \Advanced_Taxonomy_Metabox( 'space_type', array( 'space' ), 'radio' );
$space_type->set( 'priority', 'core' );

$agent_type = new \Advanced_Taxonomy_Metabox( 'agent_type', array( 'agent' ), 'radio' );
$agent_type->set( 'priority', 'core' );

$areas = new \Advanced_Taxonomy_Metabox( 'area', array( 'agent', 'space' ), 'checkbox' );
$areas->set( 'priority', 'core' );

$languages = new \Advanced_Taxonomy_Metabox( 'linguagem', array( 'event' ), 'checkbox' );
$languages->set( 'priority', 'core' );

add_action('admin_menu', function(){
    add_submenu_page('wp-mapasculturais', 'Configurações', 'Configurações', 'manage_options', 'wp-mapasculturais', 'WPMapasCulturais\\config_page');
    add_menu_page('Mapas Culturais', 'Mapas Culturais', 'manage_options', 'wp-mapasculturais', 'WPMapasCulturais\\config_page', 'dashicons-location-alt');
});

function config_page(){
    include __DIR__ . '/pages/mapasculturais-config.php';
}

add_action( 'admin_init', 'WPMapasCulturais\\register_settings' );
function register_settings(){
    register_setting( 'mapasculturais', 'MAPAS:url' );
    register_setting( 'mapasculturais', 'MAPAS:private_key' );
    register_setting( 'mapasculturais', 'MAPAS:public_key' );

    register_setting( 'mapasculturais', 'MAPAS:import-entities-interval' );
    
    register_setting( 'mapasculturais', 'MAPAS:agent:import' );
    register_setting( 'mapasculturais', 'MAPAS:space:import' );
    register_setting( 'mapasculturais', 'MAPAS:event:import' );


    register_setting( 'mapasculturais', 'MAPAS:agent:areas' );
    register_setting( 'mapasculturais', 'MAPAS:space:areas' );
    register_setting( 'mapasculturais', 'MAPAS:event:languages' );

    register_setting( 'mapasculturais', 'MAPAS:agent:types' );
    register_setting( 'mapasculturais', 'MAPAS:space:types' );

    register_setting( 'mapasculturais', 'MAPAS:event:age_ratings' );

    register_setting( 'mapasculturais', 'MAPAS:agent:verified' );
    register_setting( 'mapasculturais', 'MAPAS:space:verified' );
    register_setting( 'mapasculturais', 'MAPAS:event:verified' );
}

add_action('admin_enqueue_scripts', 'WPMapasCulturais\\admin_scripts');
function admin_scripts(){
    wp_enqueue_script('wp-mapasculturais-admin', plugin_dir_url(__FILE__) . '/assets/admin.js');
    wp_enqueue_style('wp-mapasculturais-admin', plugin_dir_url(__FILE__) . '/assets/admin.css');
    
    if(isset($_GET['post']) && isset($_SESSION['MAPAS:error:' . $_GET['post']])){
        $e = $_SESSION['MAPAS:error:' . $_GET['post']];
        $response = $e->curl->response;
        
        wp_localize_script('wp-mapasculturais-admin', 'mc_errors', (array) $response->data);
        
        unset($_SESSION['MAPAS:error:' . $_GET['post']]);
        
    }
}

add_action( 'cmb2_admin_init', 'WPMapasCulturais\\register_metaboxes' );
function register_metaboxes(){
    global $wp_mapasculturais;
    if(false) $wp_mapasculturais = new Plugin;


    foreach(Plugin::POST_TYPES as $post_type){
        $metadata_description = $wp_mapasculturais->getEntityMetadataDescription($post_type);
        // die(var_dump($post_type, $metadata_description,isset($metadata_description->location)));
        if(isset($metadata_description['location'])){
            $metaboxes = [
                'data' => (object) [
                        'name' => __( 'Dados', 'wp-mapas' ),
                        'fn'=> function ($meta_key) { return (!in_array($meta_key, ['location', 'publicLocation', 'endereco'])) && strpos($meta_key, 'En_') === false; }
                    ], 
                'location' => (object) [
                        'name' => __( 'Localização', 'wp-mapas' ),
                        'fn' => function ($meta_key) { return in_array($meta_key, ['location', 'publicLocation', 'endereco']) || strpos($meta_key, 'En_') !== false; }
                    ]
            ];
        } else {
            $metaboxes = [ 
                'data' => (object) [
                        'name' => __( 'Dados', 'wp-mapas' ),
                        'fn' => function ($meta_key) { return true; }
                    ] 
            ];
        }

        foreach($metaboxes as $metabox_id => $cfg){
            
            /**
             * Initiate the metabox
             */
            $cmb = new_cmb2_box( array(
                'id'            => "{$post_type}_{$metabox_id}",
                'title'         => $cfg->name,
                'object_types'  => array( $post_type ), // Post type
                'context'       => 'normal',
                'priority'      => 'low',
                'show_names'    => true, // Show field names on the left
                // 'cmb_styles' => false, // false to disable the CMB stylesheet
                // 'closed'     => true, // Keep the metabox closed by default
            ) );

            $fn = $cfg->fn;
            foreach($metadata_description as $key => $description){

                if(!$fn($key)){
                    continue;
                }

                if($key === 'public' && empty($description->label)){
                    $description->label = __('Publicação Livre', 'wp-mapas');
                }

                switch($description->type){
                    case 'text':
                    case 'string':
                        $cmb->add_field( array(
                            'name'       => $description->label,
                            'id'         => $key,
                            'type'       => 'text'
                        ) );
                        break;

                    case 'select':
                        $options = [];
                        foreach($description->optionsOrder as $value){
                            $options[$value] = $description->options->{$value}; 
                        }
                        $cmb->add_field( array(
                            'name'       => $description->label,
                            'id'         => $key,
                            'show_option_none' => true,
                            'type'       => 'select',
                            'options'    => $options,
                        ) );
                        break;

                    case 'date':
                        $cmb->add_field( array(
                            'name'       => $description->label,
                            'id'         => $key,
                            'type' => 'text_date',
                            // 'timezone_meta_key' => 'wiki_test_timezone',
                            // 'date_format' => 'l jS \of F Y',
                        ) );
                        break;

                    case 'boolean':
                        $cmb->add_field( array(
                            'name'       => $description->label,
                            'id'         => $key,
                            'type' => 'checkbox',
                        ) );
                        break;

                    case 'point':
                        $cmb->add_field( array(
                            'name'       => $description->label,
                            'id'         => $key,
                            'type' => 'leaflet_map',
                            'attributes' => [
                                'tilelayer'           => 'http://{s}.tile.osm.org/{z}/{x}/{y}.png',
                                'searchbox_position'  => 'topright', // topright, bottomright, topleft, bottomleft,
                                'search'              => __( 'Buscar...', 'wp-mapas' ),
                                'not_found'           => __( 'Não encontrado', 'wp-mapas' ),
                                'initial_coordinates' => [
                                    'lat' => -23, 
                                    'lng' => -46 
                                ],
                                'initial_zoom' => 4, // Zoomlevel when there's no coordinates set,
                                'default_zoom' => 13 // Zoomlevel after the coordinates have been set & page saved
                            ]
                        ) );
                    
                        break;
                }
            }   
        }
    }
}