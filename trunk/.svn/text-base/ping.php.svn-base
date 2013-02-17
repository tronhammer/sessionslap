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
 * @subpackage PilotPress
 * @since 3.5.1
 *
 */

/**
 * jQuery Ping Hooks
 * 
 * Necessary jQuery to do ajax requests to this same file in order 
 * to commit session updates to PilotPress.
 *
 * @since 1.7.1
 *
 */
function sessionslap_face(){
	$defaults = sessionslap_get_default_options();
	$options  = get_option('plugin_sessionslap_options');
	
	// Update with defaults if database entries don't exist yet
	if (is_array($options)){
		foreach($options as $optionName=>$option){
			if (empty($option)){
				$options[ $optionName ] = $defaults[ $optionName ];
			}
		}
	} else {
		$options = $defaults;
	}

	?>
	<script type="text/javascript">
	jQuery(function($){
		window.sessionslap = {
			// Show alerts in top right corner?
			"alerts": <?php echo ( $options['alerts'] == 'on' ? 1 : 0 ); ?>,
			// Duration alerts should hang for, in ms
			"alert_hang_time": <?php echo (int) $options['hang_duration'] * 1000; ?>, // 5 seconds
			// Interval of time between each keep-alive ping
			"interval_time": <?php echo (int) $options['interval_duration'] * (60*1000); ?>, //1800000 == 30 min,
			"init": function(){
				window.sessionslap.pinger_interval_id = setInterval( this.pinger, this.interval_time);
			},
			"pinger": function(){
				$(document).trigger("sessionslap.ping.start");
				jQuery.ajax({
					url: "?",
					type: "GET",
					data: {
						update: true,
						r: Math.random()
					},
					success: function(data){
						if (window.sessionslap.alerts){
							window.sessionslap.alert("Your session has been updated!", true );
						}
						$(document).trigger("sessionslap.ping.end.success");
						console.log("Your session has been updated!");
					},
					error: function(data){
						if (window.sessionslap.alerts){
							window.sessionslap.alert("There was an issue with your session getting updated!");
						}
						$(document).trigger("sessionslap.ping.end.error");
						console.log("There was an issue with your session getting updated!");
					}
				});
			},
			"alert": function(msg, good){
				$alert = $("<div>").text( msg ).addClass("sessionslap-alert " + ( good ? "sessionslap-success" : "sessionslap-error" ) ).bind("click", function(e){
					$(this).stop();
				});
				$("body").append( $alert ).find(".sessionslap-alert").delay( this.alert_hang_time ).fadeOut(1500, function(e){
					$(this).remove();
				});
			}
		}
		
		<?php
		if ($options['enabled'] == 'on'){
		?>
		window.sessionslap.init();
		<?php
		}
		?>
	});
	</script>
	<?php
}