<?php
/**
 * Plugin updater for github.com
 * Credits goes to http://code.tutsplus.com/tutorials/distributing-your-plugins-in-github-with-automatic-updates--wp-34817
 *
 * @author: Art Layese - http://artiskool.com
 */

require_once 'artiskool-updater.php';

class Artiskool_Updater_GitHub extends Artiskool_Updater
{
 
	// Get information regarding our plugin from WordPress
	// Get information regarding our plugin from GitHub
	protected function getRepoReleaseInfo()
	{
		// Only do this once
		if (!empty($this->githubAPIResult)) {
			return;
		}
		// Query the GitHub API
		$url = "https://api.github.com/repos/{$this->username}/{$this->repo}/releases";

		// We need the access token for private repos
		if (!empty($this->accessToken)) {
			$url = add_query_arg(array('access_token' => $this->accessToken), $url);
		}
 
		// Get the results
		$this->githubAPIResult = wp_remote_retrieve_body(wp_remote_get($url));
		if (!empty($this->githubAPIResult)) {
			$this->githubAPIResult = @json_decode($this->githubAPIResult);
		}

		// Use only the latest release
		if (is_array($this->githubAPIResult)) {
			$this->githubAPIResult = $this->githubAPIResult[0];
		}
	}
 
	// Push in plugin version information to get the update notification
	public function setTransient($transient)
	{
		// If we have checked the plugin data before, don't re-check
		if (empty($transient->checked)) {
			return $transient;
		}

		// Get plugin & GitHub release information
		$this->initPluginData();
		$this->getRepoReleaseInfo();

		// Check the versions if we need to do an update
		$doUpdate = version_compare($this->githubAPIResult->tag_name, $transient->checked[$this->slug]);

		// Update the transient to include our updated plugin data
		if ($doUpdate == 1) {
			$package = $this->githubAPIResult->zipball_url;
 
			// Include the access token for private GitHub repos
			if (!empty($this->accessToken)) {
				$package = add_query_arg(array('access_token' => $this->accessToken), $package);
			}

			$obj = new stdClass();
			$obj->slug = $this->slug;
			$obj->new_version = $this->githubAPIResult->tag_name;
			$obj->url = $this->pluginData['PluginURI'];
			$obj->package = $package;
			$transient->response[$this->slug] = $obj;
		}

		return $transient;
	}
 
	// Push in plugin version information to display in the details lightbox
	public function setPluginInfo($false, $action, $response)
	{
		// Get plugin & GitHub release information
		$this->initPluginData();
		$this->getRepoReleaseInfo();

		// If nothing is found, do nothing
		if (empty($response->slug) || $response->slug != $this->slug) {
			return false;
		}

		// Add our plugin information
		$response->last_updated = $this->githubAPIResult->published_at;
		$response->slug = $this->slug;
		$response->name  = $this->pluginData['Name'];
		//$response->plugin_name  = $this->pluginData['Name'];
		$response->version = $this->githubAPIResult->tag_name;
		$response->author = $this->pluginData['AuthorName'];
		$response->homepage = $this->pluginData['PluginURI'];
 
		// This is our release download zip file
		$downloadLink = $this->githubAPIResult->zipball_url;
 
		// Include the access token for private GitHub repos
		if (!empty($this->accessToken)) {
			$downloadLink = add_query_arg(
				array('access_token' => $this->accessToken),
				$downloadLink
			);
		}
		$response->download_link = $downloadLink;

		// We're going to parse the GitHub markdown release notes, include the parser
		require_once (plugin_dir_path(__FILE__) . 'Parsedown.php');
		// Create tabs in the lightbox
		$response->sections = array(
			'description' => $this->pluginData['Description'],
			'changelog' => class_exists('Parsedown') ? Parsedown::instance()->parse($this->githubAPIResult->body) : $this->githubAPIResult->body
		);

		// Gets the required version of WP if available
		$matches = null;
		preg_match("/requires:\s([\d\.]+)/i", $this->githubAPIResult->body, $matches);
		if (!empty($matches)) {
			if (is_array($matches)) {
				if (count($matches) > 1) {
					$response->requires = $matches[1];
				}
			}
		}
 
		// Gets the tested version of WP if available
		$matches = null;
		preg_match("/tested:\s([\d\.]+)/i", $this->githubAPIResult->body, $matches);
		if (!empty($matches)) {
			if (is_array($matches)) {
				if (count($matches) > 1) {
					$response->tested = $matches[1];
				}
			}
		}

		return $response;
	}
 
}
