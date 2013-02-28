<?php
/*
Plugin Name: WordPress Functionality Plugin Skeleton
Description: The skeleton for WordPress functionality plugin
Version: 0.1
Author: Ian Dunn
Author URI: http://iandunn.name
*/

/*
 * This functionality plugin was built on top of WordPress-Functionality-Plugin-Skeleton by Ian Dunn.
 * See https://github.com/iandunn/WordPress-Functionality-Plugin-Skeleton for details.
 */

 
if( $_SERVER[ 'SCRIPT_FILENAME' ] == __FILE__ )
	die( 'Access denied.' );

if( !class_exists( 'WordPressFunctionalityPluginSkeleton' ) )
{
	class WordPressFunctionalityPluginSkeleton
	{
		// Declare variables and constants
		protected static $customizedPlugins = array(
			'plugin-directory/plugin-filename.php'
		);
		
		const PREFIX					= 'wpfps_';
		const PRODUCTION_SERVER_NAME	= 'www.example.com';
		const PLUGIN_UPDATE_CHECK_URL	= 'http://api.wordpress.org/plugins/update-check';
		
		/**
		 * Constructor
		 * @author Ian Dunn <ian@iandunn.name>
		 */
		public function __construct()
		{	
			// Register actions, filters and shortcodes
			add_action( 'wp_footer',			array( $this, 'contentSensorFlag' ), 11 );
			add_action( 'login_footer',			array( $this, 'contentSensorFlag' ), 11 );
			
			add_filter( 'wp_mail', 				array( $this, 'redirectStagingMail' ) );
			add_filter( 'http_request_args',	array( $this, 'blockPluginUpdates' ), 5, 2 );
			add_filter( 'xmlrpc_enabled',		'__return_false' );		// Disable for security -- http://core.trac.wordpress.org/ticket/21509#comment:5
			
			foreach( self::$customizedPlugins as $filename )
				add_action( 'after_plugin_row_' . $filename, array( $this, 'customUpgradeWarning' ), 10, 2 );
		}

		/**
         * Adds a message the to upgrade notice on the Plugins page to warn users not to upgrade customized plugins
         * @author Ian Dunn <ian@iandunn.name>
		 * @param string $filename
		 * @param array $plugin
		 * @return array
         */
		public static function customUpgradeWarning( $filename, $plugin )
		{
			?>
			
			<tr class="plugin-update-tr">
				<td colspan="3" class="plugin-update colspanchange">
					<div class="error inline">This plugin has been customized. Don't upgrade it without re-integrating the changes.</div>
				</td>
			</tr>
			
			<?php
		}
		
		/**
         * Blocks specific plugins from being updated. Usually employed to prevent modified plugins from updating and overwriting customizations
		 * Modified version of Mark Jaquith's technique
		 * NOTE: This won't fire until the update_plugins site transient expires. Manually delete it if you want to see effect immediately
		 * 
		 * @link http://markjaquith.wordpress.com/2009/12/14/excluding-your-plugin-or-theme-from-update-checks/
         * @author Ian Dunn <ian@iandunn.name>
		 * @param array $request
		 * @param string $url
		 * @return array
         */
		public function blockPluginUpdates( $request, $url )
		{
			if( 0 !== strpos( $url, self::PLUGIN_UPDATE_CHECK_URL ) )
				return $request;
			
			$plugins = unserialize( $request[ 'body' ][ 'plugins' ] );
			foreach( self::$customizedPlugins as $cp )
			{
				unset( $plugins->plugins[ $cp ] );
				unset( $plugins->active[ array_search( $cp, $plugins->active ) ] );
			}
			$request[ 'body' ][ 'plugins' ] = serialize( $plugins );

			return $request;
		}
		
		/**
		 * Prevents emails from being sent to users from staging/development servers
		 * @author Ian Dunn <ian@iandunn.name>
		 * @param array $args
		 */
		public function redirectStagingMail( $args )
		{
			if( $_SERVER[ 'SERVER_NAME' ] != self::PRODUCTION_SERVER_NAME )
			{
				$args[ 'message' ]	= "This message was intercepted and redirected to you to prevent users getting e-mails from staging/development servers.\n\n". print_r( $args, true );
				$args[ 'to' ]		= get_bloginfo( 'admin_email' );
				$args[ 'subject' ]	= '[intercepted] '. $args[ 'subject' ];
				$args[ 'headers' ]	= '';	// wipe out CC and BCC
			}
			
			return $args;
		}
		
		/**
		 * Outputs a flag in the footer that an external monitoring service can check for
		 * If the flag is detected, we know that Apache and MySQL are ok and that there were no fatal PHP errors in the header or content areas
		 * @author Ian Dunn <ian@iandunn.name>
		 */
		public function contentSensorFlag()
		{
			echo '<!-- Monitor-WP-OK -->';
		}
		
	} // end WordPressFunctionalityPluginSkeleton
	
	$wpfps = new WordPressFunctionalityPluginSkeleton();
}

?>