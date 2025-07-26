<?php
/**
 * Plugin Name: Voting System
 * Description: Sistema de votações personalizado
 * Version: 2.0.0
 * Author: Guilherme Mandeli
 */

defined( 'ABSPATH' ) || exit;

// Define constantes do plugin
define( 'VS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'VS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'VS_PLUGIN_VERSION', '2.0.0' );

// Carrega o bootstrap
require_once VS_PLUGIN_PATH . 'bootstrap.php';
