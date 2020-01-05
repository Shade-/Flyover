<?php

namespace Flyover\Usercp;

use Flyover\Flyover as Main;
use Flyover\Session\Redirect;
use Flyover\Helper;
use Flyover\User\Update;

class Flyover extends Usercp
{

	public function __construct(
		$errors = ''
	)
	{
		$this->traitConstruct();

		$flyover = new Main();

		global $theme, $templates, $headerinclude, $header, $footer, $usercpnav;

		add_breadcrumb($this->lang->nav_usercp, 'usercp.php');
		add_breadcrumb($this->lang->flyover_page_title, 'usercp.php?action=flyover');

		$activeProviders = $this->cache->read('settings', 'enabled');

		if (!$activeProviders) {

			header('Location: usercp.php');
			exit;

		}

		ksort($activeProviders);

		// Get the connected providers				
		$query = $this->db->simple_select(
			'flyover_users',
			'*',
			'uid = ' . (int) $this->mybb->user['uid'],
			['limit' => 1]
		);
		$connectedProviders = (array) $this->db->fetch_array($query);

		// Update settings
		if ($this->mybb->request_method == 'post') {

			verify_post_check($this->mybb->input['my_post_key']);

			$newSettings = [];

			$selectedSettings = (array) $this->mybb->input['providers'];

			// Loop through the connected providers
			foreach (array_keys($activeProviders) as $provider) {

				// Skip if not connected
				if (!$connectedProviders[$provider]) {
					continue;
				}

				$tempKey = $provider . '_settings';

				foreach (Helper\Utilities::getUserfields() as $setting) {

					$newSettings[$tempKey][$setting] = 0;

					if ($selectedSettings[$provider][$setting] == 1) {
						$newSettings[$tempKey][$setting] = 1;
					}

				}

			}

			$update = new Update($this->mybb->user);

			$update->settings($newSettings);
			$update->finalize();

			$redirect = new Redirect();

			$redirect->set(['callback' => 'usercp.php?action=flyover']);

			$redirect->show(
				$this->lang->flyover_success_settings_updated_title,
				$this->lang->flyover_success_settings_updated
			);

		}

		// Errors
		if ($errors) {
			$errors = inline_error($errors);
		}

		// Show main content
		$options = '';

		// Header
		if ($connectedProviders) {
			eval("\$optionsHeader = \"" . $templates->get("flyover_usercp_settings_header") . "\";");
		}

		$usernames = (array) my_unserialize($connectedProviders['usernames']);
		$providersToLink = [];

		// List connected providers
		foreach ($activeProviders as $provider => $configuration) {

			// Add to another array if not connected
			if (!$connectedProviders[$provider]) {

				$providersToLink[$provider] = $configuration;
				continue;

			}

			$altbg = alt_trow();

			$displayPermissions = (array) $configuration['settings'];

			$lowercaseProvider = strtolower($provider);

			// Build the "Connected with" text
			$connectedWith = '';
			if ($usernames[$provider] and $connectedProviders[$provider]) {

				$connectedWith = $this->lang->sprintf(
					$this->lang->flyover_settings_connected_with,
					$usernames[$provider]
				);

			}
			else if (!$usernames[$provider] and $connectedProviders[$provider]) {

				$connectedWith = $this->lang->sprintf(
					$this->lang->flyover_settings_could_not_fetch,
					$provider
				);

			}

			// Build settings
			$settings = '';
			$userSettings = (array) my_unserialize($connectedProviders[$provider . '_settings']);

			foreach (Helper\Utilities::getUserfields() as $setting) {

				if (!$displayPermissions[$setting] or (!$this->mybb->settings['flyover_' . $setting . 'field'] and !in_array($setting, ['avatar', 'website']))) {
					continue;
				}

				$checked = ($userSettings[$setting]) ? ' checked' : '';

				// Set up this setting label
				$tempKey = 'flyover_settings_' . $setting;
				$label = $this->lang->$tempKey;

				eval("\$settings .= \"" . $templates->get("flyover_usercp_settings_provider_setting") . "\";");

			}

			eval("\$options .= \"" . $templates->get("flyover_usercp_settings_provider") . "\";");

		}

		// List not connected providers
		if ($providersToLink) {

			$class = $button = '';

			foreach ($providersToLink as $provider => $configuration) {

				$lowercaseProvider = strtolower($provider);

				eval("\$availableProviders .= \"" . $templates->get("flyover_usercp_settings_button") . "\";");

			}

			// Footer
			eval("\$optionsFooter = \"" . $templates->get("flyover_usercp_settings_footer") . "\";");

		}

		eval("\$content = \"" . $templates->get('flyover_usercp_settings') . "\";");

		output_page($content);

	}

}