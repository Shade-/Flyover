<?php

/**
 * Flyover
 */

define("IN_MYBB", 1);
define('THIS_SCRIPT', 'flyover.php');
define('ALLOWABLE_PAGE', 'login,choose_account,register');

include './global.php';
include FLYOVER;

use Flyover\Flyover;
use Flyover\Helper;
use Flyover\Session\Redirect;
use Flyover\User\Update;
use Hybridauth\Storage\Session;
use Hybridauth\Profile\User;

$lang->load('flyover');

if (!$mybb->settings['flyover_enabled']) {

    header("Location: index.php");
    exit;

}

// Registrations are disabled
if ($mybb->settings['disableregs'] == 1) {

    if (!$lang->registrations_disabled) {
        $lang->load("member");
    }

    error($lang->registrations_disabled);

}

// If the user is watching another page, fallback to login
if (!in_array($mybb->input['action'], explode(',', ALLOWABLE_PAGE))) {
    $mybb->input['action'] = 'login';
}

$settingsToAdd = [];

// Begin the authenticating process
if ($mybb->input['action'] == 'login') {

    $flyover = new Flyover();
    $localSession = new Session();
    $redirect = new Redirect();

    if ($flyover->provider) {
        $localSession->set('provider', $flyover->provider);
    }

    // Build the adapter wrapper
    try {

        if ($provider = $localSession->get('provider')) {

            $flyover->authenticate($provider);
            $localSession->set('provider', null);

        }

    }
    catch (\Exception $e) {
        error($e->getMessage());
    }

    $profile = $flyover->getUserProfile();

    if (!$profile->identifier) {
        error($flyover->adapter->getHttpClient()->getResponseBody());
    }

    // Redirect if already logged in, this user is coming from UserCP
    if ($mybb->user['uid']) {
        $redirect->toCallback();
    }

    // Attempt to get accounts matched with this user's identifier.
    // Email direct match is dropped in favor of a notice in registration page,
    // as some providers (such as Discord, shame on you!) do not require mail verification to log in
    $accounts = $flyover->getMatchedAccounts($profile->identifier);

    // Register
    if (empty($accounts)) {

        if ($mybb->request_method == 'post' or $mybb->settings['flyover_fastregistration']) {

            if ($mybb->request_method == 'post') {
                verify_post_check($mybb->input['my_post_key']);
            }

            $settingsToCheck = Helper\Utilities::getUserfields();

            foreach ($settingsToCheck as $setting) {

                // !isset() is normal. If fast registration is on, $mybb->input won't be populated.
                // We default to 1.
                if (
                    $mybb->input['syncOptions'][$setting] == 1 or 
                    (!isset($mybb->input['syncOptions'][$setting]) and $mybb->settings['flyover_fastregistration'])
                ) {
                    $settingsToAdd[$setting] = 1;
                }
                else {
                    $settingsToAdd[$setting] = 0;
                }

            }

            $username = ($mybb->input['name']) ? $mybb->input['name'] : $profile->displayName;
            $email = ($mybb->input['email']) ? $mybb->input['email'] : $profile->email;

            // Registration
            try {

                $attempt = $flyover->user->register([
                    'username' => $username,
                    'email' => $email,
                    'profile_fields' => $mybb->input['profile_fields']
                ]);

                // Merging this now will allow synchronization later on
                $attempt = array_merge($attempt, [$flyover->provider . '_settings' => $settingsToAdd]);

                $accounts = [$attempt];

                $message = $lang->sprintf($lang->flyover_redirect_registered, $flyover->provider);

            }
            catch (\Exception $e) {
                $errors = explode('###', $e->getMessage());
            }

        }

        // Page
        if ((!$mybb->settings['flyover_fastregistration'] and $mybb->request_method != 'post') or $errors) {

            if ($errors) {
                $errors = inline_error($errors);
            }

            // Fixes https://www.mybboost.com/thread-an-issue-add-custom-plugin-variable-to-flyover-register
            $plugins->run_hooks("member_register_start");

            $lang->load('member');

            /****************************************************************
             *                                                              *
             * Custom profile fields - copied from MyBB 1.8.19, member.php  *
             *                                                              *
             ****************************************************************/
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
                            $validator_javascript .= "
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
            /*****************************
             *                           *
             * End custom profile fields *
             *                           *
             *****************************/

            if ($requiredfields or $customfields) {
                eval("\$extrafields = \"".$templates->get("flyover_register_extrafields")."\";");
            }

            $options = '';

            // Synchronization optouts
            $settingsToCheck = Helper\Utilities::getUserfields();

            foreach ($settingsToCheck as $setting) {

                if ($flyover->settings[$flyover->provider]['settings'][$setting]) {

                    $tempKey = 'flyover_settings_' . $setting;
                    $checked = ($mybb->input['syncOptions'][$setting] or $mybb->request_method != 'post') ? " checked=\"checked\"" : '';

                    $label = $lang->$tempKey;
                    $altbg = alt_trow();
                    eval("\$options .= \"" . $templates->get('flyover_register_settings_setting') . "\";");

                }

            }

            // Print this provider's data onto the language vars
            $languageTitles = ['basic_info', 'title', 'what_to_sync', 'cannot_fetch_email'];
            $prefix = 'flyover_register_';

            foreach ($languageTitles as $var) {

                $temp = $prefix . $var;
                $lang->$temp = $lang->sprintf($lang->$temp, $flyover->provider);

            }

            if (!$mybb->input['name']) {
                $mybb->input['name'] = htmlspecialchars_uni($profile->displayName);
            }

            if (!$mybb->input['email']) {
                $mybb->input['email'] = htmlspecialchars_uni($profile->email);
            }

            // No email?
            $cannot_fetch_email = (!$profile->email) ? $lang->flyover_register_cannot_fetch_email : '';

            // Check for email matches and show notice
            $emailAlreadyRegistered = '';
            if ($profile->email) {

                $alreadyRegistered = [];

                $query = $db->simple_select('users', 'username', "email = '" . $db->escape_string($profile->email) . "'");
                while($name = $db->fetch_field($query, 'username')) {
                    $alreadyRegistered[] = $name;
                }

                if ($alreadyRegistered) {

                    $emailAlreadyRegistered = $lang->sprintf(
                        $lang->flyover_register_email_already_registered,
                        $flyover->provider,
                        implode(', ', $alreadyRegistered)
                    );

                }

            }

            // Output our page
            eval("\$register = \"" . $templates->get("flyover_register") . "\";");
            output_page($register);

            exit;

        }

    }

    // Choose account
    if (count($accounts) > 1) {

        $lang->flyover_choose_account_desc = $lang->sprintf(
            $lang->flyover_choose_account_desc,
            $flyover->provider
        );

        $multipleAccounts = '';
        foreach ($accounts as $user) {

            $username = format_name($user['username'], $user['usergroup'], $user['displaygroup']);

            $matchType = ($user[$flyover->provider])
                ? $lang->sprintf($lang->flyover_choose_account_match_by_id, $flyover->provider)
                : $lang->flyover_choose_account_match_by_email;

            eval("\$multipleAccounts .= \"" . $templates->get("flyover_choose_account_user") . "\";");

        }

        eval("\$chooseAccount = \"" . $templates->get("flyover_choose_account") . "\";");
        output_page($chooseAccount);

        exit;

    }
    else {
        $account = (array) reset($accounts);
    }

    if (!$account) {
        error($lang->flyover_error_unknown);
    }

    // Login
    $flyover->user->load($account);

    // Link account if still missing, and eventually join usergroup
    if (!$account[$flyover->provider] and $profile->identifier) {

        $flyover->user->link($profile->identifier);

        $usergroup = (int) $flyover->settings['usergroup'] ?? (int) $mybb->settings['flyover_usergroup'] ?? 2;

        if ($account['usergroup'] != $usergroup) {
            $flyover->usergroup->join($usergroup);
        }

    }
    // If a match is found but the identifier looks like the old one (== not hashed),
    // update it [versions affected: 1.0, 1.1]
    else if ($account[$flyover->provider] == $profile->identifier) {
        $flyover->user->update->loginIdentifier($profile->identifier);
    }

    // Add sync options
    if ($settingsToAdd) {
        $flyover->user->update->settings($settingsToAdd);
    }

    $title = $lang->sprintf($lang->flyover_redirect_title, $account['username']);
    $message = $message ?? $lang->sprintf($lang->flyover_redirect_loggedin, $flyover->provider);

    $flyover->user->login();
    $flyover->user->synchronize($profile);

    $redirect->show($title, $message);

    exit;

}