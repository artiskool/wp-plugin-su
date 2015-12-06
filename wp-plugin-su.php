<?php
/**
 * Plugin Name: Artiskool WP SU Test
 * Plugin URI: http://wp.artiskool.com/su-test
 * Description: Artiskool Wordpress Software Update plugin test
 * Version: 0.1.0
 * Author: Art Layese
 * Author URI: http://artiskool.com
 */

if (! defined('ABSPATH'))
	exit; //Exit if accessed directly

/**
 * Artiskool_Plugin_Su main class
 * @class Artiskool_Plugin_Su
 */
class Artiskool_Plugin_Su
{
	/**
	 * Instance object holder
	 * @access private static
	 * @var $instance;
	 */
	private static $instance = null;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		register_activation_hook(__FILE__, array($this, 'install'));
	}

	/**
	 * Returns singleton instance of this class
	 * @access public static
	 * @return self
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
			self::$instance = new self();
		return self::$instance;
	}

	/**
	 * Activation Hook
	 * create custom table wp_hub_messages
	 */
	public function install()
	{
		global $wpdb;

		$table_name = $wpdb->prefix . 'test';
		$sql = "CREATE TABLE $table_name("
			. "		id bigint(20) NOT NULL AUTO_INCREMENT,"
			. "		parent_id bigint(20) NOT NULL,"
			. "		post_id bigint(20) NOT NULL,"
			. "		sender bigint(20) NOT NULL,"
			. "		recipient bigint(20) NOT NULL,"
			. "		UNIQUE KEY message_id (message_id)"
			. "	);";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}

	/**
	 * Make it private so that it will not be cloned
	 * @access private
	 */
	private function __clone()
	{
		_doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?', 'Artiskool_Plugin_Su'), '1.0.0');
	}
}

Artiskool_Plugin_Su::getInstance();
if (is_admin()) {
	require_once 'classes/artiskool-updater-github.php';
	new Artiskool_Updater_GitHub (__FILE__, 'artiskool', 'wp-plugin-su');
}
