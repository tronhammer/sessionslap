<?php
/*
Plugin Name: Session Slap
Plugin URI: http://officeautopilot.com/
Description: Session Slap will provide a means to refresh a users 
session token in order to prevent unintended logouts during runtime 
from expired session tokens.
Version: 1.8.1
Author: MoonRay, LLC
Author URI: http://officeautopilot.com/
License: GPLv2
Copyright: 2013, MoonRay, LLC (email : tron@ontraport.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as 
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
	
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
 * @global Array $sessionslap_errors
 */
if (!defined('SESSION_SLAP_PATH') || !defined('SESSION_SLAP_URI_PATH')){
	define('SESSION_SLAP_PATH', plugin_dir_path( __FILE__ ));
	define('SESSION_SLAP_URI_PATH', plugin_dir_url( __FILE__ ));
}
global $sessionslap_errors;
global $SESSION_SLAP_SESSIONLIFE;
	
if (empty($SESSION_SLAP_SESSIONLIFE)){
	$SESSION_SLAP_SESSIONLIFE = ini_get("session.gc_maxlifetime");
	if (!isset($SESSION_SLAP_SESSIONLIFE) || empty($SESSION_SLAP_SESSIONLIFE) || is_null($SESSION_SLAP_SESSIONLIFE)){
		$SESSION_SLAP_SESSIONLIFE = (24 * 60); // If the value is empty, or access restricted, use PHP's 24 minute default settings.
	}
}

/**
 * Default Values
 * 
 * Utilized during the validation process and during initial 
 * install, when pre existing values have not been set yet.
 *
 * @since 1.7.1
 *
 * @return Array Default Settings.
 */
function sessionslap_get_default_options() {
	global $SESSION_SLAP_SESSIONLIFE;
	$options = array(
		'enabled'           => 'on',
		'alerts'            => 'on',
		'interval_duration' => ($SESSION_SLAP_SESSIONLIFE / 60) - 5, // current INI setting minus 5 minutes.
		'hang_duration'     => 5
	);
	return $options;
}

/**
 * Initialize Options
 * 
 * Sets up initial options in the database if none exist 
 * or current settings are invalid.
 *
 * @since 1.7.1
 * @uses sessionslap_get_default_options()
 */
add_action('admin_init','sessionslap_options_init');
function sessionslap_options_init() {
	$sessionslap_stored_options = get_option( 'plugin_sessionslap_options' );
	if ( false === $sessionslap_options ) {
		$sessionslap_stored_options = sessionslap_get_default_options();
		update_option( 'plugin_sessionslap_options', $sessionslap_stored_options );
	} else {
		$sessionslap_valid_opts = sessionslap_validate( $sessionslap_stored_options );
		if (count( array_diff_assoc( $sessionslap_valid_opts, $sessionslap_stored_options ) ) ){
			update_option( 'plugin_sessionslap_options', $sessionslap_valid_opts );
		}
	}
}

/**
 * Initialize Options
 * 
 * Sets up initial options in the database if none exist 
 * or current settings are invalid.
 *
 * @since 1.7.1
 * @uses register_setting()
 * @uses add_settings_section()
 * @uses add_settings_field()
 */
add_action('admin_init', 'sessionslap_admin_init');
function sessionslap_admin_init(){
	register_setting(
		/* group */    'plugin_sessionslap_options',
		/* name */     'plugin_sessionslap_options',
		/* sanatize */ 'sessionslap_validate'
	);
	
	add_settings_section(
		/* id */       'plugin_sessionslap_enabled', 
		/* title */     __('Enabled Settings', 'sessionslap'), 
		/* callback */ 'sessionslap_enabled_description', 
		/* page */     'sessionslap_options'
	);
	add_settings_section(
		/* id */       'plugin_sessionslap_ping', 
		/* title */     __('Ping Settings', 'sessionslap'), 
		/* callback */ 'sessionslap_duration_description', 
		/* page */     'sessionslap_options'
	);
	add_settings_section(
		/* id */       'plugin_sessionslap_alerts', 
		/* title */     __('Alert Settings', 'sessionslap'), 
		/* callback */ 'sessionslap_alerts_description', 
		/* page */     'sessionslap_options'
	);
	
	// Enabled
	add_settings_field(
		/* id */       'sessionslap_enabled_checkbox', 
		/* title */    __('Enabled', 'sessionslap'), 
		/* callback */ 'sessionslap_enabled_checkbox_display', 
		/* page */     'sessionslap_options', 
		/* section */  'plugin_sessionslap_enabled'
		/* args */
	);
	
	// Interval
	add_settings_field(
		/* id */       'sessionslap_interval_duration_input', 
		/* title */    __('Interval Duration (in minutes)', 'sessionslap'), 
		/* callback */ 'sessionslap_interval_duration_input_display', 
		/* page */     'sessionslap_options', 
		/* section */  'plugin_sessionslap_ping'
		/* args */
	);
	
	// Hang
	add_settings_field(
		/* id */       'sessionslap_hang_duration_input', 
		/* title */    __('Alert Hang Duration (in seconds)', 'sessionslap'), 
		/* callback */ 'sessionslap_hang_duration_input_display', 
		/* page */     'sessionslap_options', 
		/* section */  'plugin_sessionslap_alerts'
		/* args */
	);
	
	// Alerts
	add_settings_field(
		/* id */       'sessionslap_alerts_dropdown', 
		/* title */    __('Visible Alerts', 'sessionslap'), 
		/* callback */ 'sessionslap_alerts_dropdown_display', 
		/* page */     'sessionslap_options', 
		/* section */  'plugin_sessionslap_alerts'
		/* args */
	);
	
}

/**
 * Description Fields
 *
 * These are utilized by the WordPress Settings API 
 * to build out the Session Slap settings page.
 */
function sessionslap_enabled_description() {
	echo '<p>' . __('This is a simple way to toggle the availability Session Slap plugin without having to deactivate.', 'sessionslap') . '</p>';
}
function sessionslap_duration_description() {
	echo '<p>' . __('These duration settings will configure the interval between each keep-alive ping to the server.', 'sessionslap') . '</p>';
}
function sessionslap_alerts_description() {
	echo '<p>' . __('These settings will define how alerts (informative pop ups that happen on success or failure of a keep-alive ping) are provided.', 'sessionslap') . '</p>';
}

/**
 * Input Fields
 *
 * These are utilized by the WordPress Settings API 
 * to build out the Session Slap settings page.
 *
 * @uses sessionslap_get_default_options()
 * @uses get_option()
 */
function sessionslap_enabled_checkbox_display(){
	$defaults = sessionslap_get_default_options();
	$options  = get_option('plugin_sessionslap_options');
	$selected = ($options['enabled'] == 'on' || (empty($options['enabled']) && $defaults['enabled'] == 'on') ) ? ' checked="checked"' : '';
	echo '<input id="plugin_sessionslap_options_enabled" name="plugin_sessionslap_options[enabled]" size="40" type="checkbox" value="on"' . $selected . '/>';
}
function sessionslap_interval_duration_input_display(){
	$defaults = sessionslap_get_default_options();
	$options  = get_option('plugin_sessionslap_options');
	echo '<input id="plugin_sessionslap_options_interval_duration" name="plugin_sessionslap_options[interval_duration]" size="5" type="text" value="' . (empty($options['interval_duration']) ? $defaults['interval_duration'] : $options['interval_duration']) . '"/>';
}
function sessionslap_hang_duration_input_display(){
	$defaults = sessionslap_get_default_options();
	$options  = get_option('plugin_sessionslap_options');
	echo '<input id="plugin_sessionslap_options_hang_duration" name="plugin_sessionslap_options[hang_duration]" size="5" type="text" value="' . (empty($options['hang_duration']) ? $defaults['hang_duration'] : $options['hang_duration'] ) . '"/>';
}
function sessionslap_alerts_dropdown_display(){
	$defaults     = sessionslap_get_default_options();
	$options      = get_option('plugin_sessionslap_options');
	$on_selected  = ($options['alerts'] == 'on' || (empty($options['alerts']) && $defaults['alerts'] == 'on') ? ' selected="selected"' : '');
	$off_selected = ($options['alerts'] == 'off' || (empty($options['alerts']) && $defaults['alerts'] == 'off') ? ' selected="selected"' : '');
	echo '<select id="plugin_sessionslap_options_alerts" name="plugin_sessionslap_options[alerts]">';
	echo '<option value="on"' . $on_selected . '>' . __('On', 'sessionslap') . '</option>';
	echo '<option value="off"' . $off_selected . '>' . __('Off', 'sessionslap') . '</option>';
	echo '</select>';
}

/**
 * Input Sanitizer
 * 
 * Utilized by WordPress filters during option updates 
 * to sterilize input provided by admin. This will also 
 * report inconsistancies and invalid options to the 
 * $sessionslap_errors variable
 *
 * @since 1.7.1
 *
 * @uses sessionslap_get_default_options()
 *
 * @param Array $values Dictionary of options to be sanitized. 
 * @return Array Dictionary of sanitized options.
 */
function sessionslap_validate($values){
	global $sessionslap_errors;
	$defaults = sessionslap_get_default_options();
	$sessionslap_errors = array();
	if (is_array($values)){
		foreach($values as $valueName => $value){
			$value = trim( $value );
			switch($valueName){
				case 'enabled':
					if ( strtolower( $value ) != 'on' && strtolower( $value ) != 'off'){
						$values[ $valueName ] = $defaults[ $valueName ];
						$sessionslap_errors[ $valueName ] = array(
							'value'=>$value, 
							'fn'=>__('Enabled', 'sessionslap')
						);
					}
					unset( $defaults[ $valueName ]);
					break;
				case 'interval_duration':
					if (!is_numeric( $value )){
						$values[ $valueName ] = $defaults[ $valueName ];
						$sessionslap_errors[ $valueName ] = array(
							'value'=>$value, 
							'fn'=>__('Interval Duration', 'sessionslap')
						);
					}
					unset( $defaults[ $valueName ]);
					break;
				case 'hang_duration':
					if (!is_numeric( $value )){
						$values[ $valueName ] = $defaults[ $valueName ];
						$sessionslap_errors[ $valueName ] = array(
							'value'=>$value, 
							'fn'=>__('Alert Hang Duration', 'sessionslap')
						);
					}
					unset( $defaults[ $valueName ]);
					break;
				case 'alerts':
					if ( strtolower( $value ) != 'on' && strtolower( $value ) != 'off'){
						$values[ $valueName ] = $defaults[ $valueName ];
						$sessionslap_errors[ $valueName ] = array(
							'value'=>$value, 
							'fn'=>__('Alerts', 'sessionslap')
						);
					}
					unset( $defaults[ $valueName ]);
					break;
			}
		}
	}
	
	if (count($defaults)){
		// Add missing defaults which should trigger extras in array_diff checks
		$values = array_merge($values, $defaults);
	}
	return $values;
}


/**
 * Ping Logic
 * 
 * Imports jQuery logic to head which can then be utilized 
 * to send ajax calls to the same file to update the PilotPress
 * session.
 *
 * @since 1.7.1
 *
 * @uses wp_enqueue_script()
 * @uses add_action()
 */
add_action('init', 'sessionslap_ping');
function sessionslap_ping(){
	// Register JavaScript
	wp_enqueue_script('jquery');
	
	error_log("including the css from ".plugins_url( 'sessionslap.css' , __FILE__ ));
	
	// Register CSS
	wp_register_style(
		"sessionslap_css",
		plugins_url( 'sessionslap.css' , __FILE__ ),
		false,
		"0.1.0"
	);
	wp_enqueue_style("sessionslap_css");
	
	require_once( SESSION_SLAP_PATH . "/ping.php");
	
	// Append dynamic js to both admin and regular users head.
	add_action( "admin_head", "sessionslap_face" );
	add_action( "wp_head", "sessionslap_face" );
	
}

/**
 * Settings Menu
 * 
 * Adds the Session Slap menu to the settings menu
 *
 * @since 1.7.1
 *
 * @uses add_options_page()
 */
add_action('admin_menu', 'sessionslap_menu_item');
function sessionslap_menu_item() {
	add_options_page( 
		__('Session Slap', 'sessionslap'),
		__('Session Slap', 'sessionslap'),
		'manage_options',
		'sessionslap_options',
		'sessionslap_options_page'
	);
}

/**
 * Settings Page
 * 
 * Outputs the required html for the settings page
 *
 * @since 1.7.1
 *
 * @uses screen_icon()
 * @uses settings_fields()
 * @uses do_settings_sections()
 * @uses submit_button()
 */
function sessionslap_options_page(){ 
	global $sessionslap_errors;
	if ( isset( $_GET['settings-updated'] ) ){
		echo '<div class="updated"><p>' . __('Session Slap settings updated successfully.', 'sessionslap') . '</p></div>';
		if (count($sessionslap_errors)){
			echo '<div class="error"><p>' . __('There was an issue loading the following fields.', 'sessionslap').'</p><ul>';
			foreach($sessionslap_errors as $fieldName => $fieldInfo){
				echo '<li style="margin-left:30px"><strong>' . $fieldInfo['fn'] . '</strong></li>';
			}
			echo '</ul></div>';
		}

	}
	
	// start PRINT_FORM
	?>
<div class="wrap">
	<?php screen_icon(); ?>
	<h2><?php _e('Session Slap Settings', 'sessionslap'); ?></h2>
	
	<form action="options.php" method="post">
	<?php
		settings_fields('plugin_sessionslap_options');
		do_settings_sections('sessionslap_options');
		
		echo '<button class="button" style="margin-top:23px;float:left;margin-right:500px;" onclick="javascript:window.sessionslap.pinger(); return false;">' . __('Test Slap', 'sessionslap') . '</button>';
		
		submit_button(); 
	?>
	</form>
</div>
<?php } // end PRINT_FORM
