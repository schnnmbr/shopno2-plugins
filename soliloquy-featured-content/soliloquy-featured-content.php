<?php
/*
Plugin Name: Soliloquy Featured Content Addon
Plugin URI: http://soliloquywp.com/
Description: Enables dynamic featured content support for the Soliloquy for WordPress plugin.
Author: Thomas Griffin
Author URI: http://thomasgriffinmedia.com/
Version: 1.0.9
License: GNU General Public License v2.0 or later
License URI: http://www.opensource.org/licenses/gpl-license.php
*/

/*
	Copyright 2012  Thomas Griffin  (email : thomas@thomasgriffinmedia.com)

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

/** Load all of the necessary class files for the plugin */
spl_autoload_register( 'Tgmsp_FC::autoload' );

/**
 * Init class for the Featured Content Addon for Soliloquy.
 *
 * @since 1.0.0
 *
 * @package Tgmsp-FC
 * @author Thomas Griffin <thomas@thomasgriffinmedia.com>
 */
class Tgmsp_FC {

	/**
	 * Holds a copy of the object for easy reference.
	 *
	 * @since 1.0.0
	 *
	 * @var object
	 */
	private static $instance;

	/**
	 * Holds a copy of the main plugin filepath.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private static $file = __FILE__;

	/**
	 * Current version of the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public $version = '1.0.9';

	/**
	 * Constructor. Hooks all interactions into correct areas to start
	 * the class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		self::$instance = $this;

		/** Run a hook before the slider is loaded and pass the object */
		do_action_ref_array( 'tgmsp_fc_init', array( $this ) );

		register_activation_hook( __FILE__, array( $this, 'activation' ) );

		/** Load the plugin in the init hook (set high priority to make sure it loads after Soliloquy is fully loaded) */
		add_action( 'init', array( $this, 'init' ), 20 );

	}

	/**
	 * Activation hook. Checks to make sure that the main Soliloquy for
	 * WordPress plugin is active before proceeding.
	 *
	 * @since 1.0.0
	 */
	public function activation() {

		/** If the Tgmsp class doesn't exist, deactivate ourself and die */
		if ( ! class_exists( 'Tgmsp' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( __( 'The Soliloquy for WordPress plugin must be active before you can activate this plugin.', 'soliloquy-fc' ) );
		}

		/** If Soliloquy isn't the correct version, deactivate ourself and die */
		if ( version_compare( Tgmsp::get_instance()->version, '1.3.9', '<' ) ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( sprintf( __( 'The current version of Soliloquy for WordPress, <strong>%s</strong>, does not meet the required version of <strong>1.3.9</strong> to run this Addon. Please update Soliloquy to the latest version before activating this Addon.', 'soliloquy-fc' ), Tgmsp::get_instance()->version ) );
		}

	}

	/**
	 * Loads the plugin updater and all the actions and
	 * filters for the class.
	 *
	 * @since 1.0.0
	 *
	 * @global array $soliloquy_license Soliloquy license data
	 */
	public function init() {

		/** Load the plugin textdomain for internationalizing strings */
		load_plugin_textdomain( 'soliloquy-fc', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

		// Only load certain items in the admin.
		if ( is_admin() ) :
    		/** Instantiate all the necessary components of the plugin */
    		$tgmsp_fc_admin		= new Tgmsp_FC_Admin();
    		$tgmsp_fc_ajax		= new Tgmsp_FC_Ajax();
    		$tgmsp_fc_assets	= new Tgmsp_FC_Assets();
    		$tgmsp_fc_strings	= new Tgmsp_FC_Strings();

    		/** If the Preview Addon is available, load the Preview class */
    		if ( class_exists( 'Tgmsp_Preview', false ) ) // Don't check the autoload stack for this instantiation
    			$tgmsp_fc_preview = new Tgmsp_FC_Preview();

    		/** Setup the license checker */
    		global $soliloquy_license;

    		/** Only process update if a key has been entered and updates are on */
    		if ( isset( $soliloquy_license['license'] ) ) {
    			$args = array(
    				'remote_url' 	=> 'http://soliloquywp.com/',
    				'version' 		=> $this->version,
    				'plugin_name'   => 'Soliloquy Featured Content Addon',
    				'plugin_slug' 	=> 'soliloquy-featured-content',
    				'plugin_path' 	=> plugin_basename( __FILE__ ),
    				'plugin_url' 	=> WP_PLUGIN_URL . '/soliloquy-featured-content',
    				'time' 			=> 43200,
    				'key' 			=> $soliloquy_license['license']
    			);

    			/** Instantiate the automatic plugin updater class */
    			$tgmsp_fc_updater = new Tgmsp_Updater( $args );
    		}
        endif;

        // Load global components.
        $tgmsp_fc_shortcode	= new Tgmsp_FC_Shortcode();

	}

	/**
	 * PSR-0 compliant autoloader to load classes as needed.
	 *
	 * @since 1.0.0
	 *
	 * @param string $classname The name of the class
	 * @return null Return early if the class name does not start with the correct prefix
	 */
	public static function autoload( $classname ) {

		if ( 'Tgmsp_FC' !== mb_substr( $classname, 0, 8 ) )
			return;

		$filename = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . str_replace( '_', DIRECTORY_SEPARATOR, $classname ) . '.php';
		if ( file_exists( $filename ) )
			require $filename;

	}

	/**
	 * Helper method to determine if Soliloquy is inactive or not.
	 *
	 * @since 1.0.0
	 *
	 * @global string $pagenow The current page slug
	 * @return bool True if Soliloquy is not active, false otherwise
	 */
	public static function soliloquy_is_not_active() {

		global $pagenow;

		return ! ( class_exists( 'Tgmsp' ) ) && ! ( isset( $_GET['action'] ) && 'do-plugin-upgrade' == $_GET['action'] || 'plugin-editor.php' == $pagenow && isset( $_REQUEST['file'] ) && preg_match( '|^soliloquy|', $_REQUEST['file'] ) || 'update-core.php' == $pagenow );

	}

	/**
	 * Getter method for retrieving the object instance.
	 *
	 * @since 1.0.0
	 */
	public static function get_instance() {

		return self::$instance;

	}

	/**
	 * Getter method for retrieving the main plugin filepath.
	 *
	 * @since 1.0.0
	 */
	public static function get_file() {

		return self::$file;

	}

}

/** Instantiate the init class */
$tgmsp_fc = new Tgmsp_FC();