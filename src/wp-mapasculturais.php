<?php
/**
Plugin Name: WP Mapas Culturais
Version: 1.0.0
Author: Rafael Freitas
Author uri: https://hacklab.com.br
Description: Plugin de integração escrita e leitura com o Mapas Culturais

 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

define('WP_MAPAS__BASE_PATH', __DIR__ . '/');
define('WP_MAPAS__VIEWS_PATH', __DIR__ . '/views/');
define('WP_MAPAS__VENDOR_PATH', __DIR__ . '/vendor/');


require WP_MAPAS__VENDOR_PATH . 'MapasSDK/vendor/autoload.php';

require __DIR__ . '/includes/cache.php';

require __DIR__ . '/includes/post-types.php';

require __DIR__ . '/includes/api-wrapper.php';

require __DIR__ . '/includes/plugin.php';

if(is_admin()){
    require __DIR__ . '/admin/include.php';
}

global $wp_mapasculturais;
register_activation_hook( __FILE__, [$wp_mapasculturais, 'action__activate'] );
register_deactivation_hook( __FILE__, [$wp_mapasculturais, 'action__deactivate'] );

// [events view="calendar"]
function events_shortcodes( $atts ) {
	$a = shortcode_atts( array(
        'filters' => true,
		'view' => 'calendar'
    ), $atts );

    $filename = WP_MAPAS__VIEWS_PATH . $a['view'] . '.php';

    if(file_exists($filename)){
        ob_start();
        extract($a, EXTR_SKIP);
        include $filename;
        return ob_get_clean();
    }
}
add_shortcode( 'events', 'events_shortcodes' );

function mc_enqueue_scripts () {
    wp_enqueue_style('wp-mapasculturais-css', '/wp-content/plugins/wp-mapasculturais/dist/index.css');
    wp_enqueue_style('fontawesome5', '/wp-content/plugins/wp-mapasculturais/vendor/fontawesome-free/css/all.min.css');

    wp_enqueue_script('wp-mapasculturais', '/wp-content/plugins/wp-mapasculturais/dist/index.js', [], false, true);
}
add_action('wp_enqueue_scripts', 'mc_enqueue_scripts');

session_start();