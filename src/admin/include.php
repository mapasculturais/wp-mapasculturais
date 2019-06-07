<?php
add_action('admin_menu', function(){
    add_menu_page('Mapas Culturais', 'Mapas Culturais', 'manage_options', 'wp-mapasculturais', 'mapasculturais_config_page', 'dashicons-location-alt');
    add_submenu_page('wp-mapasculturais', 'Configurações', 'Configurações', 'manage_options', 'wp-mapasculturais-config', 'mapasculturais_config_page');
});

function mapasculturais_config_page(){
    include __DIR__ . '/pages/mapasculturais-config.php';
}

add_action( 'admin_init', 'mapasculturais_register_settings' );

function mapasculturais_register_settings(){
    register_setting( 'mapasculturais', 'mapasculturais_url' );
    register_setting( 'mapasculturais', 'mapasculturais_private_key' );
    register_setting( 'mapasculturais', 'mapasculturais_public_key' );
}