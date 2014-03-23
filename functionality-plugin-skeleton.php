<?php
/*
Plugin Name: WordPress Functionality Plugin Skeleton
Description: The skeleton for WordPress functionality plugin
Version:     0.2
Author:      Ian Dunn
Author URI:  http://iandunn.name
*/

/*
 * This functionality plugin was built on top of WordPress-Functionality-Plugin-Skeleton by Ian Dunn.
 * See https://github.com/iandunn/WordPress-Functionality-Plugin-Skeleton for details.
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Access denied.' );
}

if ( ! class_exists( 'WordPress_Functionality_Plugin_Skeleton' ) ) {
	class WordPress_Functionality_Plugin_Skeleton {
		protected static $customized_plugins = array(
			'plugin-directory/plugin-filename.php'
		);
		protected $previous_error_handler;

		const PREFIX                  = 'wpfps_';
		const PRODUCTION_SERVER_NAME  = 'www.example.org';
		const PLUGIN_UPDATE_CHECK_URL = 'http://api.wordpress.org/plugins/update-check';

		/**
		 * Constructor
		 */
		public function __construct() {
			// Register actions, filters and shortcodes
			add_action( 'wp_footer',         array( $this, 'content_sensor_flag' ), 11 );
			add_action( 'login_footer',      array( $this, 'content_sensor_flag' ), 11 );

			add_filter( 'wp_mail',           array( $this, 'intercept_outbound_mail' ), 99 );
			add_filter( 'http_request_args', array( $this, 'block_plugin_updates' ), 5, 2 );
			add_filter( 'xmlrpc_enabled', '  __return_false' );   // Disable for security -- http://core.trac.wordpress.org/ticket/21509#comment:5

			foreach ( self::$customized_plugins as $filename ) {
				add_action( 'after_plugin_row_' . $filename, array( $this, 'custom_upgrade_warning' ), 10, 2 );
			}

			$this->previous_error_handler = set_error_handler( array( $this, 'ignore_third_party_warnings' ) );
		}

		/**
		 * Adds a message the to upgrade notice on the Plugins page to warn users not to upgrade customized plugins.
		 *
		 * @param string $filename
		 * @param array  $plugin
		 * @return array
		 */
		public static function custom_upgrade_warning( $filename, $plugin ) {
			?>

			<tr class="plugin-update-tr">
				<td colspan="3" class="plugin-update colspanchange">
					<div class="error inline">This plugin has been customized. Don't upgrade it without re-integrating the changes.</div>
				</td>
			</tr>

		<?php
		}

		/**
		 * Blocks specific plugins from being updated. Usually employed to prevent modified plugins from updating and overwriting customizations.
		 *
		 * Modified version of Mark Jaquith's technique.
		 * NOTE: This won't fire until the update_plugins site transient expires. Manually delete it if you want to see effect immediately.
		 *
		 * @link http://markjaquith.wordpress.com/2009/12/14/excluding-your-plugin-or-theme-from-update-checks/
		 * @param array  $request
		 * @param string $url
		 * @return array
		 */
		public function block_plugin_updates( $request, $url ) {
			if ( 0 !== strpos( $url, self::PLUGIN_UPDATE_CHECK_URL ) ) // todo moving to https at some point, if hasn't already
				return $request;

			$plugins = unserialize( $request['body']['plugins'] ); // todo use json now -- http://make.wordpress.org/core/2013/10/25/json-encoding-ssl-api-wordpress-3-7/
			foreach ( self::$customized_plugins as $cp ) {
				unset( $plugins->plugins[$cp] );
				unset( $plugins->active[array_search( $cp, $plugins->active )] );
			}
			$request['body']['plugins'] = serialize( $plugins );

			return $request;
		}

		/**
		 * Prevents emails from being sent to users from staging/development servers.
		 *
		 * @param array $args
		 */
		// Prevent sandbox e-mails from going to production email accounts
		function intercept_outbound_mail( $args ) {
			if ( self::PRODUCTION_SERVER_NAME != $_SERVER[ 'SERVER_NAME' ] ) {
				$original_message = $args[ 'message' ];
				unset( $args['message'] );

				$args[ 'message' ] = sprintf(
					"This message was intercepted and redirected to you to prevent users getting e-mails from staging/development servers.\n\nwp_mail() arguments:\n\n%s\n\nOriginal message:\n-----------------------\n\n%s",
					print_r( $args, true ),
					$original_message
				);

				$args[ 'to' ]      = get_bloginfo( 'admin_email' );
				$args[ 'subject' ] = '[Intercepted] ' . $args[ 'subject' ];
				$args[ 'headers' ] = '';	// wipe out CC and BCC
			}

			return $args;
		}

		/**
		 * Outputs a flag in the footer that an external monitoring service can check for.
		 *
		 * If the flag is detected, we know that Apache and MySQL are ok and that there were no fatal PHP errors in the header or content areas.
		 *
		 * @author Ian Dunn <ian@iandunn.name>
		 */
		public function content_sensor_flag() {
			echo '<!-- Monitor-WP-OK -->';
		}

		/*
		 * Ignore warnings/notices from poorly written third-party plugins/themes.
		 *
		 * If you can't fix 'em, might as well ignore 'em. This way you can still run
		 * `error_reporting` at `E_ALL` to make sure your own work is done right.
		 *
		 * Note that this method won't catch errors that occur before it was registered as the error
		 * handler. Most errors will happen after `plugins_loaded`, but if necessary you can
		 * convert this to an mu-plugin named `_ignore_third_party_warnings.php` so that it runs first.
		 *
		 * Also note that the slug matching is fairly loose in order to avoid dealing with
		 * subdirectories and other theme/plugin path variations, which may cause problems in edge
		 * cases.
		 *
		 * And one final note, when an error comes in that is outside of `error_reporting`, technically
		 * we should try calling the previous error handler if one was registered, on the outside chance
		 * that it wants to handle the error, but that's an edge case that would bloat the method. So, I
		 * chose to bloat the documentation instead :)
		 *
		 * @param int    $level   See set_error_handler() for parameter descriptions
		 * @param string $message
		 * @param string $file
		 * @param int    $line
		 * @param array  $context
		 */
		public function ignore_third_party_warnings( $level, $message, $file, $line, $context = array() ) {
			$bad_module_slugs = apply_filters( 'wpfps_bad_module_slugs', array() );

			// The error isn't one that error_reporting is set to handle.
			if ( ! ( error_reporting() & $level ) ) {
				return;
			}

			$module_file = str_replace(
				array_merge( array( WP_PLUGIN_DIR, WPMU_PLUGIN_DIR ), $GLOBALS['wp_theme_directories'] ),
				'',
				$file
			);

			// If the warning was triggered by a registered third-party plugin, ignore it and return
			foreach ( $bad_module_slugs as $slug ) {
				if ( false !== strpos( $module_file, $slug ) ) {
					return;
				}
			}

			/*
			 * The error wasn't from a registered third-party plugin, so handle it.
			 * If a custom handler was previously used, then call that. Otherwise mimic the default handler.
			 */
			if ( isset( $this->previous_error_handler ) && is_callable( $this->previous_error_handler ) ) {
				call_user_func( $this->previous_error_handler, $level, $message, $file, $line, $context );
			} else {
				$this->faux_php_default_error_handler( $level, $message, $file, $line, $context );
			}
		}

		/**
		 * Mimic PHP's default error handler
		 *
		 * It doesn't seem possible to call the default PHP error handler, so we have to mimic it,
		 * and also Xdebug's handler. Unfortunately this means that the Xdebug error description
		 * and stack traces will both includes references to this method.
		 *
		 * @param int    $level   See set_error_handler() for parameter descriptions
		 * @param string $message
		 * @param string $file
		 * @param int    $line
		 * @param array  $context
		 */
		protected function faux_php_default_error_handler( $level, $message, $file, $line, $context ) {
			$error_message = sprintf(
				'%s in %s on line %s',
				$message, $file, $line
			);

			if ( ini_get( 'display_errors' ) ) {
				if ( function_exists( 'xdebug_is_enabled' ) && xdebug_is_enabled() ) {
					xdebug_print_function_stack( sanitize_text_field( $error_message ) );
				} else {
					if ( ini_get( 'html_errors' ) ) {
						echo '<p>'. esc_html( $error_message ) .'</p>';
					} else {
						echo sanitize_text_field( $error_message );
					}
				}
			}

			if ( ini_get( 'log_errors' ) ) {
				error_log( sanitize_text_field( $error_message ) );
			}
		}

	} // end WordPress_Functionality_Plugin_Skeleton

	$GLOBALS['wpfps'] = new WordPress_Functionality_Plugin_Skeleton();
}
