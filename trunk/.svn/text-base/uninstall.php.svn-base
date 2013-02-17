<?php
/**
 * Plugin to refresh PilotPress session to prevent expiration before wordpress timeout
 * 
 * This plugin will mimic a keep-alive ping by performing an ajax request to 
 * ping.php which will update the "rehash" session token tied to PilotPress. 
 * Session Slap will also provide a settings interface to allow admins to 
 * configure smaller end details
 *
 * @package Session Slap
 * @since 3.5.1
 *
 */
 
if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') ){
	exit();
}

delete_option('plugin_sessionslap_options');