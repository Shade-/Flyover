<?php

if (!defined("IN_MYBB")) {
	header("HTTP/1.0 404 Not Found");
	exit;
}

define(MODULE, "flyover");
define(MAINURL, "index.php?module=config-flyover");

$lang->load(MODULE);

$gid = flyover_settings_gid();

$query = $db->simple_select('flyover_reports');
$number_of_reports = $db->num_rows($query);

$sub_tabs['general']  = [
	'title' => $lang->flyover_general,
	'link' => MAINURL,
	'description' => $lang->flyover_general_desc
];
$sub_tabs['settings'] = [
	'title' => $lang->flyover_settings,
	'link' => "index.php?module=config-settings&action=change&gid={$gid}"
];
$sub_tabs['reports'] = [
	'title' => $lang->flyover_reports . ' (' . $number_of_reports . ')',
	'link' => MAINURL . '&action=reports',
	'description' => $lang->flyover_reports_desc
];
$sub_tabs['cache'] = [
	'title' => $lang->flyover_cache,
	'link' => MAINURL . '&action=rebuild_cache',
];
$sub_tabs['export'] = [
	'title' => $lang->flyover_export,
	'link' => MAINURL . '&action=export',
];
$sub_tabs['import'] = [
	'title' => $lang->flyover_import,
	'link' => MAINURL . '&action=import',
];
$sub_tabs['migration'] = [
	'title' => $lang->flyover_migration,
	'link' => MAINURL . '&action=migration',
	'description' => $lang->flyover_migration_desc
];
$sub_tabs['documentation'] = [
	'title' => $lang->flyover_documentation,
	'link' => MAINURL . '&action=documentation',
	'description' => $lang->flyover_documentation_desc
];

$allowed_providers = [
	'AOL' => [''],
	'BeatsMusic' => ['id','secret'],
	'BitBucket' => ['id','secret'],
	'Deezer' => ['id','secret'],
	'Discord' => ['id','secret'],
	'Disqus' => ['id','secret'],
	'Dribbble' => ['id','secret'],
	'Dropbox' => ['id','secret'],
	'Facebook' => ['id','secret'],
	'Foursquare' => ['id','secret'],
	'GitHub' => ['id','secret'],
	'Google' => ['id','secret'],
	'Instagram' => ['id','secret'],
	'LastFM' => ['key','secret'],
	'LinkedIn' => ['id','secret'],
	'Microsoft' => ['id','secret'],
	'PayPal' => ['id','secret'],
	'Pinterest' => ['id','secret'],
	'Reddit' => ['id','secret'],
	'SoundCloud' => ['id','secret'],
	'StackExchange' => ['id','secret','key'],
	'Steam' => [''],
	'Tumblr' => ['key','secret'],
	'TwitchTV' => ['id','secret'],
	'Twitter' => ['key','secret'],
	'Vimeo' => ['key','secret'],
	'Vkontakte' => ['id','secret'],
	'WordPress' => ['id','secret'],
	'Yahoo' => ['key','secret'],
	'500px' => ['key','secret']
];

global $inline_style;

require_once FLYOVER;
$Flyover = new Flyover();

/**
 * Remove a provider
 */
if ($mybb->input['action'] == 'remove_provider') {

	$provider = $mybb->input['provider'];

	// Get settings
	$flyover_settings = $Flyover->readCache('settings', $provider);

	$options = [
		'enabled' => 0,
		'provider' => $provider,
		'id' => '',
		'key_token' => '',
		'secret' => '',
		'usergroup' => '',
		'settings' => []
	];

	// Drop columns
	if ($db->field_exists($provider, 'flyover_settings_data')) {
		$db->drop_column('flyover_settings_data', $provider);
	}

	if ($db->field_exists($provider . '_settings', 'flyover_settings_data')) {
		$db->drop_column('flyover_settings_data', $provider . '_settings');
	}

	// Save
	$Flyover->updateCache('settings', $provider, $options);

	// Redirect
	flash_message($lang->sprintf($lang->flyover_setup_complete_inactive, $provider), 'success');
	admin_redirect(MAINURL);

}

/**
 * Rebuild cache
 */
else if ($mybb->input['action'] == 'rebuild_cache') {

	if ($mybb->request_method == 'post') {

		if ($mybb->input['no']) {
			admin_redirect(MAINURL);
		}

		$folder = MYBB_ROOT . 'inc/plugins/Flyover/hybridauth/Hybrid/Providers';

		// Rebuild cache – 1st attempt
		$Flyover->rebuildCache('settings');

		$flyover_settings = $Flyover->readCache('settings');

		// Fix for providers that don't exist anymore
		if (is_dir($folder)) {

			$files = (array) scandir($folder);

			// These will return . and .., unset them
			unset($files[0], $files[1]);

			foreach ($flyover_settings as $file => $arr) {

				$check = $file . '.php';

				if (!in_array($check, $files)) {

					// User ID
					if ($db->field_exists($file, 'flyover_settings_data')) {
						$db->drop_column('flyover_settings_data', $file);
					}

					// User settings
					if ($db->field_exists($file . '_settings', 'flyover_settings_data')) {
						$db->drop_column('flyover_settings_data', $file . '_settings');
					}

					// Internal settings
					$db->delete_query('flyover_settings', "provider = '" . $db->escape_string($file) . "'");

				}

			}

			foreach ($files as $key => $file) {

				$check = str_replace('.php', '', $file);

				if ($flyover_settings[$check]['enabled']) {

					// User ID
					if (!$db->field_exists($check, 'flyover_settings_data')) {
						$db->add_column('flyover_settings_data', $check, "VARCHAR(255) NOT NULL DEFAULT ''");
					}

					// User settings
					if (!$db->field_exists($check . '_settings', 'flyover_settings_data')) {
						$db->add_column('flyover_settings_data', $check . '_settings', "TEXT");
					}

				}

			}

		}

		// Rebuild cache - 2nd attempt
		$Flyover->rebuildCache('settings');

		flash_message($lang->flyover_cache_rebuilt, 'success');
		admin_redirect(MAINURL);

	}

	$page->add_breadcrumb_item($lang->flyover, MAINURL);
	$page->add_breadcrumb_item($lang->flyover_cache, MAINURL . '&action=rebuild_cache');

	$page->output_confirm_action(MAINURL . '&action=rebuild_cache', $lang->flyover_cache_desc, $lang->flyover_cache);

}
/**
 * Configure a provider
 */
else if ($mybb->input['action'] == 'add_provider') {

	$sub_tabs['add'] = [
		'title' => $lang->flyover_add,
		'link' => MAINURL,
		'description' => $lang->flyover_add_desc
	];

	$provider = $mybb->input['provider'];

	// Https check if using Dropbox
	if ($provider == 'Dropbox' and !verify_https()) {
		flash_message($lang->flyover_error_not_on_https, 'error');
		admin_redirect(MAINURL);
	}

	// Param check
	if (!$allowed_providers[$provider]) {
		flash_message($lang->flyover_error_not_ready, 'error');
		admin_redirect(MAINURL);
	}

	// Get settings
	$flyover_settings = $Flyover->readCache('settings', $provider);

	// Save the incoming settings
	if ($mybb->request_method == 'post') {

		$options = [
			'enabled' => (int) $mybb->input['enabled'],
			'provider' => $provider,
			'id' => $mybb->input['_id'],
			'key_token' => $mybb->input['key'],
			'secret' => $mybb->input['secret'],
			'usergroup' => (int) $mybb->input['usergroup']
		];

		foreach ($Flyover->getActiveUserfieldList() as $field) {
			$options['settings'][$field] = (int) $mybb->input[$field];
		}

		// Save
		$Flyover->updateCache('settings', $provider, $options);

		// Create/delete column
		if ((int) $mybb->input['enabled']) {

			// User ID
			if (!$db->field_exists($provider, 'flyover_settings_data')) {
				$db->add_column('flyover_settings_data', $provider, "VARCHAR(255) NOT NULL DEFAULT ''");
			}

			// User settings
			if (!$db->field_exists($provider . '_settings', 'flyover_settings_data')) {
				$db->add_column('flyover_settings_data', $provider . '_settings', "TEXT");
			}

		}
		else {

			// User ID
			if ($db->field_exists($provider, 'flyover_settings_data')) {
				$db->drop_column('flyover_settings_data', $provider);
			}

			// User settings
			if ($db->field_exists($provider . '_settings', 'flyover_settings_data')) {
				$db->drop_column('flyover_settings_data', $provider . '_settings');
			}

		}

		// Redirect
		$message = $lang->sprintf($lang->flyover_setup_complete_inactive, $provider);
		if ($mybb->input['enabled']) {
			$message = $lang->sprintf($lang->flyover_setup_complete_active, $provider);
		}

		flash_message($message, 'success');
		admin_redirect(MAINURL);

	}

	$page->add_breadcrumb_item($lang->flyover_add, MAINURL . '&action=add');

	$page->output_header($lang->flyover);

	$page->output_nav_tabs($sub_tabs, 'add');

	// Form wrapper
	$form = new Form(MAINURL . "&action=add_provider", "post", "add_provider");

	// Hidden provider
	echo $form->generate_hidden_field("provider", $provider);

	if ($errors) {
		$page->output_inline_error($errors);
	}

	// Question image
	$question_image = " <a href='" . MAINURL . "&action=documentation'><img src='../images/icons/question.png' alt='What is this?' /></a>";

	// Start container
	$form_container = new FormContainer($provider . " integration setup");

	// Enable/disable
	$form_container->output_row($lang->flyover_setup_enabled, $lang->flyover_setup_enabled_desc, $form->generate_on_off_radio('enabled', $flyover_settings['enabled'], 1, ['class' => 'setting_enabled'], ['class' => 'setting_enabled']));

	// Id
	if (in_array('id', $allowed_providers[$provider])) {

		$form_container->output_row($lang->flyover_setup_id . $question_image, $lang->flyover_setup_id_desc, $form->generate_text_box('_id', $flyover_settings['id'], [
			'id' => 'id'
		]), 'id', [], ['id' => 'row_id']);

		$count++;

	}

	// Key
	if (in_array('key', $allowed_providers[$provider])) {

		$form_container->output_row($lang->flyover_setup_key . $question_image, $lang->flyover_setup_key_desc, $form->generate_text_box('key', $flyover_settings['key_token'], [
			'id' => 'key'
		]), 'key', [], ['id' => 'row_key']);

		$count++;

	}

	// Secret
	if (in_array('secret', $allowed_providers[$provider])) {

		$form_container->output_row($lang->flyover_setup_secret . $question_image, $lang->flyover_setup_secret_desc, $form->generate_text_box('secret', $flyover_settings['secret'], [
			'id' => 'secret'
		]), 'secret', [], ['id' => 'row_secret']);

		$count++;

	}

	// OpenID/Redirect URI notice
	if (!$count) {
		$form_container->output_row($lang->flyover_setup_openid, '', $lang->flyover_setup_openid_desc, 'openid', [], ['id' => 'row_openid']);
	}
	else {
		$form_container->output_row($lang->flyover_setup_redirect_uri . $question_image, $lang->sprintf($lang->flyover_setup_redirect_uri_desc, $provider), $mybb->settings['bburl'] . '/flyover.php?auth=true&hauth.done=' . $provider, 'redirect_uri', [], ['id' => 'row_redirect_uri']);
	}

	// Usergroup
	if (!$flyover_settings['usergroup']) {
		$flyover_settings['usergroup'] = 2;
	}

	$form_container->output_row($lang->flyover_setup_usergroup, $lang->flyover_setup_usergroup_desc, $form->generate_group_select('usergroup', [$flyover_settings['usergroup']], [
		'id' => 'usergroup'
	]), 'usergroup', [], ['id' => 'row_usergroup']);

	// Sync options
	$sync_options_row = '';
	$syncFields = $Flyover->getActiveUserfieldList();

	foreach($syncFields as $field) {

		if (!$mybb->settings['flyover_' . $field . 'field'] and !in_array($field, ['avatar', 'website'])) {
			continue;
		}

		$tempName = 'flyover_setup_' . $field;
		$tempDesc = $tempName . '_desc';

		$sync_options_row .= $form->generate_check_box($field, 1, $lang->$tempName, ['id' => $field, 'class' => $field, 'checked' => (int) $flyover_settings['settings'][$field]]);
		$sync_options_row .= "<div class='description'>{$lang->$tempDesc}</div>";

	}

	$form_container->output_row($lang->flyover_setup_sync_options . $question_image, '', $sync_options_row, 'sync_options', [], ['id' => 'row_sync_options']);

	// End container
	$form_container->end();

	flyover_load_peekers();

	// Close form wrapper
	$buttons[] = $form->generate_submit_button($lang->flyover_setup_save);
	$form->output_submit_wrapper($buttons);
	$form->end();

}
else if ($mybb->input['action'] == 'reports') {

	// Download report
	if ($mybb->input['export_id']) {

		$plugin_info = flyover_info();

		$xml = "<?xml version=\"1.0\" encoding=\"{$lang->settings['charset']}\"?".">\r\n";
		$xml .= "<report name=\"".$plugin_info['name']."\" version=\"".$plugin_info['version']."\">\r\n";

		$query = $db->simple_select('flyover_reports', '*', 'id = ' . (int) $mybb->input['export_id']);
		while ($report = $db->fetch_array($query)) {

			foreach ($report as $k => $v) {

				$xml .= "\t\t<{$k}>{$v}</{$k}>\r\n";

			}

		}
		$xml .= "</report>";

		header("Content-disposition: attachment; filename=" . $plugin_info['name'] . "-report-" . $mybb->input['export_id'] . ".xml");
		header("Content-type: application/octet-stream");
		header("Content-Length: ".strlen($xml));
		header("Pragma: no-cache");
		header("Expires: 0");
		echo $xml;

		exit;
	}

	// Delete reports
	if ($mybb->input['delete_report']) {

		switch ($mybb->input['delete_report']) {
			case 'all':
				$db->delete_query('flyover_reports');
				break;
			default:
				$db->delete_query('flyover_reports', 'id = ' . (int) $mybb->input['delete_report']);
		}

		flash_message($lang->flyover_success_deleted_reports, 'success');
		admin_redirect(MAINURL . '&action=reports');

	}

	$page->add_breadcrumb_item($lang->flyover_reports, MAINURL . '&action=reports');

	$page->output_header($lang->flyover_reports);

	$page->output_nav_tabs($sub_tabs, 'reports');

	$reports = [];
	$query = $db->simple_select('flyover_reports');
	while ($report = $db->fetch_array($query)) {
		$reports[] = $report;
	}

	if ($reports) {

		$table = new Table;
		$table->construct_header($lang->flyover_reports_date, [
			'width' => '15%'
		]);
		$table->construct_header($lang->flyover_reports_code, [
			'width' => '5%'
		]);
		$table->construct_header($lang->flyover_reports_file);
		$table->construct_header($lang->flyover_reports_line, [
			'width' => '5%'
		]);
		$table->construct_header($lang->options, [
			'width' => '10%',
			'style' => 'text-align: center'
		]);

		foreach ($reports as $report) {

			foreach ($report as $k => $val) {

				if (in_array($k, ['id', 'message', 'trace'])) {
					continue;
				}

				if ($k == 'dateline') {
					$val = my_date($mybb->settings['dateformat'], $val) . ', ' . my_date($mybb->settings['timeformat'], $val);
				}

				$table->construct_cell($val);

			}

			$popup = new PopupMenu("item_{$report['id']}", $lang->options);
			$popup->add_item($lang->flyover_reports_download, MAINURL . '&action=reports&export_id=' . $report['id']);
			$popup->add_item($lang->flyover_reports_delete, MAINURL . '&action=reports&delete_report=' . $report['id']);

			$table->construct_cell($popup->fetch(), [
				'class' => 'align_center'
			]);

			$table->construct_row();

		}

		$table->construct_cell('<a href="' . MAINURL . '&action=reports&delete_report=all" class="button">' . $lang->flyover_reports_delete_all . '</a>', [
			'colspan' => 5,
			'class' => 'align_center'
		]);
		$table->construct_row();

		$table->output($lang->flyover_reports);

	}
	else {
		echo "<p class=\"notice\">{$lang->flyover_error_no_reports}</p>";
	}
}
/**
 * Export the current settings as an XML file
 */
else if ($mybb->input['action'] == 'export') {

	$xml = "<?xml version=\"1.0\" encoding=\"{$lang->settings['charset']}\"?".">\r\n";
	$xml .= "<settings name=\"Flyover\" version=\"".$mybb->version_code."\">\r\n";
	$xml .= "\t<providers>\r\n";

	$flyover_settings = $Flyover->readCache('settings');

	foreach ($flyover_settings as $key => $value) {

		$xml .= "\t\t<provider name=\"{$key}\">\r\n";

		foreach ($value as $k => $v) {

			if ($k == 'sid') {
				continue;
			}

			if (is_array($v)) {
				$content = serialize($v);
			}
			else {
				$content = htmlspecialchars_uni($v);
			}

			$xml .= "\t\t\t<setting name=\"{$k}\">{$content}</setting>\r\n";

		}

		$xml .= "\t\t</provider>\r\n";

	}

	$xml .= "\t</providers>\r\n";
	$xml .= "</settings>";

	header("Content-disposition: attachment; filename=flyover-settings.xml");
	header("Content-type: application/octet-stream");
	header("Content-Length: ".strlen($xml));
	header("Pragma: no-cache");
	header("Expires: 0");
	echo $xml;

	exit;

}
/**
 * Import an XML file as settings
 */
else if ($mybb->input['action'] == 'import') {

	if ($mybb->request_method == "post") {

		if (!$_FILES['local_file'] and !$mybb->input['url']) {
			$errors[] = $lang->error_missing_url;
		}

		if (!$errors) {
			// Find out if there was an uploaded file
			if ($_FILES['local_file']['error'] != 4) {
				// Find out if there was an error with the uploaded file
				if ($_FILES['local_file']['error'] != 0) {

					$errors[] = $lang->error_uploadfailed.$lang->error_uploadfailed_detail;
					switch ($_FILES['local_file']['error']) {
						case 1: // UPLOAD_ERR_INI_SIZE
							$errors[] = $lang->error_uploadfailed_php1;
							break;
						case 2: // UPLOAD_ERR_FORM_SIZE
							$errors[] = $lang->error_uploadfailed_php2;
							break;
						case 3: // UPLOAD_ERR_PARTIAL
							$errors[] = $lang->error_uploadfailed_php3;
							break;
						case 6: // UPLOAD_ERR_NO_TMP_DIR
							$errors[] = $lang->error_uploadfailed_php6;
							break;
						case 7: // UPLOAD_ERR_CANT_WRITE
							$errors[] = $lang->error_uploadfailed_php7;
							break;
						default:
							$errors[] = $lang->sprintf($lang->error_uploadfailed_phpx, $_FILES['local_file']['error']);
							break;
					}
				}

				if (!$errors) {
					// Was the temporary file found?
					if (!is_uploaded_file($_FILES['local_file']['tmp_name'])) {
						$errors[] = $lang->error_uploadfailed_lost;
					}
					// Get the contents
					$contents = @file_get_contents($_FILES['local_file']['tmp_name']);
					// Delete the temporary file if possible
					@unlink($_FILES['local_file']['tmp_name']);
					// Are there contents?
					if (!trim($contents)) {
						$errors[] = $lang->error_uploadfailed_nocontents;
					}
				}
			}
			else {
				// UPLOAD_ERR_NO_FILE
				$errors[] = $lang->error_uploadfailed_php4;
			}

			if (!$errors) {

				require_once  MYBB_ROOT."inc/class_xml.php";

				$parser = new XMLParser($contents);

				$tree = $parser->get_tree();

				if ($tree['settings']['attributes']['name'] != 'Flyover') {
					$errors[] = $lang->flyover_error_invalid_settings_file;
				}

				if (!$errors) {

					foreach ($tree['settings']['providers']['provider'] as $provider) {

						$options = [];

						foreach ($provider['setting'] as $val) {

							$options[$val['attributes']['name']] = trim($val['value']);

						}

						$options['settings'] = my_unserialize($options['settings']);

						// Rebuild internal settings
						$Flyover->updateCache('settings', $provider['attributes']['name'], $options);

						// Rebuild users table
						if ($options['enabled']) {

							// User ID
							if (!$db->field_exists($provider['attributes']['name'], 'flyover_settings_data')) {
								$db->add_column('flyover_settings_data', $provider['attributes']['name'], "VARCHAR(255) NOT NULL DEFAULT ''");
							}

							// User settings
							if (!$db->field_exists($provider['attributes']['name'] . '_settings', 'flyover_settings_data')) {
								$db->add_column('flyover_settings_data', $provider['attributes']['name'] . '_settings', "TEXT");
							}

						}

					}	

					flash_message($lang->flyover_import_successful, 'success');
					admin_redirect(MAINURL);

				}

			}
		}
	}

	$page->add_breadcrumb_item($lang->flyover_import, MAINURL . '&action=import');

	$page->output_header($lang->flyover);

	$page->output_nav_tabs($sub_tabs, 'import');

	if ($errors) {
		$page->output_inline_error($errors);
	}

	$form = new Form(MAINURL . "&action=import", "post", "", 1);

	$form_container = new FormContainer($lang->flyover_import_settings);
	$form_container->output_row($lang->flyover_import_from, $lang->flyover_import_from_desc, $form->generate_file_upload_box("local_file", ['style' => 'width: 300px;']), 'file');
	$form_container->end();

	$buttons[] = $form->generate_submit_button($lang->flyover_import_button);

	$form->output_submit_wrapper($buttons);

	$form->end();

	$page->output_footer();

}
/**
 * Documentation
 */
else if ($mybb->input['action'] == 'documentation') {

	$page->add_breadcrumb_item($lang->flyover_documentation, MAINURL . '&action=documentation');

	$page->output_header($lang->flyover_documentation);

	$page->output_nav_tabs($sub_tabs, 'documentation');

	$table = new Table;
	$table->construct_header($lang->flyover_documentation_title, [
		'style' => 'text-align: center'
	]);
	$table->construct_header($lang->flyover_documentation_description, [
		'style' => 'text-align: center'
	]);

	$docs = ['general', 'tokens', 'sync'];

	$prefix = 'flyover_documentation_';
	$suffix = '_desc';
	foreach ($docs as $doc) {
		$temp_title = $prefix . $doc;
		$temp_desc = $prefix . $doc . $suffix;
		$table->construct_cell($lang->$temp_title);
		$table->construct_cell($lang->$temp_desc);
		$table->construct_row();
	}

	$table->output($lang->flyover_documentation);

}
/**
 * Migration
 */
else if ($mybb->input['action'] == 'migration') {

	$allowed_plugins = [
		'myfbconnect.php' => [
			'name' => 'Facebook',
			'field' => 'myfb_uid'
		],
		'mytwconnect.php' => [
			'name' => 'Twitter',
			'field' => 'mytw_uid'
		],
		'steamlogin.php' => [
			'name' => 'Steam',
			'field' => 'loginname'
		]
	];

	$fieldExists = false;

	if ($mybb->input['migrate']) {

		$codename = (string) $mybb->input['migrate'];
		$migration_info = $allowed_plugins[$codename];

		$fieldExists = $db->field_exists($migration_info['name'], 'flyover_settings_data');

		if ($migration_info and $fieldExists) {

			$perpage = 100;
			$start = (isset($mybb->input['start'])) ? (int) $mybb->input['start'] : 0;

			// Set this provider's name
			$Flyover->provider = $migration_info['name'];

			// Load the core API
			$Flyover->load();

			$counter = $start;

			// Loop through this provider's linked users
			$query = $db->simple_select('users', "uid, usergroup, additionalgroups, {$migration_info['field']}", "{$migration_info['field']} <> ''", ['limit_start' => $start, 'limit' => $perpage]);
			while ($user = $db->fetch_array($query)) {
				$counter++;
				$Flyover->linkUser($user, $user[$migration_info['field']]);
			}

			$numusers = $db->num_rows($query);

			// Display next page notice
			if ($numusers == $perpage) {

				$page->add_breadcrumb_item($lang->flyover_migration, MAINURL . '&action=migration');

				$page->output_header($lang->flyover_migration);

				$page->output_nav_tabs($sub_tabs, 'migration');

				$table = new Table;
				$table->construct_cell($lang->sprintf($lang->flyover_migration_nextpage_notice, $start + $perpage, $codename, $counter, $perpage));
				$table->construct_row();
				$table->output($lang->flyover_migration_nextpage);

			}
			else {

				require_once MYBB_ROOT . "inc/plugins/" . $codename;
				$infofunc = str_replace('.php', '', $codename) . '_info';
				$info = $infofunc();

				flash_message($lang->sprintf($lang->flyover_migration_successful, $counter, $info['name']), 'success');
				admin_redirect(MAINURL);

			}

		}

	}
	else {

		$page->add_breadcrumb_item($lang->flyover_migration, MAINURL . '&action=migration');

		$page->output_header($lang->flyover_migration);

		$page->output_nav_tabs($sub_tabs, 'migration');

		$table = new Table;
		$table->construct_header($lang->flyover_migration_plugin);
		$table->construct_header($lang->flyover_migration_status, [
			'width' => '25%'
		]);

		$dir = @opendir(MYBB_ROOT."inc/plugins/");
		if ($dir) {

			while ($file = readdir($dir)) {

				$ext = get_extension($file);

				if ($ext == "php" and in_array($file, array_keys($allowed_plugins))) {
					$plugins_list[] = $file;
				}

			}

			@sort($plugins_list);

		}

		@closedir($dir);

		$codenames = $cache->read('plugins');
		$active_plugins = $codenames['active'];

		$active = false;

		if ($plugins_list) {

			foreach ($plugins_list as $plugin) {

				require_once MYBB_ROOT . "inc/plugins/" . $plugin;
				$codename = str_replace('.php', '', $plugin);

				if (!$active_plugins[$codename]) {
					continue;
				}

				$active = true;

				$infofunc = $codename . '_info';

				if (!function_exists($infofunc)) {
					continue;
				}

				$info = $infofunc();

				// Get number of users to process
				$query = $db->simple_select('users', 'uid', $allowed_plugins[$plugin]['field'] . " <> ''");
				$numUsers = $db->num_rows($query);

				$migrationLink = ($fieldExists) ? '<a href="' . MAINURL . '&amp;action=migration&amp;migrate=' . $plugin . '">' . $lang->flyover_migration_migrate . '</a>' : $lang->sprintf($lang->flyover_migration_configure_provider, $allowed_plugins[$plugin]['name']);

				$table->construct_cell($info['name'] . ' (' . $numUsers . ' users)');
				$table->construct_cell($migrationLink);
				$table->construct_row();

			}

		}

		if (!$active) {

			$table->construct_cell($lang->flyover_migration_no_plugins_available, [
				'colspan' => 2
			]);
			$table->construct_row();

		}

		$table->output($lang->flyover_migration);

	}

}
/**
 * Show the currently active/unactive providers and controls to activate/deactivate them
 */
else if (!$mybb->input['action']) {

	$page->add_breadcrumb_item($lang->flyover, MAINURL);

	$page->extra_header .= PHP_EOL . '<link rel="stylesheet" href="https://d1azc1qln24ryf.cloudfront.net/114779/Socicon/style-cf.css?c2sn1i">';
	$page->extra_header .= PHP_EOL . '<style type="text/css">.login_box *:before {font-family: inherit}</style>';

	$page->output_header($lang->flyover);

	$page->output_nav_tabs($sub_tabs, 'general');

	$providers = [];

	$folder = MYBB_ROOT . 'inc/plugins/Flyover/hybridauth/Hybrid/Providers';

	$flyover_settings = $Flyover->readCache('settings');

	$provider_list = '<div class="login_box">';

	// Get the available providers
	if (is_dir($folder)) {

		$files = scandir($folder);

		// These will return . and .., unset them
		unset($files[0], $files[1]);

		foreach ($files as $file) {

			if ($file == '.DS_Store') continue;

			$file = str_replace('.php', '', $file);
			$lfile = strtolower($file);

			$not_active = (!$flyover_settings[$file]['enabled']) ? 'inactive ' : '';

			$provider_list .= '<a class="provider_btn ' . $not_active . $lfile . '" href="' . MAINURL . '&action=add_provider&provider=' . $file . '"><i class="socicon-' . $lfile . '"></i> ' . $file . '</a>';

			$providers[$file] = $file;

		}

	}

	$images .= '</div>';

	$form = new Form('index.php', "get");
	echo $form->generate_hidden_field('module', 'config-flyover');
	echo $form->generate_hidden_field('action', 'add_provider');

	$table = new Table;

	$table->construct_header($lang->flyover_overview);

	$table->construct_cell($lang->flyover_homepage . $provider_list . '</div>', [
		'style' => 'vertical-align: top'
	]);
	$table->construct_row();

	$table->output($lang->flyover_providers);

	$form->end();

}

echo $inline_style;

$page->output_footer();

function flyover_load_peekers()
{
	global $mybb;

	if ($mybb->version_code > 1700) {
		echo '<script type="text/javascript" src="./jscripts/peeker.js"></script>
<script type="text/javascript">
	$(document).ready(function() {
		new Peeker($(".setting_enabled"), $("#row_id"), 1, true);
		new Peeker($(".setting_enabled"), $("#row_secret"), 1, true);
		new Peeker($(".setting_enabled"), $("#row_key"), 1, true);
		new Peeker($(".setting_enabled"), $("#row_openid"), 1, true);
		new Peeker($(".setting_enabled"), $("#row_usergroup"), 1, true);
		new Peeker($(".setting_enabled"), $("#row_sync_options"), 1, true);
		new Peeker($(".setting_enabled"), $("#row_redirect_uri"), 1, true);
		new Peeker($(".setting_enabled"), $("#row_sync_username"), 1, true);

		add_star("row_id");
		add_star("row_secret");
		add_star("row_key");
	});
</script>';
	}
	else {
		echo '<script type="text/javascript">
Event.observe(window, "load", function() {
	loadPeekers();
	loadStars();
});
function loadPeekers()
{
	new Peeker($$(".setting_enabled"), $("row_id"), 1, true);
	new Peeker($$(".setting_enabled"), $("row_secret"), 1, true);
	new Peeker($$(".setting_enabled"), $("row_key"), 1, true);
	new Peeker($$(".setting_enabled"), $("row_openid"), 1, true);
	new Peeker($$(".setting_enabled"), $("row_usergroup"), 1, true);
	new Peeker($$(".setting_enabled"), $("row_sync_options"), 1, true);
	new Peeker($$(".setting_enabled"), $("row_redirect_uri"), 1, true);
	new Peeker($$(".setting_enabled"), $("row_sync_username"), 1, true);
}
function loadStars()
{
	add_star("row_id");
	add_star("row_secret");
	add_star("row_key");
}
</script>';
	}
}

function verify_https()
{
	return (!empty($_SERVER['HTTPS']) and $_SERVER['HTTPS'] !== 'off') or $_SERVER['SERVER_PORT'] == 443;
}