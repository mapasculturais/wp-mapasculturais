<?php
/**
Plugin Name: WP Mapas Culturais
Version: 1.0.0
Author: Rafael Freitas
Author uri: https://hacklab.com.br
Description: Plugin de integração escrita e leitura com o Mapas Culturais

 */

namespace WPMapasCulturais;

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

define('WP_MAPAS__BASE_PATH', __DIR__ . '/');
define('WP_MAPAS__VIEWS_PATH', __DIR__ . '/views/');
define('WP_MAPAS__SINGLES_PATH', __DIR__ . '/singles/');
define('WP_MAPAS__VENDOR_PATH', __DIR__ . '/vendor/');


require WP_MAPAS__VENDOR_PATH . 'MapasSDK/vendor/autoload.php';

require __DIR__ . '/includes/cache.php';

require __DIR__ . '/includes/post-types.php';

require __DIR__ . '/includes/api-wrapper.php';

require __DIR__ . '/includes/plugin.php';

require __DIR__ . '/includes/helper.php';

if(is_admin()){
    require __DIR__ . '/admin/admin.php';
}

global $wp_mapasculturais;
register_activation_hook( __FILE__, [$wp_mapasculturais, 'action__activate'] );
register_deactivation_hook( __FILE__, [$wp_mapasculturais, 'action__deactivate'] );

// [events view="calendar"]
function events_shortcodes( $atts ) {
	$a = shortcode_atts( array(
        'agents' => NULL,
        'filters' => true,
        'spaces' => NULL,
		'view' => 'calendar'
    ), $atts );

    if( strtolower($a['filters']) === 'no' || strtolower($a['filters']) === 'false' ){
        $a['filters'] = false;
    }

    $filename = WP_MAPAS__VIEWS_PATH . $a['view'] . '.php';

    if(file_exists($filename)){
        ob_start();
        extract($a, EXTR_SKIP);
        include $filename;
        return ob_get_clean();
    }
}
add_shortcode( 'events', 'WPMapasCulturais\\events_shortcodes' );

add_shortcode( 'events-agenda', function($atts){
    $atts = (array) $atts;
    $atts['view'] = 'agenda';
    return events_shortcodes($atts);
} );
add_shortcode( 'events-calendar', function($atts){
    $atts = (array) $atts;
    $atts['view'] = 'calendar';
    return events_shortcodes($atts);
} );
add_shortcode( 'events-day', function($atts){
    $atts = (array) $atts;
    $atts['view'] = 'day';
    return events_shortcodes($atts);
} );
add_shortcode( 'events-list', function($atts){
    $atts = (array) $atts;
    $atts['view'] = 'list';
    return events_shortcodes($atts);
} );
add_shortcode( 'events-now', function($atts){
    $atts = (array) $atts;
    $atts['view'] = 'now';
    return events_shortcodes($atts);
} );


function mc_enqueue_scripts () {
    // @todo usar método mais elegante para a url
    $plugin_url = get_bloginfo('url') . '/wp-content/plugins/wp-mapasculturais/';
    
    wp_enqueue_script('jquery');
    wp_enqueue_script('js-cookies', $plugin_url . 'assets/js/js-cookies.js', [], false, true);

    wp_enqueue_style('wp-mapasculturais-css', $plugin_url . 'dist/index.css');
    wp_enqueue_style('fontawesome5', $plugin_url . 'vendor/fontawesome-free/css/all.min.css');
    
    wp_enqueue_script('wp-mapasculturais', $plugin_url . 'dist/index.js', ['js-cookies'], false, true);
    
    wp_enqueue_script('wp-procuration', $plugin_url . 'assets/js/procuration.js', ['js-cookies'], false, true);

    wp_localize_script('wp-procuration', 'mapas', [
        'url' => Plugin::getOption('url'),
        'publicKey' => Plugin::getOption('public_key'),
        'wpUrl' => get_bloginfo('url')
    ]);

    $plugin = Plugin::instance();

    if(!($age_ratings = $plugin->getOption('event:age_ratings'))){
        $age_ratings = $plugin->getEntityMetadataOptions('event', 'classificacaoEtaria');
    }

    sort($age_ratings);

    if(!($languages = $plugin->getOption('event:languages'))){
        $languages = $plugin->api->getTaxonomyTerms('linguagem');
    }
    if(!($areas = $plugin->getOption('space:areas'))){
        $areas = $plugin->api->getTaxonomyTerms('area');
    }

    wp_localize_script('wp-mapasculturais', 'mcTaxonomies', [
        'languages' => $languages,
        'areas' => $areas,
        'ageRatings' => $age_ratings
    ]);
}
add_action('wp_enqueue_scripts', 'WPMapasCulturais\\mc_enqueue_scripts');

session_start();