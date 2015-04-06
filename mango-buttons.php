<?php
/*
Plugin Name: Mango Buttons
Plugin URI: https://mangobuttons.com
Description: Mango Buttons is a button creator for WordPress that allows anyone to create beautiful buttons anywhere on their site.
Version: 1.0.4
Author: Phil Baylog
Author URI: https://mangobuttons.com
License: GPLv2
*/

//Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

define( 'MB_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'MB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

global $MB_VERSION;
$MB_VERSION = '1.0.4';

class MangoButtons{

	private static $instance;

	private function __construct(){

		$this->include_before_plugin_loaded();
		add_action('plugins_loaded', array($this, 'include_after_plugin_loaded'));
		
		add_action('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_upgrade_link_to_plugins_page'));
 		add_action('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link_to_plugins_page'));
	}

	/** Singleton Instance Implementation **********/
	public static function instance(){
		if ( !isset( self::$instance ) ){
			self::$instance = new MangoButtons();
			self::$instance->init();
			//self::$instance->load_textdomain();
		}
		return self::$instance;
	}

	//called before the 'plugins_loaded action is fired
	function include_before_plugin_loaded(){
		global $wpdb;
	}
	
	function add_upgrade_link_to_plugins_page($links){
		$upgrade_link = '<a href="https://mangobuttons.com/pricing" target="_blank">Upgrade to PRO</a>'; 
	  array_unshift($links, $upgrade_link);
	
	  return $links;
	}
	
	function add_settings_link_to_plugins_page($links){
		$settings_link = '<a href="options-general.php?page=mb-admin">Settings</a>'; 
	  array_unshift($links, $settings_link);
	
	  return $links;
	}
	
	function add_mb_tiny_mce_button($buttons){
		array_push($buttons, 'mangobuttons');
		
		return $buttons;
	}
	function add_mb_tiny_mce_js($plugin_array){
		$plugin_array['mangobuttons'] = plugins_url( '/admin/js/tinymce.mangobuttons-plugin.js',__file__);
	  
		return $plugin_array;
	}
	function add_mb_tiny_mce_css($mce_css){
		if(!empty($mce_css)){
			$mce_css .= ',';
		}
		
		$mce_css .= MB_PLUGIN_URL . 'public/style/mb-button.css';//mb button styles (includes open sans google font)
		$mce_css .= ',' . '//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css';//fontawesome
		
		return $mce_css;
	}
	
	function render_mb_modal(){
		readfile(MB_PLUGIN_PATH . 'admin/views/mb-modal.html');
	}

	//called after the 'plugins_loaded action is fired
	function include_after_plugin_loaded(){
		
		global $MB_VERSION;
		
		//update database or options if plugin version updated
		if(get_option('MB_VERSION') != $MB_VERSION){
			mb()->initializeMBOptions();
			
			update_option('MB_VERSION', $MB_VERSION);
		}

		//admin only includes
		if( is_admin() ){
			
			/*If setup is not complete, render the setup page. Otherwise, render the settings page*/
			if(false && !get_option('mb_setup_complete')){//TODO remove "FALSE"
				include_once( MB_PLUGIN_PATH . 'admin/controllers/setup.php');
			}
			else{
				include_once( MB_PLUGIN_PATH . 'admin/controllers/settings.php');
			}
			
			//Add tiny mce button filters (one for button and one for JS)
			add_filter('mce_buttons', array( $this, 'add_mb_tiny_mce_button' ) );
			add_filter('mce_external_plugins', array( $this, 'add_mb_tiny_mce_js' ) );
			add_filter('mce_css', array( $this, 'add_mb_tiny_mce_css' ) );

			//include ajax handler for processing ajax calls made from admin pages
			include_once( MB_PLUGIN_PATH . 'admin/ajax/mb-ajax-handler.php');
			
			//TODO check if edit post / edit page & only include if on one of those pages
			add_action('admin_footer', array( $this, 'render_mb_modal') );
		}

		add_action( 'wp_print_scripts',					array( $this, 'print_scripts'		) );
		add_action( 'admin_print_scripts',			array( $this, 'print_scripts'	) );
		add_action( 'wp_print_styles',					array( $this, 'print_styles'			) );
		add_action( 'admin_print_styles',				array( $this, 'print_styles'	) );

	}

	private function init(){

	}
	
	static function initializeMBOptions(){
		
		if(!get_option('mb_setup_complete')){
			update_option('mb_setup_complete', false);
		}
		if(!get_option('mb_email')){
			update_option('mb_email', '');
		}
		if(!get_option('mb_subscribed')){
			update_option('mb_subscribed', false);
		}
		
	}
	
	static function destroyMBOptions(){
		return;
		delete_option('MB_VERSION');
		delete_option('mb_setup_complete');
		delete_option('mb_email');
		delete_option('mb_subscribed');
	}
	
	static function destroyMBDB(){
		
		return;
		
		global $wpdb;
		
		//$sql = "DROP TABLE IF EXISTS " . $wpdb->mb_bars . ", " . $wpdb->mb_views . ", " . $wpdb->mb_conversions . ";";
		
		//$wpdb->query($sql);
	}

	static function activate(){
		
		
	}

	static function deactivate(){
		//This should be done every time plugin is deactivated
	}
	
	/*Delete all mb options, bars, and conversion data, and deactivate the plugin*/
	static function deactivateAndDestroyMBData(){
		return;
		global $MB_VERSION;
		
		mb()->destroyMBOptions();
		mb()->destroyMBDB();
		
		//if plugin is in default folder name
		if(is_plugin_active('mango-buttons/mango-buttons.php')){
			deactivate_plugins('mango-buttons/mango-buttons.php');
		}
		
		//if plugin is in '-plugin' folder name
		if(is_plugin_active('mango-buttons-plugin/mango-buttons.php')){
			deactivate_plugins('mango-buttons-plugin/mango-buttons.php');
		}
		
		//if plugin is in versioned folder name
		if(is_plugin_active('mango-buttons-' . $MB_VERSION . '/mango-buttons.php')){
			deactivate_plugins('mango-buttons-' . $MB_VERSION . '/mango-buttons.php');
		}
		
	}

	function print_scripts(){
		
		global $MB_VERSION;
		
		if( is_admin() ){

			wp_enqueue_script('knockout', MB_PLUGIN_URL . 'admin/js/inc/knockout-3.2.0.js', array('jquery'), '3.2.0', true);
			wp_enqueue_script('knockout-mb-utilities', MB_PLUGIN_URL . 'admin/js/inc/knockout-utilities.js', array('jquery', 'knockout'), '3.2.0', true);
			
			//COL PICK
			
			
			//MB dialog
			wp_enqueue_script('mb-modal', MB_PLUGIN_URL . 'admin/js/mb-modal.js', array( 'jquery' ), $MB_VERSION, false);
			wp_localize_script('mb-modal', 'ajaxurl', admin_url('admin-ajax.php') );
			
			wp_enqueue_script('colpick', MB_PLUGIN_URL . 'admin/js/inc/colpick/js/colpick.js', array('jquery'), '0.0.0', true);
			wp_enqueue_script('tooltipster', MB_PLUGIN_URL . 'admin/js/inc/tooltipster/jquery.tooltipster.min.js', array('jquery'), '0.0.0', true);
		}

	}

	function print_styles(){
		
		global $MB_VERSION;
		
		//if admin...
		if( is_admin() ){
			
			wp_enqueue_style('mb-admin', MB_PLUGIN_URL . 'admin/style/mb.css', false, microtime(), 'all');
			
			//todo add check if editing post?
			wp_enqueue_style('colpick', MB_PLUGIN_URL . 'admin/js/inc/colpick/css/colpick.css', false, '0.0.0', 'all');
			wp_enqueue_style('tooltipster', MB_PLUGIN_URL . 'admin/js/inc/tooltipster/tooltipster.css', false, '0.0.0', 'all');
			
		}

		//always...
		
		//required fonts for MB
		wp_enqueue_style( 'fontawesome', '//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css', false, '4.3.0', 'all' );
		
		//Open Sans font already included in mb-button.css stylesheet (see next line)
		
		//public mb_button styles
		wp_enqueue_style( 'mb', MB_PLUGIN_URL . 'public/style/mb-button.css', false, $MB_VERSION, 'all');
		
	}
	
}/*end MangoButtons class*/

//The main function used to retrieve the one true MangoButtons instance (a shortcut for MangoButtons::instance())
function mb(){
	return MangoButtons::instance();
}

//initialize
mb();

//activation
if(is_admin()){
	register_activation_hook( __FILE__, array( 'MangoButtons', 'activate') );
}

//deactivation
if(is_admin()){
	register_deactivation_hook( __FILE__, array( 'MangoButtons', 'deactivate') );
}
?>
