<?php
/**
 * Plugin Name: Eventos - Mapas Culturais
 * Plugin URI: https://github.com/ahmadawais/create-guten-block/
 * Description: Bloco gutenberg para exibição de eventos
 * Author: hacklab/
 * Author URI: https://hacklab.com.br/
 * Version: 1.0.0
 * License: GPL2+
 * License URI: https://www.gnu.org/licenses/gpl-2.0.txt
 *
 * @package CGB
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block Initializer.
 */
require_once plugin_dir_path( __FILE__ ) . 'src/init.php';
