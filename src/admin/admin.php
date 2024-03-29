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
    add_menu_page('Mapas Culturais', 'Mapas Culturais', 'manage_options', 'wp-mapasculturais', 'WPMapasCulturais\\config_page', 'dashicons-location-alt');
    add_submenu_page('wp-mapasculturais', 'Configurações', 'Configurações', 'manage_options', 'wp-mapasculturais', 'WPMapasCulturais\\config_page');
    add_submenu_page('wp-mapasculturais', 'Agentes', 'Agentes', 'manage_options', 'wp-mapasculturais-agents', 'WPMapasCulturais\\config_agents');
    add_submenu_page('wp-mapasculturais', 'Espaços', 'Espaços', 'manage_options', 'wp-mapasculturais-spaces', 'WPMapasCulturais\\config_spaces');
    add_submenu_page('wp-mapasculturais', 'Eventos', 'Eventos', 'manage_options', 'wp-mapasculturais-events', 'WPMapasCulturais\\config_events');
});

function config_page(){
    include __DIR__ . '/pages/mapasculturais-config.php';
}

function config_agents(){
    include __DIR__ . '/pages/mapasculturais-agents.php';
}

function config_spaces(){
    include __DIR__ . '/pages/mapasculturais-spaces.php';
}

function config_events(){
    include __DIR__ . '/pages/mapasculturais-events.php';
}

add_action('add_meta_boxes', function() {
    add_meta_box('occurrences', __('Quando e onde', 'wp-mapas'), 'WPMapasCulturais\\event_occurrences_metabox','event','normal');
});

function event_occurrences_metabox() {
    $entityId = get_post_meta(get_the_ID(), 'MAPAS:entity_id', true); ?>
    <div id="quando-onde">
        <mc-occurrence-cmb :event="<?= (empty($entityId) ? -1 : $entityId) ?>" :post="<?= get_the_ID() ?>"></mc-occurrence-cmb>
    </div>
    <?php
}

add_action( 'admin_init', 'WPMapasCulturais\\register_settings' );
function register_settings(){
    register_setting( 'mapasculturais', 'MAPAS:url' );
    register_setting( 'mapasculturais', 'MAPAS:private_key' );
    register_setting( 'mapasculturais', 'MAPAS:public_key' );
    register_setting( 'mapasculturais', 'MAPAS:import-entities-interval' );

    register_setting( 'mapasculturais_agents', 'MAPAS:agent:auto_import' );
    register_setting( 'mapasculturais_agents', 'MAPAS:agent:import' );
    register_setting( 'mapasculturais_agents', 'MAPAS:agent:verified' );
    register_setting( 'mapasculturais_agents', 'MAPAS:agent:types' );
    register_setting( 'mapasculturais_agents', 'MAPAS:agent:areas' );

    register_setting( 'mapasculturais_spaces', 'MAPAS:space:auto_import' );
    register_setting( 'mapasculturais_spaces', 'MAPAS:space:import' );
    register_setting( 'mapasculturais_spaces', 'MAPAS:space:verified' );
    register_setting( 'mapasculturais_spaces', 'MAPAS:space:types' );
    register_setting( 'mapasculturais_spaces', 'MAPAS:space:areas' );

    register_setting( 'mapasculturais_events', 'MAPAS:event:auto_import' );
    register_setting( 'mapasculturais_events', 'MAPAS:event:import' );
    register_setting( 'mapasculturais_events', 'MAPAS:event:verified' );
    register_setting( 'mapasculturais_events', 'MAPAS:event:age_ratings' );
    register_setting( 'mapasculturais_events', 'MAPAS:event:languages' );
}

add_action('admin_enqueue_scripts', 'WPMapasCulturais\\admin_scripts');
function admin_scripts(){
    wp_enqueue_script('wp-mapasculturais-admin', plugin_dir_url(__FILE__) . '/assets/admin.js', ['jquery'], false, true);
    wp_enqueue_style('wp-mapasculturais-admin', plugin_dir_url(__FILE__) . '/assets/admin.css');

    wp_localize_script('wp-mapasculturais-admin', 'mapas', [
        'wpUrl' => get_bloginfo('url'),
        'url' => Plugin::getOption('url'),
        'publicKey' => Plugin::getOption('public_key')
    ]);

    if(isset($_GET['post']) && isset($_SESSION['MAPAS:error:' . $_GET['post']])){
        $e = $_SESSION['MAPAS:error:' . $_GET['post']];
        $response = $e->curl->response;

        wp_localize_script('wp-mapasculturais-admin', 'mc_errors', (array) $response->data);

        unset($_SESSION['MAPAS:error:' . $_GET['post']]);

    }

    /* Assets processed by Laravel-Mix */
    wp_enqueue_script('wp-mapasculturais-laravel-admin', plugin_dir_url(__FILE__) . '/../../dist/admin.js', [], false, true);
    wp_enqueue_style('wp-mapasculturais-laravel-admin', plugin_dir_url(__FILE__) . '/../../dist/admin.css');
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