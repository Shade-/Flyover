<?php

/**
 * Upgrading routines
 */

class Flyover_Update
{

	private $version;

	private $old_version;

	private $plugins;

	private $info;

	public function __construct()
	{

		global $mybb, $db, $cache, $lang;

		if (!$lang->flyover) {
			$lang->load("flyover");
		}

		$this->load_version();

		$check = $this->check_update();

		if ($mybb->input['update'] == 'flyover' and $check) {
			$this->update();
		}

	}

	private function load_version()
	{
		global $cache;

		$this->info        = flyover_info();
		$this->plugins     = $cache->read('shade_plugins');
		$this->old_version = $this->plugins[$this->info['name']]['version'];
		$this->version     = $this->info['version'];

	}

	private function check_update()
	{
		global $lang, $mybb;

		if (version_compare($this->old_version, $this->version, "<")) {

			if ($mybb->input['update']) {
				return true;
			} else {
				flash_message($lang->flyover_error_needtoupdate, "error");
			}

		}

		return false;

	}

	private function update()
	{
		global $db, $mybb, $cache, $lang, $PL;

		if (!$lang->flyover) {
			$lang->load('flyover');
		}

		$new_settings = $drop_settings = [];
		$updateTemplates = 0;

		// Get the gid
		$query = $db->simple_select("settinggroups", "gid", "name='flyover'");
		$gid   = (int) $db->fetch_field($query, "gid");

		// 1.2
		if (version_compare($this->old_version, '1.2', "<")) {

			$new_settings[] = [
				"name" => "flyover_email_pw_less",
				"title" => $db->escape_string($lang->setting_flyover_email_pw_less),
				"description" => $db->escape_string($lang->setting_flyover_email_pw_less_desc),
				"optionscode" => "yesno",
				"value" => 0,
				"disporder" => 8,
				"gid" => $gid
			];

			$updateTemplates = 1;

		}

		// 1.3
		if (version_compare($this->old_version, '1.3', "<")) {

			$i = 100;

			$userFields = flyover_get_active_userfield_list(true);

			foreach ($userFields as $field) {

				$tempKey = $field . 'field';

				if ($db->num_rows($db->simple_select('settings', 'gid', "name = 'flyover_{$tempKey}'"), 'gid') != 0) {
					continue;
				}

				$tempTitle = 'setting_flyover_' . $tempKey;
				$tempDesc = $tempTitle . '_desc';

				$new_settings[] = [
					"name" => 'flyover_' . $tempKey,
					"title" => $db->escape_string($lang->$tempTitle),
					"description" => $db->escape_string($lang->$tempDesc),
					'optionscode' => 'text',
					"value" => '',
					"disporder" => $i,
					"gid" => $gid
				];

				$i++;

			}

			$updateTemplates = 1;

		}

		// 1.4.1
		if (version_compare($this->old_version, '1.4.1', "<")) {

			$db->modify_column('flyover_settings', 'provider', 'TEXT');
			$db->modify_column('flyover_settings', 'enabled', "TINYINT(1) NOT NULL DEFAULT '1'");
			$db->modify_column('flyover_settings', 'id', "VARCHAR(255) NOT NULL DEFAULT ''");
			$db->modify_column('flyover_settings', 'secret', "VARCHAR(255) NOT NULL DEFAULT ''");
			$db->modify_column('flyover_settings', 'key_token', "VARCHAR(255) NOT NULL DEFAULT ''");
			$db->modify_column('flyover_settings', 'usergroup', "TINYINT(5) NOT NULL DEFAULT '2'");
			$db->modify_column('flyover_settings_data', 'usernames', 'TEXT');
			$db->modify_column('flyover_reports', 'file', 'TEXT');
			$db->modify_column('flyover_reports', 'message', 'TEXT');
			$db->modify_column('flyover_reports', 'trace', 'TEXT');
			$db->modify_column('flyover_reports', 'dateline', "VARCHAR(15) NOT NULL DEFAULT ''");
			$db->modify_column('flyover_reports', 'code', "VARCHAR(10) NOT NULL DEFAULT ''");
			$db->modify_column('flyover_reports', 'line', "INT(6) NOT NULL DEFAULT '0'");

			$folder = MYBB_ROOT . 'inc/plugins/Flyover/hybridauth/Hybrid/Providers';

			if (is_dir($folder)) {

				$files = (array) scandir($folder);

				// These will return . and .., unset them
				unset($files[0], $files[1]);

				foreach ($files as $key => $file) {

					$check = str_replace('.php', '', $file);

					if ($db->field_exists($check, 'flyover_settings_data')) {
						$db->modify_column('flyover_settings_data', $check, "VARCHAR(255) NOT NULL DEFAULT ''");
					}

					if ($db->field_exists($check . '_settings', 'flyover_settings_data')) {
						$db->modify_column('flyover_settings_data', $check . '_settings', 'TEXT');
					}

				}

			}

		}

		if ($new_settings) {
			$db->insert_query_multiple('settings', $new_settings);
		}

		if ($drop_settings) {
			$db->delete_query('settings', "name IN ('flyover_". implode("','flyover_", $drop_settings) ."')");
		}

		rebuild_settings();

		if ($updateTemplates) {

			$PL or require_once PLUGINLIBRARY;

			// Update templates	   
			$dir       = new DirectoryIterator(dirname(__FILE__) . '/templates');
			$templates = [];
			foreach ($dir as $file) {
				if (!$file->isDot() and !$file->isDir() and pathinfo($file->getFilename(), PATHINFO_EXTENSION) == 'html') {
					$templates[$file->getBasename('.html')] = file_get_contents($file->getPathName());
				}
			}

			$PL->templates('flyover', 'Flyover', $templates);

		}

		// Update the current version number and redirect
		$this->plugins[$this->info['name']] = [
			'title' => $this->info['name'],
			'version' => $this->version
		];

		$cache->update('shade_plugins', $this->plugins);

		flash_message($lang->sprintf($lang->flyover_success_updated, $this->old_version, $this->version), "success");
		admin_redirect('index.php');

	}

}

// Direct init on call
$FlyoverUpdate = new Flyover_Update();