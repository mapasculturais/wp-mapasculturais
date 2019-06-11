<?php
/**
Plugin Name: WP Mapas Culturais
Version: 1.0.0
Author: Rafael Freitas
Author uri: https://hacklab.com.br
Description: Plugin de integração escrita e leitura com o Mapas Culturais

 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

require __DIR__ . '/MapasSDK/vendor/autoload.php';

require __DIR__ . '/includes/post-types.php';

require __DIR__ . '/includes/api-wrapper.php';

require __DIR__ . '/includes/plugin.php';

if(is_admin()){
    require __DIR__ . '/admin/include.php';
}
