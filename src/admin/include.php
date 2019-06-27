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

$linguagens = new \Advanced_Taxonomy_Metabox( 'linguagem', array( 'event' ), 'checkbox' );
$linguagens->set( 'priority', 'core' );

add_action('admin_menu', function(){
    add_menu_page('Mapas Culturais', 'Mapas Culturais', 'manage_options', 'wp-mapasculturais', 'WPMapasCulturais\\config_page', 'dashicons-location-alt');
    add_submenu_page('wp-mapasculturais', 'Configurações', 'Configurações', 'manage_options', 'wp-mapasculturais-config', 'WPMapasCulturais\\config_page');
});

function config_page(){
    include __DIR__ . '/pages/mapasculturais-config.php';
}

add_action( 'admin_init', 'WPMapasCulturais\\register_settings' );
function register_settings(){
    register_setting( 'mapasculturais', 'MAPAS:url' );
    register_setting( 'mapasculturais', 'MAPAS:private_key' );
    register_setting( 'mapasculturais', 'MAPAS:public_key' );
    
    register_setting( 'mapasculturais', 'MAPAS:agent:import' );
    register_setting( 'mapasculturais', 'MAPAS:space:import' );
    register_setting( 'mapasculturais', 'MAPAS:event:import' );
}

add_action('admin_enqueue_scripts', 'WPMapasCulturais\\admin_scripts');
function admin_scripts(){
    wp_enqueue_script('wp-mapasculturais-admin', plugin_dir_url(__FILE__) . '/assets/js/admin.js');
    
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
        
        /**
         * Initiate the metabox
         */
        $cmb = new_cmb2_box( array(
            'id'            => $post_type . '_metabox',
            'title'         => __( 'Dados', 'wp-mapas' ),
            'object_types'  => array( $post_type ), // Post type
            'context'       => 'normal',
            'priority'      => 'high',
            'show_names'    => true, // Show field names on the left
            // 'cmb_styles' => false, // false to disable the CMB stylesheet
            // 'closed'     => true, // Keep the metabox closed by default
        ) );


        foreach($metadata_description as $key => $description){
            switch($description->type){
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
                            'not_found'           => __( 'Not found', 'wp-mapas' ),
                            'initial_coordinates' => [
                                'lat' => -23, // Go Finland!
                                'lng' => -46  // Go Finland!
                            ],
                            'initial_zoom'        => 4, // Zoomlevel when there's no coordinates set,
                            'default_zoom'        => 13 // Zoomlevel after the coordinates have been set & page saved
                        ]
                    ) );
                
                    break;
                        

            }
        }
    }
}