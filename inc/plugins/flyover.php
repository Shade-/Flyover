<?php

/**
 * Integrates MyBB with many social networks, featuring login and registration.
 *
 * @package Flyover
 * @author  Shade <legend_k@live.it>
 * @license Copyrighted ©
 * @version 1.5
 */

if (!defined('IN_MYBB')) {
	die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');
}

if (!defined("PLUGINLIBRARY")) {
	define("PLUGINLIBRARY", MYBB_ROOT . "inc/plugins/pluginlibrary.php");
}

define ('FLYOVER', MYBB_ROOT . "inc/plugins/Flyover/class_core.php");

function flyover_info()
{
	return [
		'name' => 'Flyover',
		'description' => "Integrates MyBB with many social networks, featuring login and registration.",
		'website' => 'https://www.mybboost.com',
		'author' => 'Shade',
		'authorsite' => 'https://www.mybboost.com',
		'version' => '1.5',
		'compatibility' => '16*,18*'
	];
}

function flyover_is_installed()
{
	global $cache;

	$info      = flyover_info();
	$installed = $cache->read("shade_plugins");
	if ($installed[$info['name']]) {
		return true;
	}
}

function flyover_install()
{
	global $db, $PL, $lang, $mybb, $cache;

	if (!$lang->flyover) {
		$lang->load('flyover');
	}

	if (!file_exists(PLUGINLIBRARY)) {
		flash_message($lang->flyover_pluginlibrary_missing, "error");
		admin_redirect("index.php?module=config-plugins");
	}

	$PL or require_once PLUGINLIBRARY;

	$settingsToAdd = [
		'enabled' => [
			'title' => $lang->setting_flyover_enable,
			'description' => $lang->setting_flyover_enable_desc,
			'value' => '1'
		],

		// PM delivery
		'passwordpm' => [
			'title' => $lang->setting_flyover_passwordpm,
			'description' => $lang->setting_flyover_passwordpm_desc,
			'value' => '1'
		],
		'passwordpm_subject' => [
			'title' => $lang->setting_flyover_passwordpm_subject,
			'description' => $lang->setting_flyover_passwordpm_subject_desc,
			'optionscode' => 'text',
			'value' => $lang->flyover_default_passwordpm_subject
		],
		'passwordpm_message' => [
			'title' => $lang->setting_flyover_passwordpm_message,
			'description' => $lang->setting_flyover_passwordpm_message_desc,
			'optionscode' => 'textarea',
			'value' => $lang->flyover_default_passwordpm_message
		],
		'passwordpm_fromid' => [
			'title' => $lang->setting_flyover_passwordpm_fromid,
			'description' => $lang->setting_flyover_passwordpm_fromid_desc,
			'optionscode' => 'text',
			'value' => ''
		],

		// Usergroup
		'usergroup' => [
			'title' => $lang->setting_flyover_usergroup,
			'description' => $lang->setting_flyover_usergroup_desc,
			'value' => '2',
			'optionscode' => 'text'
		],

		// Fast registration
		'fastregistration' => [
			'title' => $lang->setting_flyover_fastregistration,
			'description' => $lang->setting_flyover_fastregistration_desc,
			'value' => '1'
		],

		// Email&passwordless
		'email_pw_less' => [
			'title' => $lang->setting_flyover_email_pw_less,
			'description' => $lang->setting_flyover_email_pw_less_desc,
			'value' => '1'
		],

		// Popup mode
		'popup_mode' => [
			'title' => $lang->setting_flyover_popup_mode,
			'description' => $lang->setting_flyover_popup_mode_desc,
			'value' => '0'
		],

		// Login box type
		'login_box_type' => [
			'title' => $lang->setting_flyover_login_box_type,
			'description' => $lang->setting_flyover_login_box_type_desc,
			'value' => '1',
			'optionscode' => "radio \n 1=buttons \n 2=icons \n 3=icons_text"
		]

	];

	// Get custom userfields to add to the db
	$customFields = flyover_get_active_userfield_list(true);
	foreach ($customFields as $field) {

		$tempKey = $field . 'field';
		$tempTitle = 'setting_flyover_' . $tempKey;
		$tempDesc = $tempTitle . '_desc';

		$value = '';

		switch ($field) {

			case 'location':
				$value = '1';
				break;

			case 'bio':
				$value = '2';
				break;

			case 'sex':
				$value = '3';
				break;

		}

		$settingsToAdd[$tempKey] = [
			'title' => $lang->$tempTitle,
			'description' => $lang->$tempDesc,
			'optionscode' => 'text',
			'value' => $value
		];

	}

	$PL->settings('flyover', $lang->setting_group_flyover, $lang->setting_group_flyover_desc, $settingsToAdd);

	// Add templates	   
	$dir       = new DirectoryIterator(dirname(__FILE__) . '/Flyover/templates');
	$templates = [];
	foreach ($dir as $file) {
		if (!$file->isDot() and !$file->isDir() and pathinfo($file->getFilename(), PATHINFO_EXTENSION) == 'html') {
			$templates[$file->getBasename('.html')] = file_get_contents($file->getPathName());
		}
	}

	$PL->templates('flyover', 'Flyover', $templates);

	// Add stylesheet
	$stylesheet = file_get_contents(
		dirname(__FILE__) . '/Flyover/stylesheets/flyover.css'
	);
	$PL->stylesheet('flyover.css', $stylesheet);

	// Create tables
	if (!$db->table_exists('flyover_settings')) {

		$collation = $db->build_create_table_collation();

		$db->write_query("CREATE TABLE " . TABLE_PREFIX . "flyover_settings (
	        sid INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
			enabled TINYINT(1) NOT NULL DEFAULT '1',
			provider TEXT,
	        id VARCHAR(255) NOT NULL DEFAULT '',
	        secret VARCHAR(255) NOT NULL DEFAULT '',
	        key_token VARCHAR(255) NOT NULL DEFAULT '',
	        usergroup TINYINT(5) NOT NULL DEFAULT '2',
			settings TEXT
        ) ENGINE=MyISAM{$collation};");

	}

	if (!$db->table_exists('flyover_settings_data')) {

		$collation = $db->build_create_table_collation();

		$db->write_query("CREATE TABLE " . TABLE_PREFIX . "flyover_settings_data (
	        uid INT(10) NOT NULL PRIMARY KEY,
	        usernames TEXT
        ) ENGINE=MyISAM{$collation};");

	}

	if (!$db->table_exists('flyover_reports')) {

        $collation = $db->build_create_table_collation();

        $db->write_query("CREATE TABLE ".TABLE_PREFIX."flyover_reports (
            id INT(10) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            dateline VARCHAR(15) NOT NULL DEFAULT '',
            code VARCHAR(10) NOT NULL DEFAULT '',
            file TEXT,
            line INT(6) NOT NULL DEFAULT '0',
            message TEXT,
            trace TEXT
            ) ENGINE=MyISAM{$collation};");

    }

	// Create cache
	$info                        = flyover_info();
	$shadePlugins                = $cache->read('shade_plugins');
	$shadePlugins[$info['name']] = [
		'title' => $info['name'],
		'version' => $info['version']
	];

	$cache->update('shade_plugins', $shadePlugins);

	// Edit templates
	require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';
	find_replace_templatesets('header_welcomeblock_guest', '#\<input name\=\"submit\" type\=\"submit\" class\=\"button\" value\=\"\{\$lang\-\>login\}\" \/\>\<\/div\>\s*\<\/td\>\s*\<\/tr\>#i', '<input name="submit" type="submit" class="button" value="{\$lang->login}" /></div>
								</td>
							</tr>
							<flyover_login_box>');
	find_replace_templatesets('error_nopermission', '#\<input type\=\"submit\" class\=\"button\" value\=\"\{\$lang\-\>login\}\" tabindex\=\"3\" \/\>\s*\<\/td\>\s*\<\/tr\>#i', '<input type="submit" class="button" value="{\$lang->login}" tabindex="3"/>
</td>
</tr>
<flyover_login_box>');
	find_replace_templatesets('headerinclude', '#' . preg_quote('{$stylesheets}') . '#i', '{$stylesheets}{$flyover_popup_mode}');

}

function flyover_uninstall()
{
	global $db, $PL, $cache, $lang;

	if (!$lang->flyover) {
		$lang->load('flyover');
	}

	if (!file_exists(PLUGINLIBRARY)) {
		flash_message($lang->flyover_pluginlibrary_missing, "error");
		admin_redirect("index.php?module=config-plugins");
	}

	$PL or require_once PLUGINLIBRARY;

	// Drop settings
	$PL->settings_delete('flyover');

	// Delete cache
	$info         = flyover_info();
	$shadePlugins = $cache->read('shade_plugins');
	unset($shadePlugins[$info['name']]);
	$cache->update('shade_plugins', $shadePlugins);

	// Drop tables
	$db->drop_table('flyover_settings');
	$db->drop_table('flyover_settings_data');
	$db->drop_table('flyover_reports');

	// Delete settings cache
	$PL->cache_delete('flyover_settings');

	// Delete templates and stylesheets
	$PL->templates_delete('flyover');
	$PL->stylesheet_delete('alerts.css');

	// Edit templates
	require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';
	find_replace_templatesets('header_welcomeblock_guest', '#' . preg_quote('<flyover_login_box>') . '#i', '');
	find_replace_templatesets('error_nopermission', '#' . preg_quote('<flyover_login_box>') . '#i', '');
	find_replace_templatesets('headerinclude', '#' . preg_quote('{$flyover_popup_mode}') . '#i', '');
}

global $mybb;

if ($mybb->settings['flyover_enabled']) {

	// Global
	$plugins->add_hook('global_start', 'flyover_global');
	$plugins->add_hook('global_end', 'flyover_global_end');
	$plugins->add_hook('pre_output_page', 'flyover_pre_output_page');

	// 1.8
	if ($mybb->version_code > 1700) {
		$plugins->add_hook('global_intermediate', 'flyover_global_int');
	}

	// User CP
	$plugins->add_hook('usercp_menu', 'flyover_usercp_menu', 40);
	$plugins->add_hook('usercp_start', 'flyover_usercp');

	// Datahandler for email&passwordless hack
	$plugins->add_hook('datahandler_user_validate', 'flyover_user_validate');
	$plugins->add_hook('usercp_start', 'flyover_usercp_email_password');
	$plugins->add_hook('usercp_email', 'flyover_usercp_email_password_redirect');
	$plugins->add_hook('usercp_password', 'flyover_usercp_email_password_redirect');
	$plugins->add_hook('datahandler_login_verify_password_start', 'flyover_user_validate_password');
	$plugins->add_hook('member_start', 'flyover_lang_load');

	// Who's Online
	$plugins->add_hook("fetch_wol_activity_end", "flyover_fetch_wol_activity");
	$plugins->add_hook("build_friendly_wol_location_end", "flyover_build_wol_location");

	// Admin CP
	if (defined('IN_ADMINCP')) {

		// Delete user
		$plugins->add_hook("admin_user_users_delete_commit", "flyover_user_delete");

		$plugins->add_hook("admin_page_output_header", "flyover_update");
		$plugins->add_hook("admin_page_output_footer", "flyover_settings_footer");

		// Custom module
		$plugins->add_hook("admin_config_menu", "flyover_admin_config_menu");
		$plugins->add_hook("admin_config_action_handler", "flyover_admin_config_action_handler");

		// Replace text inputs to select boxes dinamically
		$plugins->add_hook("admin_config_settings_change", "flyover_settings_saver");
		$plugins->add_hook("admin_formcontainer_output_row", "flyover_settings_replacer");
	}

}

function flyover_user_delete()
{
	global $db, $user;

	$db->delete_query("flyover_settings_data", "uid = '{$user['uid']}'");

	return true;

}

// Block empty passwords
function flyover_user_validate_password(&$data)
{
	global $mybb;

	if ($mybb->settings['flyover_email_pw_less'] and trim($mybb->input['password']) == '') {
		$data['this']->set_error('flyoveremptypassword');
	}
	
	return $data;
}

function flyover_lang_load()
{
	global $lang;

	$lang->load('flyover');
}

function flyover_user_validate(&$data)
{
	global $mybb;

	// Fixes https://www.mybboost.com/thread-about-the-pw-username-issue
	if ($mybb->settings['flyover_email_pw_less'] and $mybb->input['action'] != 'do_email_password' and THIS_SCRIPT == 'flyover.php') {

		unset ($data->errors['missing_email'],
			   $data->errors['invalid_password_length'],
			   $data->errors['bad_password_security'],
			   $data->errors['no_complex_characters']);

		require_once MYBB_ROOT . 'inc/functions_user.php';

		// Add a random loginkey to the user (since it is normally added during validation, and the password has not been entered, it will result in an empty value == not able to login)
		$data->data['loginkey'] = generate_loginkey();

		return $data;

	}
}

function flyover_usercp_email_password()
{
	global $mybb, $db, $lang, $templates, $email, $email2, $headerinclude, $header, $errors, $theme, $usercpnav, $footer;

	if (($mybb->user['email'] and $mybb->user['password']) or !in_array($mybb->input['action'], ['email_password', 'do_email_password'])) {
		return false;
	}

	if ($mybb->input['action'] == 'do_email_password' and $mybb->request_method == 'post') {

		// Verify incoming POST request
		verify_post_check(mybb_get_input('my_post_key'));

		$errors = [];

		// Set up user handler.
		require_once "inc/datahandlers/user.php";
		$userhandler = new UserDataHandler("update");

		$_user = [
			"uid" => $mybb->user['uid'],
			"email" => mybb_get_input('email'),
			"email2" => mybb_get_input('email2'),
			"password" => mybb_get_input('password'),
			"password2" => mybb_get_input('password2')
		];

		$userhandler->set_data($_user);

		if (!$userhandler->validate_user()) {
			$errors = $userhandler->get_friendly_errors();
		}
		else {

			$userhandler->update_user();
			my_setcookie("mybbuser", $mybb->user['uid']."_".$userhandler->data['loginkey']);

			// Notify the user by email that their password has changed
			$mail_message = $lang->sprintf($lang->email_changepassword, $mybb->user['username'], mybb_get_input('email'), $mybb->settings['bbname'], $mybb->settings['bburl']);
			$lang->emailsubject_changepassword = $lang->sprintf($lang->emailsubject_changepassword, $mybb->settings['bbname']);
			my_mail(mybb_get_input('email'), $lang->emailsubject_changepassword, $mail_message);

			// Email requires no activation since the user has already registered using a provider (which should not produce any bots whatsoever)
			$mail_message = $lang->sprintf($lang->email_changeemail_noactivation, $mybb->user['username'], $mybb->settings['bbname'], $mybb->user['email'], mybb_get_input('email'), $mybb->settings['bburl']);
			my_mail(mybb_get_input('email'), $lang->sprintf($lang->emailsubject_changeemail, $mybb->settings['bbname']), $mail_message);

			redirect("usercp.php?action=email", $lang->redirect_email_password_updated);

		}

		if (count($errors) > 0) {
			$mybb->input['action'] = 'email_password';
			$errors = inline_error($errors);
		}

	}

	if ($mybb->input['action'] == 'email_password') {

		if ($errors) {
			$email = htmlspecialchars_uni(mybb_get_input('email'));
			$email2 = htmlspecialchars_uni(mybb_get_input('email2'));
		}
		else {
			$email = $email2 = '';
		}

		eval("\$change_email_password = \"".$templates->get("flyover_usercp_email_password")."\";");
		output_page($change_email_password);

	}
}

function flyover_usercp_email_password_redirect()
{
	global $mybb;

	if (!$mybb->user['email'] and !$mybb->user['password']) {
		header("Location: usercp.php?action=email_password");
		exit();
	}
}

function flyover_global()
{
	global $mybb, $lang, $templatelist;

	if ($templatelist) {
		$templatelist = explode(',', $templatelist);
	}
	else {
		$templatelist = [];
	}

	$templatelist[] = 'flyover_login_box';
	$templatelist[] = 'flyover_login_box_button';
	$templatelist[] = 'flyover_login_box_icon';
	$templatelist[] = 'flyover_login_box_icon_text';

	if (THIS_SCRIPT == 'flyover.php') {

		$templatelist[] = 'flyover_register';
		$templatelist[] = 'flyover_register_email';
		$templatelist[] = 'flyover_register_settings_setting';
		$templatelist[] = 'flyover_register_extrafields';
		$templatelist[] = 'usercp_profile_profilefields_checkbox';
		$templatelist[] = 'usercp_profile_profilefields_textarea';
		$templatelist[] = 'usercp_profile_profilefields_radio';
		$templatelist[] = 'usercp_profile_profilefields_text';
		$templatelist[] = 'usercp_profile_profilefields_select';
		$templatelist[] = 'usercp_profile_profilefields_select_option';
		$templatelist[] = 'usercp_profile_profilefields_multiselect';
		$templatelist[] = 'member_register_customfield';
		$templatelist[] = 'member_register_requiredfields';
		$templatelist[] = 'member_register_additionalfields';

		if ($mybb->input['action'] == 'choose_account') {

			$templatelist[] = 'flyover_choose_account';
			$templatelist[] = 'flyover_choose_account_user';

		}

	}

	if (THIS_SCRIPT == 'usercp.php') {

		$templatelist[] = 'flyover_usercp_menu';

		if (in_array($mybb->input['action'], ['email_password', 'do_email_password'])) {
			$templatelist[] = 'flyover_usercp_email_password';
		}

		if ($mybb->input['action'] == 'flyover') {

			$templatelist[] = 'flyover_usercp_settings';
			$templatelist[] = 'flyover_usercp_settings_header';
			$templatelist[] = 'flyover_usercp_settings_provider';
			$templatelist[] = 'flyover_usercp_settings_provider_setting';
			$templatelist[] = 'flyover_usercp_settings_footer';
			$templatelist[] = 'flyover_usercp_settings_button';

		}

	}

	// Slight performance loss, workaround needed? MyBB 1.6
	if ($mybb->version_code < 1700) {

		if ($mybb->settings['flyover_popup_mode'] and (!$mybb->user['uid'] or THIS_SCRIPT == 'usercp.php')) {

			global $templates;

			eval("\$popup_html = \"".$templates->get("flyover_popup_html")."\";");
			$popup_html = trim(preg_replace('/\s+/', ' ', $popup_html));

			eval("\$flyover_popup_mode = \"".$templates->get("flyover_popup_js")."\";");

		}

	}
	else {

		if ($mybb->settings['flyover_popup_mode']) {

			$templatelist[] = 'flyover_popup_js';
			$templatelist[] = 'flyover_popup_html';

		}

	}

	$templatelist = implode(',', array_filter($templatelist));

	$lang->load('flyover');

}

function flyover_global_int()
{
	global $mybb, $lang, $flyover_popup_mode;

	if ($mybb->settings['flyover_popup_mode'] and (!$mybb->user['uid'] or THIS_SCRIPT == 'usercp.php')) {

		global $templates;

		eval("\$popup_html = \"".$templates->get("flyover_popup_html")."\";");
		$popup_html = trim(preg_replace('/\s+/', ' ', $popup_html));

		eval("\$flyover_popup_mode = \"".$templates->get("flyover_popup_js")."\";");

	}
}

function flyover_global_end()
{
	if (!session_id()) {
		session_start();
	}

	// Search for any stored message (populated using popup mode)
	$session = (array) $_SESSION['flyover'];

	if ($session) {

		unset($_SESSION['flyover']);

		if ($session['type'] == 'error') {
			error(htmlspecialchars_uni($session['message']));
		}
		else if ($session['type'] == 'success') {
			redirect($_SERVER['REQUEST_URI'], htmlspecialchars_uni($session['message']), htmlspecialchars_uni($session['title']));
		}

	}
}

function flyover_pre_output_page(&$page)
{
	global $mybb, $lang, $templates;

	require_once FLYOVER;
	$Flyover = new Flyover();

	// Logout from all providers – TODO: load every provider since HybridAuth logs out from loaded providers
	/*if ($mybb->input['action'] == 'logout') {

		$Flyover->load_api(true);

		$Flyover->logout_all_providers();

	}*/

	// Build login links
	$providers = $Flyover->readCache('settings', 'enabled');

	if (!$providers) {
		return false;
	}

	$querystring = $popupmode = '';

	// Debug mode
	if ($mybb->settings['flyover_debug_mode']) {
		$querystring = "&debug_mode=true";
	}

	// Popup mode
	if ($mybb->settings['flyover_popup_mode']) {
		$popupmode = 'fpopup';
	}

	ksort($providers);

	$buttons = '';
	foreach ($providers as $provider) {

		$name = $provider['provider'];
		$l_name = strtolower($name);

		switch ($mybb->settings['flyover_login_box_type']) {

			// Buttons
			case 1:
			default:
				eval("\$buttons .= \"".$templates->get("flyover_login_box_button")."\";");
				break;

			// Icons
			case 2:
				eval("\$buttons .= \"".$templates->get("flyover_login_box_icon")."\";");
				break;

			// Icons + text
			case 3:
				eval("\$buttons .= \"".$templates->get("flyover_login_box_icon_text")."\";");
				break;

		}

	}

	eval("\$loginbox = \"".$templates->get("flyover_login_box")."\";");

	return str_replace('<flyover_login_box>', $loginbox, $page);
}

function flyover_usercp_menu()
{
	global $mybb, $templates, $theme, $usercpmenu, $lang, $collapsed, $collapsedimg;

	if (!$lang->flyover) {
		$lang->load("flyover");
	}

	eval("\$usercpmenu .= \"" . $templates->get('flyover_usercp_menu') . "\";");
}

function flyover_usercp()
{
	global $mybb, $lang, $inlinesuccess;

	if (!in_array($mybb->input['action'], ['link', 'unlink', 'sync', 'flyover'])) {
		return false;
	}

	$errors = [];

	require_once FLYOVER;
	$Flyover = new Flyover();

	// If we are just watching the UserCP page, we should not load any provider BUT still load the APIs (necessary to check for connected providers if using popup mode)
	$without_provider = ($mybb->input['action'] == 'flyover') ? true : false;

	$Flyover->load($without_provider);

	$settingsToCheck = $Flyover->getActiveUserfieldList();

	if (!$lang->flyover) {
		$lang->load('flyover');
	}

	// Link user
	if ($mybb->input['action'] == 'link' and $mybb->input['provider']) {

		$Flyover->authenticate();

		$Flyover->getUser();

		if ($Flyover->user) {

			// Link him
			if ($Flyover->linkUser($mybb->user, $Flyover->user['identifier'])) {

				$tempKey = $Flyover->provider . '_settings';

				$user = [
					$tempKey => $Flyover->getUserSettings()
				];

				$Flyover->sync($user);

				$Flyover->redirect('usercp.php?action=flyover', $lang->flyover_success_linked_title, $lang->sprintf($lang->flyover_success_linked, $Flyover->provider));

			}
			else {
				$mybb->input['action'] = 'flyover';
				$errors[] = $lang->sprintf($lang->flyover_error_linking, $Flyover->provider);
			}

		}
		else {			
			$errors[] = $lang->flyover_error_noauth;
		}

	}

	// Unlink user
	if ($mybb->input['action'] == 'unlink' and $mybb->input['provider']) {

		if (count($Flyover->getUserEnabledProviders()) == 1 and !$mybb->user['email']) {
			$errors[] = $lang->sprintf($lang->flyover_error_need_to_change_email_password, $Flyover->provider);
			$mybb->input['action'] = 'flyover';
		}
		else {

			$Flyover->unlinkUser();
			$Flyover->redirect('usercp.php?action=flyover', $lang->flyover_success_unlinked_title, $lang->sprintf($lang->flyover_success_unlinked, $Flyover->provider));

		}

	}

	// Sync
	if ($mybb->input['action'] == 'sync' and $mybb->input['provider']) {

		$Flyover->authenticate();

		$Flyover->getUser();

		if ($Flyover->user) {

			$tempKey = $Flyover->provider . '_settings';

			$user = [
				$tempKey => $Flyover->getUserSettings()
			];

			$Flyover->sync($user);

			$Flyover->redirect('usercp.php?action=flyover', $lang->flyover_success_synced_title, $lang->sprintf($lang->flyover_success_synced, $Flyover->provider));

		}
		else {
			$errors[] = $lang->flyover_error_noauth;
		}

	}

	// Settings page
	if ($mybb->input['action'] == 'flyover') {

		global $db, $theme, $templates, $headerinclude, $header, $footer, $plugins, $usercpnav;

		add_breadcrumb($lang->nav_usercp, 'usercp.php');
		add_breadcrumb($lang->flyover_page_title, 'usercp.php?action=flyover');

		$flyoverSettings = $Flyover->readCache('settings', 'enabled');

		if (!$flyoverSettings) {
			header('Location: usercp.php');
		}

		ksort($flyoverSettings);

		// The actual connected providers				
		$query = $db->simple_select('flyover_settings_data', '*', 'uid = ' . (int) $mybb->user['uid'], ['limit' => 1]);
		$user_connected_providers = (array) $db->fetch_array($query);

		// Update settings
		if ($mybb->request_method == 'post') {

			verify_post_check($mybb->input['my_post_key']);

			$new_settings = [];

			$settingsSelected = (array) $mybb->input['providers'];
			$providers = array_keys($flyoverSettings);

			// Loop through the connected providers
			foreach ($providers as $provider) {

				// Skip if not connected
				if (!$user_connected_providers[$provider]) {
					continue;
				}

				$tempKey = $provider . '_settings';

				foreach ($settingsToCheck as $setting) {

					$new_settings[$tempKey][$setting] = 0;

					if ($settingsSelected[$provider][$setting] == 1) {
						$new_settings[$tempKey][$setting] = 1;
					}

				}

			}

			$Flyover->updateUserSettings($new_settings);
			$Flyover->performUserUpdate();

			$Flyover->redirect('usercp.php?action=flyover', $lang->flyover_success_settings_updated_title, $lang->flyover_success_settings_updated);

		}

		// Errors
		if ($errors) {
			$errors = inline_error($errors);
		}
		else {
			unset($errors);
		}

		// Show main content
		$options = '';

		// Header
		if ($user_connected_providers) {
			eval("\$options_header = \"" . $templates->get("flyover_usercp_settings_header") . "\";");
		}

		$flyoverUsernames = (array) my_unserialize($user_connected_providers['usernames']);
		$not_connected = [];

		$buttons = ['sync', 'unlink'];

		// List connected providers
		foreach ($flyoverSettings as $key => $configuration) {

			// Add to another array if not connected
			if (!$user_connected_providers[$key]) {

				$not_connected[$key] = $configuration;
				continue;

			}

			// Build the class
			$altbg = alt_trow();

			// Popup mode
			$popupmode = ($mybb->settings['flyover_popup_mode'] and $Flyover->userIsConnectedWith($key) != 1) ? 'fpopup' : '';

			$flyover_setting = (array) $configuration['settings'];

			$tempkey = $key . '_settings';
			$disconnect = $sync = '';

			// Build the Sync and Disconnect button
			foreach ($buttons as $button) {

				$querystring = '?action=' . $button;

				$tempKey = 'flyover_settings_' . $button;

				$label = $lang->$tempKey;

				eval("\$$button = \"" . $templates->get("flyover_usercp_settings_button") . "\";");

			}

			// Build the image
			$temp_image = $mybb->settings['bburl'] . '/images/social/' . strtolower($key) . '.png';
			$image = '<img src="' . $temp_image . '" class="icon ' . strtolower($key) . '" />';

/*
			if (@getimagesize($temp_image)) {
				$image = '<img src="' . $temp_image . '" class="icon ' . strtolower($key) . '" />';
			}
*/

			// Build the "Connected with" label
			$connected_with = '';
			if ($flyoverUsernames[$key] and $user_connected_providers[$key]) {
				$connected_with = $lang->sprintf($lang->flyover_settings_connected_with, $flyoverUsernames[$key]);
			}
			else if (!$flyoverUsernames[$key] and $user_connected_providers[$key]) {
				$connected_with = $lang->sprintf($lang->flyover_settings_could_not_fetch, $key);
			}

			// Build settings
			$provider_settings = '';
			$user_settings = (array) my_unserialize($user_connected_providers[$tempkey]);

			foreach ($settingsToCheck as $setting) {

				if (!$flyover_setting[$setting] or (!$mybb->settings['flyover_' . $setting . 'field'] and $setting != 'avatar')) {
					continue;
				}

				$checked = ($user_settings[$setting]) ? ' checked' : '';

				// Set up this setting label
				$tempKey = 'flyover_settings_' . $setting;
				$label = $lang->$tempKey;

				eval("\$provider_settings .= \"" . $templates->get("flyover_usercp_settings_provider_setting") . "\";");

			}

			eval("\$options .= \"" . $templates->get("flyover_usercp_settings_provider") . "\";");

		}

		// List not connected providers
		if ($not_connected) {

			$class = $button = '';

			foreach ($not_connected as $key => $configuration) {

				$querystring = '?action=link';

				// Popup mode
				$popupmode = ($mybb->settings['flyover_popup_mode'] and $Flyover->userIsConnectedWith($key) != 1) ? 'fpopup' : '';

				// Build the image
				$temp_image = $mybb->settings['bburl'] . '/images/social/' . strtolower($key) . '.png';

// 				$label = (@getimagesize($temp_image)) ? '<img src="' . $temp_image . '" class="icon ' . strtolower($key) . '" />' : $key;
				$label = '<img src="' . $temp_image . '" class="icon ' . strtolower($key) . '" />';

				eval("\$available_providers .= \"" . $templates->get("flyover_usercp_settings_button") . "\";");

			}

			// Footer
			eval("\$options_footer = \"" . $templates->get("flyover_usercp_settings_footer") . "\";");

		}

		eval("\$content = \"" . $templates->get('flyover_usercp_settings') . "\";");

		output_page($content);

	}
}

/**
 * Update the plugin in the ACP and display inline style
 **/
function flyover_update()
{
	global $mybb, $db, $cache, $lang, $inline_style;

	$file = MYBB_ROOT . "inc/plugins/Flyover/class_update.php";

	if (file_exists($file)) {
		require_once $file;
	}

	$inline_style = <<<HTML
<style type='text/css'>
	.icon {
		width: 30px;
		border-radius: 2px;
	}
	.icon.inactive,
	.provider_btn.inactive {
		opacity: .3
	}
	*[class*="500px"] {
		background: #444
	}
	.aol,
	.steam {
		background: #000
	}
	.amazon {
		background: #ff9900
	}
	.beatsmusic {
		background: #C00045
	}
	.bitbucket {
		background: #205081
	}
	.deezer {
		background: #272727
	}
	.discord {
		background: #7289da
	}
	.disqus {
		background: #2E9FFF
	}
	.dribbble {
		background: #EA4C89
	}
	.dropbox {
		background: #2281CF
	}
	.envato {
		background: #82b541
	}
	.evernote {
		background: #6BB130
	}
	.facebook {
		background: #3B5998
	}
	.foursquare {
		background: #2398C9
	}
	.github {
		background: #4183C4
	}
	.gitlab {
		background: #fc6d26
	}
	.google {
		background: #245DC1
	}
	.instagram {
		background: #3F729B
	}
	.lastfm {
		background: #D51007
	}
	.linkedin {
		background: #007FB1
	}
	.live,
	.microsoft {
		background: #3E73B4
	}
	.paypal {
		background: #1F356F
	}
	.pinterest {
		background: #bd081c
	}
	.reddit {
		background: #FF4500
	}
	.slack {
		background: #6ecadc
	}
	.soundcloud {
		background: #FF6600
	}
	.stackexchange {
		background: #1F5196
	}
	.tumblr {
		background: #2C4762
	}
	.twitchtv {
		background: #6441A5
	}
	.twitter {
		background: #00ACED
	}
	.vimeo {
		background: #44BBFF
	}
	.vkontakte {
		background: #2E9FFF
	}
	.wargaming {
		background: #d2191f
	}
	.wordpress {
		background: #21759B
	}
	.yahoo {
		background: #731A8B
	}
	.login_box {
		display: inline-block;
	    padding: 10px 15px;
	    margin: 10px 0;
	    vertical-align: middle
	}
	.login_box_buttons,
	.login_box > .provider_btn {
		padding: 5px 8px;
	    border-radius: 3px;
	    color: #fff;
	    font-family: 'Helvetica Neue', 'Helvetica', Arial, sans-serif;
	    font-size: 13px;
	    vertical-align: middle;
	    margin: 5px 0;
	    display: block;
	    line-height: 20px
	}
	.login_box > .provider_btn {
		margin: 5px 10px;
		display: inline-block;
		min-width: 120px
	}
	.login_box_buttons .icon {
	    width: 20px;
	    vertical-align: middle
	}
	.login_box_icon {
		vertical-align: middle;
		margin: 0 3px
	}
	.login_box_icon_text {
		vertical-align: middle;
		margin: 5px 5px 5px 0
	}
</style>
HTML;
}

/**
 * Displays peekers in settings
 **/
function flyover_settings_footer()
{
	global $mybb, $db;

	if ($mybb->input["action"] == "change" and $mybb->request_method != "post") {

		$gid = flyover_settings_gid();

		if ($mybb->input["gid"] == $gid or !$mybb->input['gid']) {

			if ($mybb->version_code > 1700) {
				echo '<script type="text/javascript">
$(document).ready(function() {
	new Peeker($(".setting_flyover_passwordpm"), $("#row_setting_flyover_passwordpm_subject"), /1/, true);
	new Peeker($(".setting_flyover_passwordpm"), $("#row_setting_flyover_passwordpm_message"), /1/, true);
	new Peeker($(".setting_flyover_passwordpm"), $("#row_setting_flyover_passwordpm_fromid"), /1/, true);
});
</script>';
			}
			else {
				echo '<script type="text/javascript">
Event.observe(window, "load", function() {
	loadFlyoverPeekers();
});
function loadFlyoverPeekers()
{
	new Peeker($$(".setting_flyover_passwordpm"), $("row_setting_myfbconnect_passwordpm_subject"), /1/, true);
	new Peeker($$(".setting_flyover_passwordpm"), $("row_setting_myfbconnect_passwordpm_message"), /1/, true);
	new Peeker($$(".setting_flyover_passwordpm"), $("row_setting_myfbconnect_passwordpm_fromid"), /1/, true);
}
</script>';
			}

		}

	}

}

/**
 * Gets the gid of Flyover settings group.
 **/
function flyover_settings_gid()
{
	global $db;

	$query = $db->simple_select("settinggroups", "gid", "name = 'flyover'", [
		"limit" => 1
	]);
	$gid   = (int) $db->fetch_field($query, "gid");

	return $gid;
}

function flyover_fetch_wol_activity(&$user_activity)
{
	global $user, $mybb;

	// Get the base filename
	$split_loc = explode(".php", $user_activity['location']);
	if ($split_loc[0] == $user['location']) {
		$filename = '';
	} else {
		$filename = my_substr($split_loc[0], -my_strpos(strrev($split_loc[0]), "/"));
	}

	// Get parameters of the URI
	if ($split_loc[1]) {
		$temp = explode("&amp;", my_substr($split_loc[1], 1));
		foreach ($temp as $param) {
			$temp2                 = explode("=", $param, 2);
			$temp2[0]              = str_replace("amp;", '', $temp2[0]);
			$parameters[$temp2[0]] = $temp2[1];
		}
	}

	// If our plugin is found, store our custom vars in the main $user_activity array
	switch ($filename) {
		case "flyover":
			if ($parameters['action']) {
				$user_activity['activity'] = $parameters['action'];
			}
			if ($parameters['provider']) {
				$user_activity['flyover_provider'] = $parameters['provider'];
			}
			break;
	}

	return $user_activity;
}

function flyover_build_wol_location(&$plugin_array)
{
	global $lang;

	$lang->load('flyover');

	// Let's see what action we are watching
	switch ($plugin_array['user_activity']['activity']) {
		case "login":
		case "do_login":
			$plugin_array['location_name'] = $lang->sprintf($lang->flyover_viewing_logging_in, $plugin_array['user_activity']['flyover_provider']);
			break;
		case "register":
			$plugin_array['location_name'] = $lang->sprintf($lang->flyover_viewing_registering, $plugin_array['user_activity']['flyover_provider']);
			break;
	}

	return $plugin_array;
}

$GLOBALS['replace_custom_fields'] = flyover_get_active_userfield_list();

function flyover_settings_saver()
{
	global $mybb, $page, $replace_custom_fields;

	if ($mybb->request_method == "post" and $mybb->input['upsetting'] and $page->active_action == "settings" and $mybb->input['gid'] == flyover_settings_gid()) {

		// Custom fields casting
		foreach ($replace_custom_fields as $setting) {

			$child = $setting . 'field';

			$mybb->input['upsetting']['flyover_'.$child] = $mybb->input['flyover_'.$child.'_select'];

			// Reset parent field if empty
			if (!$mybb->input['upsetting']['flyover_'.$child]) {
				$mybb->input['upsetting']['flyover_'.$setting] = 0;
			}
		}

		// Usergroup casting
		$mybb->input['upsetting']['flyover_usergroup'] = (int) $mybb->input['flyover_usergroup_select'];

	}
}

function flyover_settings_replacer($args)
{
	global $db, $lang, $form, $mybb, $page, $inline_style, $replace_custom_fields;

	if ($page->active_action != "settings" and $mybb->input['action'] != "change" and $mybb->input['gid'] != flyover_settings_gid()) {
		return false;
	}

	$query = $db->simple_select('profilefields', 'name, fid');

	$profilefields = ['' => ''];

	while ($field = $db->fetch_array($query)) {
		$profilefields[$field['fid']] = $field['name'];
	}

	foreach ($replace_custom_fields as $setting) {

		if ($args['row_options']['id'] == 'row_setting_flyover_' . $setting . 'field') {

			if (!$profilefields) {

				$args['content'] = $lang->flyover_select_nofieldsavailable;

				continue;

			}

			$tempKey = 'flyover_' . $setting . 'field';

			// Replace the textarea with a cool selectbox
			$args['content'] = $form->generate_select_box($tempKey . '_select', $profilefields, $mybb->settings[$tempKey]);

		}

	}

	if ($args['row_options']['id'] == 'row_setting_flyover_usergroup') {

		$tempKey = 'flyover_usergroup';

		// Replace the textarea with a cool selectbox
		$args['content'] = $form->generate_group_select($tempKey . '_select', [
			$mybb->settings[$tempKey]
		]);

	}

	if ($args['row_options']['id'] == 'row_setting_flyover_login_box_type') {

		require_once FLYOVER;
		$Flyover = new Flyover();

		// Get 3 random providers
		$flyoverSettings = (array) array_filter($Flyover->readCache('settings'));

		if (!$flyoverSettings) {

			$args['content'] = $lang->flyover_login_box_configure;

			return;

		}

		// Stick to some defaults if we've got less than 3
		if (count($flyoverSettings) < 3) {
			$flyoverSettings['Facebook'] = 'Facebook';
			$flyoverSettings['Twitter'] = 'Twitter';
			$flyoverSettings['Google'] = 'Google';
		}

		$flyoverSettings = array_rand($flyoverSettings, 3);

		// Build the buttons
		$login_box_buttons = $login_box_icons = $login_box_icons_text = '';
		foreach ($flyoverSettings as $key => $file) {

			$l_file = strtolower($file);
			$login_box_buttons .= "<span class='{$l_file} login_box_buttons'><img class='icon' src='../images/social/{$l_file}.png' /> Login with {$file}</span>\n";
			$login_box_icons .= "<img class='icon {$l_file} login_box_icon' src='../images/social/{$l_file}.png' />\n";
			$login_box_icons_text .= "<img class='icon {$l_file} login_box_icon_text' src='../images/social/{$l_file}.png' /> Login with {$file}<br />\n";

		}

		$arr = [
			'buttons' => $login_box_buttons,
			'icons_text' => $login_box_icons_text,
			'icons' => $login_box_icons
		];

		// Replace them
		foreach ($arr as $find => $replace) {
			$args['content'] = str_replace($find, '<div class="login_box">' . $replace . '</div>', $args['content']);
		}

		// Echo the stylesheets
		echo $inline_style;

	}
}

function flyover_admin_config_menu($sub_menu)
{
	global $lang;

	$lang->load("flyover");

	$sub_menu[] = [
		"id" => "flyover",
		"title" => $lang->flyover,
		"link" => "index.php?module=config-flyover"
	];

	return $sub_menu;
}

function flyover_admin_config_action_handler($actions)
{
	$actions['flyover'] = [
		"active" => "flyover",
		"file" => "flyover.php"
	];

	return $actions;
}

function mybb_get_input($name)
{
	global $mybb;
	if (!isset($mybb->input[$name]) || !is_scalar($mybb->input[$name])) {
		return '';
	}
	return $mybb->input[$name];
}

function flyover_get_active_userfield_list($excludeProtected = false)
{
	$fields = [
		'avatar',
		'sex',
		'bio',
		'location',
		'username',
		'website',
		'identifier'
	];

	$protected = [
		'avatar',
		'website'
	];

	return ($excludeProtected) ? array_values(array_diff($fields, $protected)) : $fields;
}