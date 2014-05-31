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

			add_filter( 'wp_headers',        array( $this, 'prevent_clickjacking' ), 10, 2 );
			add_filter( 'wp_mail',           array( $this, 'intercept_outbound_mail' ), 99 );
			add_filter( 'http_request_args', array( $this, 'block_plugin_updates' ), 5, 2 );
			add_filter( 'xmlrpc_enabled', '  __return_false' );   // Disable for security -- http://core.trac.wordpress.org/ticket/21509#comment:5

			foreach ( self::$customized_plugins as $filename ) {
				add_action( 'after_plugin_row_' . $filename, array( $this, 'custom_upgrade_warning' ), 10, 2 );
			}
		}

		/**
		 * Prevents clickjacking by sending the X-Frame-Options header
		 *
		 * WordPress automatically does this for the Administration Panels (see #12293), but avoids
		 * doing it on the front end because it can interfere with some legitimate remote services.
		 *
		 * Note that this doesn't work with certain page caching strategies, so it's better to set it as the
		 * web server layer if possible.
		 * @see http://wordpress.org/support/topic/plugin-wp-super-cache-bug-headers-not-being-included
		 * @see https://httpd.apache.org/docs/2.2/mod/mod_headers.html
		 *
		 * @todo Add support for ALLOW-FROM when it's safe to use (when modern browsers fully support it).
		 *
		 * @param array $headers
		 * @param object $wp
		 * @return array
		 */
		public function prevent_clickjacking( $headers, $wp ) {
			$excluded_page_slugs = array();

			if ( empty( $wp->query_vars['pagename'] ) || ! in_array( $wp->query_vars['pagename'], $excluded_page_slugs ) ) {
				$headers['X-Frame-Options'] = 'SAMEORIGIN';
			}

			return $headers;
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

	} // end WordPress_Functionality_Plugin_Skeleton

	$GLOBALS['wpfps'] = new WordPress_Functionality_Plugin_Skeleton();
}
