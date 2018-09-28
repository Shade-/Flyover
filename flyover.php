<?php

/**
 * Flyover
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'flyover.php');
define('ALLOWABLE_PAGE', 'login,choose_account,register');

if (isset($_REQUEST['auth']) and $_REQUEST['auth'] == true) {

	require_once './inc/plugins/Flyover/hybridauth/index.php';
	exit;

}

require_once "./global.php";

$lang->load('flyover');

if (!$mybb->settings['flyover_enabled']) {

	header("Location: index.php");
	exit;

}

if ($mybb->user['uid']) {
	header('Location: index.php');
}

// Registrations are disabled
if ($mybb->settings['disableregs'] == 1) {

	if (!$lang->registrations_disabled) {
		$lang->load("member");
	}

	error($lang->registrations_disabled);

}

// Load API
require_once MYBB_ROOT . "inc/plugins/Flyover/class_core.php";
$Flyover = new Flyover();
$Flyover->load();

// If the user is watching another page, fallback to login
if (!in_array($mybb->input['action'], explode(',', ALLOWABLE_PAGE))) {
	$mybb->input['action'] = 'login';
}

// Begin the authenticating process
if ($mybb->input['action'] == 'login') {

	if ($mybb->user['uid']) {
		header('Location: index.php');
	}

	$Flyover->rememberCurrentPage();
	$Flyover->authenticate();

	$Flyover->getUser();

	$result = $Flyover->process();

	if ($result['error']) {
		$errors = $result['error'];
	}

	if (in_array($result['action'], ['choose_account', 'register'])) {
		$mybb->input['action'] = $result['action'];
	}

	// If using popup mode, this closes the popup
	if ($result['error'] or in_array($result['action'], ['choose_account', 'register'])) {

		$url = (in_array($result['action'], ['choose_account', 'register'])) ? 'flyover.php?action=' . $result['action'] . '&provider=' . $Flyover->provider : '';

		$Flyover->closePopup($url, 'exit');

	}

}

// Choose account
if ($mybb->input['action'] == 'choose_account') {

	// We still need to check if this user is authenticated
	if (!$Flyover->user) {

		if (!$Flyover->provider) {
			error($lang->flyover_error_no_provider);
		}

		$Flyover->authenticate();
		$Flyover->getUser();

		// Still no user?
		if (!$Flyover->user) {
			error($lang->sprintf($lang->flyover_error_no_user, $Flyover->provider));
		}

		$result = $Flyover->process();

	}

	if ($mybb->request_method == 'post') {

		verify_post_check($mybb->input['my_post_key']);

		$uid = (int) $mybb->input['uid'];

		$result = $Flyover->process($uid);

		if ($result['error']) {
			error($result['error']);
		}

		if ($result['action'] == 'register') {
			$mybb->input['action'] = $result['action'];
		}

	}
	else {

		$users = $result['accounts'];

		if (!$users) {
			error($lang->flyover_error_function_not_used_correctly);
		}

		$lang->flyover_choose_account_desc = $lang->sprintf($lang->flyover_choose_account_desc, $Flyover->provider);

		$accounts = '';
		foreach ($users as $user) {

			$matchType = ($user[$Flyover->provider]) ? $lang->sprintf($lang->flyover_choose_account_match_by_id, $Flyover->provider) : $lang->flyover_choose_account_match_by_email;
			eval("\$accounts .= \"" . $templates->get("flyover_choose_account_user") . "\";");

		}

		eval("\$choose_account = \"" . $templates->get("flyover_choose_account") . "\";");
		output_page($choose_account);

	}

}

// Register page fallback
if ($mybb->input['action'] == 'register') {

	if (!$Flyover->user) {

		if (!$Flyover->provider) {
			error($lang->flyover_error_no_provider);
		}

		$Flyover->authenticate();

		$Flyover->getUser();

		// Still no user?
		if (!$Flyover->user) {
			error($lang->sprintf($lang->flyover_error_no_user, $Flyover->provider));
		}

	}

	if ($mybb->request_method == "post") {

		verify_post_check($mybb->input['my_post_key']);

		$settingsToAdd = [];
		$settingsToCheck = $Flyover->getActiveUserfieldList();

		foreach ($settingsToCheck as $setting) {

			if ($mybb->input[$setting] == 1) {
				$settingsToAdd[$setting] = 1;
			}
			else {
				$settingsToAdd[$setting] = 0;
			}

		}

		$settingsToAdd = [
			$Flyover->provider . '_settings' => $settingsToAdd
		];

		// Register
		$registeredUser = $Flyover->register([
			'displayName' => $mybb->input['u_username'],
			'email' => $mybb->input['email'],
			'profile_fields' => $mybb->input['profile_fields']
		]);

		if (!$registeredUser['error']) {

			$Flyover->setUID($registeredUser['uid']);

			$Flyover->updateUserSettings($settingsToAdd);

			$Flyover->linkUser($registeredUser, $Flyover->user['identifier']);

			$Flyover->login($registeredUser);

			$Flyover->sync($registeredUser);

			$Flyover->redirect($mybb->input['redirect_url'], $lang->sprintf($lang->flyover_redirect_title, $registeredUser['username']), $lang->sprintf($lang->flyover_redirect_registered, $Flyover->provider));

		}
		else {
			$errors = $registeredUser['error'];
		}

	}

	if ($errors) {
		$errors = inline_error($errors);
	}

	// Fixes https://www.mybboost.com/thread-an-issue-add-custom-plugin-variable-to-flyover-register
	$plugins->run_hooks("member_register_start");

	$lang->load('member');
	// Custom profile fields - copied from MyBB 1.8.12, member.php, L885-L1095
	$pfcache = $cache->read('profilefields');
	if(is_array($pfcache))
	{
		foreach($pfcache as $profilefield)
		{
			if($profilefield['required'] != 1 && $profilefield['registration'] != 1 || !is_member($profilefield['editableby'], array('usergroup' => $mybb->user['usergroup'], 'additionalgroups' => $usergroup)))
			{
				continue;
			}
			$code = $select = $val = $options = $expoptions = $useropts = '';
			$seloptions = array();
			$profilefield['type'] = htmlspecialchars_uni($profilefield['type']);
			$thing = explode("\n", $profilefield['type'], "2");
			$type = trim($thing[0]);
			$options = $thing[1];
			$select = '';
			$field = "fid{$profilefield['fid']}";
			$profilefield['description'] = htmlspecialchars_uni($profilefield['description']);
			$profilefield['name'] = htmlspecialchars_uni($profilefield['name']);
			if($errors && isset($mybb->input['profile_fields'][$field]))
			{
				$userfield = $mybb->input['profile_fields'][$field];
			}
			else
			{
				$userfield = '';
			}
			if($type == "multiselect")
			{
				if($errors)
				{
					$useropts = $userfield;
				}
				else
				{
					$useropts = explode("\n", $userfield);
				}
				if(is_array($useropts))
				{
					foreach($useropts as $key => $val)
					{
						$seloptions[$val] = $val;
					}
				}
				$expoptions = explode("\n", $options);
				if(is_array($expoptions))
				{
					foreach($expoptions as $key => $val)
					{
						$val = trim($val);
						$val = str_replace("\n", "\\n", $val);
						$sel = "";
						if(isset($seloptions[$val]) && $val == $seloptions[$val])
						{
							$sel = ' selected="selected"';
						}
						eval("\$select .= \"".$templates->get("usercp_profile_profilefields_select_option")."\";");
					}
					if(!$profilefield['length'])
					{
						$profilefield['length'] = 3;
					}
					eval("\$code = \"".$templates->get("usercp_profile_profilefields_multiselect")."\";");
				}
			}
			elseif($type == "select")
			{
				$expoptions = explode("\n", $options);
				if(is_array($expoptions))
				{
					foreach($expoptions as $key => $val)
					{
						$val = trim($val);
						$val = str_replace("\n", "\\n", $val);
						$sel = "";
						if($val == $userfield)
						{
							$sel = ' selected="selected"';
						}
						eval("\$select .= \"".$templates->get("usercp_profile_profilefields_select_option")."\";");
					}
					if(!$profilefield['length'])
					{
						$profilefield['length'] = 1;
					}
					eval("\$code = \"".$templates->get("usercp_profile_profilefields_select")."\";");
				}
			}
			elseif($type == "radio")
			{
				$expoptions = explode("\n", $options);
				if(is_array($expoptions))
				{
					foreach($expoptions as $key => $val)
					{
						$checked = "";
						if($val == $userfield)
						{
							$checked = 'checked="checked"';
						}
						eval("\$code .= \"".$templates->get("usercp_profile_profilefields_radio")."\";");
					}
				}
			}
			elseif($type == "checkbox")
			{
				if($errors)
				{
					$useropts = $userfield;
				}
				else
				{
					$useropts = explode("\n", $userfield);
				}
				if(is_array($useropts))
				{
					foreach($useropts as $key => $val)
					{
						$seloptions[$val] = $val;
					}
				}
				$expoptions = explode("\n", $options);
				if(is_array($expoptions))
				{
					foreach($expoptions as $key => $val)
					{
						$checked = "";
						if(isset($seloptions[$val]) && $val == $seloptions[$val])
						{
							$checked = 'checked="checked"';
						}
						eval("\$code .= \"".$templates->get("usercp_profile_profilefields_checkbox")."\";");
					}
				}
			}
			elseif($type == "textarea")
			{
				$value = htmlspecialchars_uni($userfield);
				eval("\$code = \"".$templates->get("usercp_profile_profilefields_textarea")."\";");
			}
			else
			{
				$value = htmlspecialchars_uni($userfield);
				$maxlength = "";
				if($profilefield['maxlength'] > 0)
				{
					$maxlength = " maxlength=\"{$profilefield['maxlength']}\"";
				}
				eval("\$code = \"".$templates->get("usercp_profile_profilefields_text")."\";");
			}
			if($profilefield['required'] == 1)
			{
				// JS validator extra, choose correct selectors for everything except single select which always has value
				if($type != 'select')
				{
					if($type == "textarea")
					{
						$inp_selector = "$('textarea[name=\"profile_fields[{$field}]\"]')";
					}
					elseif($type == "multiselect")
					{
						$inp_selector = "$('select[name=\"profile_fields[{$field}][]\"]')";
					}
					elseif($type == "checkbox")
					{
						$inp_selector = "$('input[name=\"profile_fields[{$field}][]\"]')";
					}
					else
					{
						$inp_selector = "$('input[name=\"profile_fields[{$field}]\"]')";
					}
					$validator_extra .= "
					{$inp_selector}.rules('add', {
						required: true,
						messages: {
							required: '{$lang->js_validator_not_empty}'
						}
					});\n";
				}
				eval("\$requiredfields .= \"".$templates->get("member_register_customfield")."\";");
			}
			else
			{
				eval("\$customfields .= \"".$templates->get("member_register_customfield")."\";");
			}
		}
		if($requiredfields)
		{
			eval("\$requiredfields = \"".$templates->get("member_register_requiredfields")."\";");
		}
		if($customfields)
		{
			eval("\$customfields = \"".$templates->get("member_register_additionalfields")."\";");
		}
	}
	// End custom profile fields

	if ($requiredfields or $customfields) {
		eval("\$extrafields = \"".$templates->get("flyover_register_extrafields")."\";");
	}

	$options = '';
	$settingsToBuild = [];

	// Sync stuff
	$settingsToCheck = [
		'avatar',
		'sex',
		'bio',
		'location',
		'username'
	];

	foreach ($settingsToCheck as $setting) {

		if ($Flyover->provider_settings['settings'][$setting]) {
			$settingsToBuild[] = $setting;
		}

	}

	foreach ($settingsToBuild as $setting) {

		$tempKey = 'flyover_settings_' . $setting;
		$checked = " checked=\"checked\"";

		$label = $lang->$tempKey;
		$altbg = alt_trow();
		eval("\$options .= \"" . $templates->get('flyover_register_settings_setting') . "\";");

	}

	// Print this provider's data onto the language vars
	$lang_var_array = ['basic_info', 'title', 'what_to_sync', 'cannot_fetch_email'];
	$temp_prefix = 'flyover_register_';
	foreach ($lang_var_array as $l_var) {

		$temp = $temp_prefix . $l_var;

		$lang->$temp = $lang->sprintf($lang->$temp, $Flyover->provider);

	}

	// Registration errors fallback for username
	if ($mybb->input['u_username']) {
		$Flyover->user['displayName'] = htmlspecialchars_uni($mybb->input['u_username']);
	}

	$username = "<input type=\"text\" class=\"textbox\" name=\"u_username\" value=\"{$Flyover->user['displayName']}\" placeholder=\"Username\" />";
	$redirect_url = "<input type=\"hidden\" name=\"redirect_url\" value=\"{$_SERVER['HTTP_REFERER']}\" />";

	// Email&passwordless option is disabled
	if (!$mybb->settings['flyover_email_pw_less']) {

		$email = "<input type=\"text\" class=\"textbox\" name=\"email\" value=\"{$Flyover->user['email']}\" placeholder=\"Email\" />";

		// Registration errors fallback for email
		if ($mybb->input['email']) {
			$Flyover->user['email'] = htmlspecialchars_uni($mybb->input['email']);
		}

		// No email?
		$cannot_fetch_email = '';
		if (!$Flyover->user['email']) {
			$cannot_fetch_email = $lang->flyover_register_cannot_fetch_email;
		}

		eval("\$email_bit = \"" . $templates->get('flyover_register_email') . "\";");

	}

	// Output our page
	eval("\$register = \"" . $templates->get("flyover_register") . "\";");
	output_page($register);

}