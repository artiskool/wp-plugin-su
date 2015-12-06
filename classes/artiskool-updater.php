<?php
/**
 * Plugin updater main class
 * Credits goes to http://code.tutsplus.com/tutorials/distributing-your-plugins-in-github-with-automatic-updates--wp-34817
 *
 * @author: Art Layese - http://artiskool.com
 */

abstract class Artiskool_Updater
{
 
	protected $slug; // plugin slug
	protected $pluginData; // plugin data
	protected $username; // repo username
	protected $repo; // repo name
	protected $pluginFile; // __FILE__ of our plugin
	protected $accessToken; // private repo token
 
	// Get information regarding our plugin from repo
	abstract protected function getRepoReleaseInfo();
 
	// Push in plugin version information to get the update notification
	abstract public function setTransient($transient);
 
	// Push in plugin version information to display in the details lightbox
	abstract public function setPluginInfo($false, $action, $response);
 
	public function __construct($pluginFile, $username, $repo, $accessToken = '')
	{
		add_filter('pre_set_site_transient_update_plugins', array($this, 'setTransient'));
		add_filter('plugins_api', array($this, 'setPluginInfo'), 10, 3);
		add_filter('upgrader_post_install', array($this, 'postInstall'), 10, 3);
 
		$this->pluginFile = $pluginFile;
		$this->username = $username;
		$this->repo = $repo;
		$this->accessToken = $accessToken;
	}
 
	// Get information regarding our plugin from WordPress
	protected function initPluginData()
	{
		// code here
		$this->slug = plugin_basename($this->pluginFile);
		$this->pluginData = get_plugin_data($this->pluginFile);
	}
 
	// Perform additional actions to successfully install our plugin
	public function postInstall($true, $hook_extra, $result)
	{
		// Get plugin information
		$this->initPluginData();

		// Remember if our plugin was previously activated
		$wasActivated = is_plugin_active($this->slug);

		// Since we are hosted in GitHub, our plugin folder would have a dirname of
		// reponame-tagname change it to our original one:
		global $wp_filesystem;
		$pluginFolder = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . dirname($this->slug);
		$wp_filesystem->move($result['destination'], $pluginFolder);
		$result['destination'] = $pluginFolder;

		// Re-activate plugin if needed
		if ($wasActivated) {
			$activate = activate_plugin($this->slug);
		}

		return $result;
	}

}
