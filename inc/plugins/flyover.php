<?php

/**
 * Integrates MyBB with many social networks, featuring login and registration.
 *
 * @package Flyover
 * @author  Shade <shad3-@outlook.com>
 * @license Copyrighted ©
 * @version 2.1
 */

if (!defined('IN_MYBB')) {
	die('Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.');
}

if (!defined("PLUGINLIBRARY")) {
	define("PLUGINLIBRARY", MYBB_ROOT . "inc/plugins/pluginlibrary.php");
}

define ('FLYOVER', MYBB_ROOT . "flyover/autoload.php");
include FLYOVER;

function flyover_info()
{
	return [
		'name' => 'Flyover',
		'description' => "Integrates MyBB with many social networks, featuring login and registration.",
		'website' => 'https://www.mybboost.com/forum-flyover',
		'author' => 'Shade',
		'authorsite' => 'https://www.mybboost.com',
		'version' => '2.1',
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

		// Passwordless
		'passwordless' => [
			'title' => $lang->setting_flyover_passwordless,
			'description' => $lang->setting_flyover_passwordless_desc,
			'value' => '0'
		],

		// Continuous operational state
		'keeprunning' => [
			'title' => $lang->setting_flyover_keeprunning,
			'description' => $lang->setting_flyover_keeprunning_desc,
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
	$customFields = Flyover\Helper\Utilities::getUserfields();
	foreach ($customFields as $field) {

		if (in_array($field, ['avatar', 'website'])) {
			continue;
		}

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
	        scopes TEXT,
	        usergroup TINYINT(5) NOT NULL DEFAULT '2',
			settings TEXT
        ) ENGINE=MyISAM{$collation};");

	}

	if (!$db->table_exists('flyover_users')) {

		$collation = $db->build_create_table_collation();

		$db->write_query("CREATE TABLE " . TABLE_PREFIX . "flyover_users (
	        uid INT(10) NOT NULL PRIMARY KEY,
	        usernames TEXT
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
	find_replace_templatesets('header_welcomeblock_guest_login_modal', '#\z#i', "\n<flyover_login_box>");
	find_replace_templatesets('error_nopermission', '#' . preg_quote("</tr>
</table>
</form>") . '#i', "</tr>
<flyover_login_box>
</table>
</form>");

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
	$db->drop_table('flyover_users');
	$db->drop_table('flyover_reports');

	// Delete settings cache
	$PL->cache_delete('flyover_settings');

	// Delete templates and stylesheets
	$PL->templates_delete('flyover');
	$PL->stylesheet_delete('flyover.css');

	// Edit templates
	require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';
	find_replace_templatesets('header_welcomeblock_guest_login_modal', '#' . preg_quote('<flyover_login_box>') . '#i', '');
	find_replace_templatesets('error_nopermission', '#' . preg_quote('<flyover_login_box>') . '#i', '');
}

global $mybb;

if ($mybb->settings['flyover_enabled']) {

	// Global
	$plugins->add_hook('global_start', 'flyover_global');
	$plugins->add_hook('pre_output_page', 'flyover_pre_output_page');

	// User CP
	$plugins->add_hook('usercp_menu', 'flyover_usercp_menu', 40);
	$plugins->add_hook('usercp_start', 'flyover_usercp');

	// Datahandler for email&passwordless hack
	$plugins->add_hook('datahandler_user_validate', 'flyover_user_validate');
	$plugins->add_hook('usercp_start', 'flyover_usercp_email_password');
	$plugins->add_hook('usercp_email', 'flyover_usercp_email_password_redirect');
	$plugins->add_hook('usercp_password', 'flyover_usercp_email_password_redirect');
	$plugins->add_hook('datahandler_login_verify_password_start', 'flyover_user_validate_login');
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

	return $db->delete_query("flyover_users", "uid = '{$user['uid']}'");
}

// Block empty passwords and usernames
function flyover_user_validate_login(&$data)
{
	if (empty(trim($data['this']->data['password']))) {
		$data['this']->set_error('flyoveremptypassword');
	}

	if (empty(trim($data['this']->data['username']))) {
		$data['this']->invalid_combination();
	}

	return $data;
}

function flyover_lang_load()
{
	$GLOBALS['lang']->load('flyover');
}

function flyover_user_validate(&$data)
{
	global $mybb;

	// Fixes https://www.mybboost.com/thread-about-the-pw-username-issue
	if ($mybb->settings['flyover_passwordless']
		and $mybb->input['action'] != 'do_email_password'
		and THIS_SCRIPT == 'flyover.php') {

		unset ($data->errors['invalid_password_length'],
			   $data->errors['bad_password_security'],
			   $data->errors['no_complex_characters']);

		require_once MYBB_ROOT . 'inc/functions_user.php';

		// Add a random loginkey to the user (since it is normally added during validation, and the password
		// has not been entered, it will result in an empty value == not able to login)
		$data->data['loginkey'] = generate_loginkey();

		return $data;

	}
}

function flyover_usercp()
{
	new Flyover\Usercp\Usercp;
}

function flyover_usercp_email_password()
{
	global $mybb, $db, $lang, $templates, $email, $email2, $headerinclude, $header, $errors, $theme, $usercpnav, $footer;

	if (($mybb->user['email'] and $mybb->user['password'])
		or !in_array($mybb->input['action'], ['email_password', 'do_email_password'])) {
		return false;
	}

	if ($mybb->request_method == 'post') {

		// Verify incoming POST request
		verify_post_check($mybb->get_input('my_post_key'));

		$errors = [];

		// Set up user handler.
		require_once "inc/datahandlers/user.php";
		$userhandler = new UserDataHandler("update");

		$newData = [
			"uid" => $mybb->user['uid'],
			"password" => $mybb->get_input('password'),
			"password2" => $mybb->get_input('password2')
		];

		// Fallback for missing email (from 2.0, accounts are required to enter an email)
		if ($mybb->get_input('email')) {

			$newData['email'] = $mybb->get_input('email');
			$newData['email2'] = $mybb->get_input('email2');

		}

		$userhandler->set_data($newData);

		if (!$userhandler->validate_user()) {
			$errors = $userhandler->get_friendly_errors();
		}
		else {

			$userhandler->update_user();
			my_setcookie("mybbuser", $mybb->user['uid']."_".$userhandler->data['loginkey']);

			// Notify the user by email that their password has changed
			$mail_message = $lang->sprintf($lang->email_changepassword, $mybb->user['username'], $mybb->get_input('email'), $mybb->settings['bbname'], $mybb->settings['bburl']);
			$lang->emailsubject_changepassword = $lang->sprintf($lang->emailsubject_changepassword, $mybb->settings['bbname']);
			my_mail($mybb->get_input('email'), $lang->emailsubject_changepassword, $mail_message);

			// Email requires no activation since the user has already registered using a provider (which should not produce any bots whatsoever)
			if ($mybb->get_input('email')) {
				$mail_message = $lang->sprintf($lang->email_changeemail_noactivation, $mybb->user['username'], $mybb->settings['bbname'], $mybb->user['email'], $mybb->get_input('email'), $mybb->settings['bburl']);
				my_mail($mybb->get_input('email'), $lang->sprintf($lang->emailsubject_changeemail, $mybb->settings['bbname']), $mail_message);
			}

			redirect("usercp.php?action=email", $lang->redirect_email_password_updated);

		}

		if (count($errors) > 0) {
			$mybb->input['action'] = 'email_password';
			$errors = inline_error($errors);
		}

	}

	if ($mybb->input['action'] == 'email_password') {

		add_breadcrumb($lang->nav_usercp, "usercp.php");
		add_breadcrumb($lang->nav_password);

		if ($errors) {
			$email = htmlspecialchars_uni($mybb->get_input('email'));
			$email2 = htmlspecialchars_uni($mybb->get_input('email2'));
		}
		else {
			$email = $email2 = '';
		}

		if (!$mybb->user['email']) {
			eval("\$email = \"".$templates->get("flyover_usercp_email_password_emailbit")."\";");
		}

		eval("\$change_email_password = \"".$templates->get("flyover_usercp_email_password")."\";");
		output_page($change_email_password);

	}
}

function flyover_usercp_email_password_redirect()
{
	global $mybb;

	if (!$mybb->user['email'] or !$mybb->user['password']) {
		header("Location: usercp.php?action=email_password");
		exit;
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
			$templatelist[] = 'flyover_usercp_email_password_emailbit';

		}

		if ($mybb->input['action'] == 'flyover') {

			$templatelist[] = 'flyover_usercp_settings';
			$templatelist[] = 'flyover_usercp_settings_header';
			$templatelist[] = 'flyover_usercp_settings_actions';
			$templatelist[] = 'flyover_usercp_settings_provider';
			$templatelist[] = 'flyover_usercp_settings_provider_setting';
			$templatelist[] = 'flyover_usercp_settings_footer';
			$templatelist[] = 'flyover_usercp_settings_button';

		}

	}

	$templatelist = implode(',', array_filter($templatelist));

	$lang->load('flyover');

}

function flyover_pre_output_page(&$page)
{
	global $mybb, $lang, $templates;

	$cache = new Flyover\Session\Cache();

	// Build login links
	$providers = $cache->read('settings', 'enabled');

	if (!$providers) {
		return false;
	}

	ksort($providers);

	$types = [
		1 => 'button',
		2 => 'icon',
		3 => 'icon_text'
	];

	$type = $types[intval($mybb->settings['flyover_login_box_type'])] ?? $types[1];

	$buttons = '';
	foreach ($providers as $provider) {

		$name = $provider['provider'];
		$lowercaseName = strtolower($name);

		// Adjustments for icons
		if ($name == 'TwitchTV') {
			$lowercaseName = 'twitch';
		}
		else if ($name == 'Vkontakte') {
			$lowercaseName = 'vk';
		}
		else if ($name == 'WindowsLive') {
			$lowercaseName = 'windows';
		}
		else if ($name == 'StackExchange') {
			$lowercaseName = 'stack-exchange';
		}

		eval("\$buttons .= \"".$templates->get("flyover_login_box_" . $type)."\";");

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

/**
 * Update the plugin in the ACP and display inline style
 **/
function flyover_update()
{
	global $inline_style;

	new Flyover\Update\Update;

	$inline_style = <<<HTML
<style type='text/css'>
	.flyover.settings {
		display: inline-block;
		vertical-align: middle;
		margin: 10px;
	}
	.flyover .icon {
	    width: 30px;
	    height: 30px;
	    line-height: 30px;
	    border-radius: 2px;
	    color: #fff;
	    text-align: center;
	    margin: 5px 5px 5px 0;
	    vertical-align: middle
	}
	.flyover .icon.inactive,
	.flyover .provider.inactive {
		opacity: .3
	}
	.bitbucket {
		background: #205081
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
	.linkedin {
		background: #007FB1
	}
	.mailru {
		background: #168de2
	}
	.odnoklassniki {
		background: #ed812b
	}
	.reddit {
		background: #FF4500
	}
	.spotify {
		background: #1db954
	}
	.stackexchange, .stack-exchange {
		background: #1F5196
	}
	.steam {
		background: #000
	}
	.tumblr {
		background: #2C4762
	}
	.twitchtv, .twitch {
		background: #6441A5
	}
	.twitter {
		background: #00ACED
	}
	.vkontakte, .vk {
		background: #2E9FFF
	}
	.wordpress {
		background: #21759B
	}
	.yahoo {
		background: #731A8B
	}
	.yandex {
		background: #FFCC00
	}
	.wechat {
		background: #7bb32e
	}
	.windowslive,
	.windows {
		background: #3E73B4
	}
	.flyover .provider {
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
	.flyover .provider {
		margin: 5px 10px;
		display: inline-block;
		min-width: 120px
	}
	.flyover .iconAndText {
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
	global $mybb;

	if ($mybb->input["action"] == "change" and
		$mybb->request_method != "post" and
		($mybb->input["gid"] == flyover_settings_gid() or !$mybb->input['gid'])
		) {

			echo <<<HTML
<script type="text/javascript">
	$(document).ready(function() {
		new Peeker($(".setting_flyover_passwordpm"), $("#row_setting_flyover_passwordpm_subject"), /1/, true);
		new Peeker($(".setting_flyover_passwordpm"), $("#row_setting_flyover_passwordpm_message"), /1/, true);
		new Peeker($(".setting_flyover_passwordpm"), $("#row_setting_flyover_passwordpm_fromid"), /1/, true);
	});
</script>
HTML;

	}
}

/**
 * Gets the gid of Flyover settings group.
 **/
function flyover_settings_gid()
{
	global $db;
	static $gid;

	if (!$gid) {

		$query = $db->simple_select("settinggroups", "gid", "name = 'flyover'", [
			"limit" => 1
		]);
		$gid   = (int) $db->fetch_field($query, "gid");

	}

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

$GLOBALS['replace_custom_fields'] = Flyover\Helper\Utilities::getUserfields();

function flyover_settings_saver()
{
	global $mybb, $page, $replace_custom_fields;

	if ($mybb->request_method == "post" and $mybb->input['upsetting'] and $page->active_action == "settings" and $mybb->input['gid'] == flyover_settings_gid()) {

		// Custom fields casting
		foreach ($replace_custom_fields as $setting) {

			$child = $setting . 'field';

			$mybb->input['upsetting']['flyover_' . $child] = $mybb->input['flyover_'.$child.'_select'];

			// Reset parent field if empty
			if (!$mybb->input['upsetting']['flyover_' . $child]) {
				$mybb->input['upsetting']['flyover_' . $setting] = 0;
			}
		}

		// Usergroup casting
		$mybb->input['upsetting']['flyover_usergroup'] = (int) $mybb->input['flyover_usergroup_select'];

	}
}

function flyover_settings_replacer($args)
{
	global $db, $lang, $form, $mybb, $page, $inline_style, $replace_custom_fields;
	static $profilefields;

	if ($page->active_action != "settings" or $mybb->input['action'] != "change" or $mybb->input['gid'] != flyover_settings_gid()) {
		return false;
	}

	if (!$profilefields) {

		$profilefields = ['' => ''];

		$query = $db->simple_select('profilefields', 'name, fid');
		while ($field = $db->fetch_array($query)) {
			$profilefields[$field['fid']] = $field['name'];
		}

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

		$cache = new Flyover\Session\Cache();

		echo PHP_EOL . '<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/brands.css" crossorigin="anonymous">';
		echo PHP_EOL . '<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/fontawesome.css" crossorigin="anonymous">';

		// Get 3 random providers
		$settings = (array) array_filter($cache->read('settings'));

		if (!$settings) {
			return $args['content'] = $lang->flyover_login_box_configure;
		}

		// Stick to some defaults if we've got less than 3
		if (count($settings) < 3) {
			$settings = array_flip(['Facebook', 'Twitter', 'Google']);
		}

		$settings = array_rand($settings, 3);

		// Build the buttons
		$buttons = $icons = $iconsAndText = '';
		foreach ($settings as $provider) {

			$lowercaseProvider = strtolower($provider);
			$buttons .= "<span class='provider {$lowercaseProvider}'><i class='fab fa-{$lowercaseProvider}'></i> Login with {$provider}</span>";
			$icons .= "<i class='icon {$lowercaseProvider} fab fa-{$lowercaseProvider}'></i>";
			$iconsAndText .= "<span><i class='icon {$lowercaseProvider} fab fa-{$lowercaseProvider}'></i>  Login with {$provider}</span>";

		}

		$arr = [
			'buttons' => $buttons,
			'icons_text' => $iconsAndText,
			'icons' => $icons
		];

		// Replace them
		foreach ($arr as $find => $replace) {
			$args['content'] = str_replace($find, '<div class="flyover settings">' . $replace . '</div>', $args['content']);
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