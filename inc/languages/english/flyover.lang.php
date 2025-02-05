<?php

$l['flyover'] = "Flyover";
$l['flyover_login'] = "Login with Social Networks";

// Redirect messages
$l['flyover_redirect_loggedin'] = "You have successfully logged in with {1}.";
$l['flyover_redirect_registered'] = "You have successfully registered and logged in with {1}.";
$l['flyover_redirect_title'] = "Welcome, {1}!";

// Error messages
$l['flyover_error_noconfigfound'] = "You haven't configured Flyover plugin yet: either your Social Application ID or your Social Application Secret are missing. If you are an administrator, please read the instructions provided in the documentation.";
$l['flyover_error_noauth'] = "You didn't let us login with your {1} account. Please authorize our application from your {1} Application manager if you would like to login into our Forum.";
$l['flyover_error_report'] = "An unknown error occurred. The output of the error is:
<pre>
{1}
</pre>
Please report this error to an administrator and try again.";
$l['flyover_error_already_logged_in'] = "You are already logged into the board.";
$l['flyover_error_no_id_provided'] = "An unknown error occurred while fetching your data from {1}. Please report this error to an administrator.";
$l['flyover_error_no_provider'] = "You landed on this page without specifying a provider ID. You are probably using this feature unconventionally. Please do not attempt to use Flyover features out of their context.";
$l['flyover_error_no_user'] = "Flyover couldn't get an authenticated {1} user. Please do not attempt to use Flyover features out of their context.";
$l['flyover_error_unknown'] = "An unknown error occurred using Flyover.";
$l['flyover_error_linking'] = "There was an error linking your profile to {1}. Please try again.";
$l['flyover_error_need_to_change_email_password'] = "You cannot unlink your profile from {1} since it is the last social network associated to your account. You need to <a href='usercp.php?action=email_password'>set a password</a> if you want to unlink your account from {1}.";
$l['flyover_error_function_not_used_correctly'] = "You did not use this function properly. Please go back and try again.";
$l['flyover_error_unlinking_is_disabled'] = "Unlinking from providers is currently not allowed.";
$l['flyover_error_forced_login'] = "At present times logging in is allowed through social networks only.";
$l['flyover_error_forced_registration'] = "At present times registering is allowed through social networks only.";

// Success messages
$l['flyover_success_settings_updated'] = "Your social integration related settings have been updated correctly.";
$l['flyover_success_settings_updated_title'] = "Settings updated";
$l['flyover_success_linked'] = "Your account on this board has been correctly linked to your {1}'s one.";
$l['flyover_success_linked_title'] = "Account linked";
$l['flyover_success_unlinked'] = "Your {1} account has been unlinked successfully from your MyBB's one.";
$l['flyover_success_unlinked_title'] = "Account unlinked";
$l['flyover_success_synced'] = "Your account has been synced successfully with your {1}'s one.";
$l['flyover_success_synced_title'] = "Account synced";

// User Control Panel
$l['flyover_settings_title'] = $l['flyover_page_title'] = "Third party integration";
$l['flyover_settings_save'] = "Save";
$l['flyover_settings_sync'] = "Sync";
$l['flyover_settings_unlink'] = "Disconnect";
$l['flyover_settings_social_network'] = "Social network";
$l['flyover_settings_options_sync'] = "Synchronization options";
$l['flyover_settings_avatar'] = "Avatar";
$l['flyover_settings_sex'] = "Sex";
$l['flyover_settings_bio'] = "Bio";
$l['flyover_settings_location'] = "Location";
$l['flyover_settings_username'] = "Username";
$l['flyover_settings_website'] = "Website";
$l['flyover_settings_identifier'] = "Identifier";
$l['flyover_settings_language'] = "Language";
$l['flyover_settings_profileurl'] = "Profile URL";
$l['flyover_settings_email'] = "Email";
$l['flyover_settings_connected_with'] = 'Connected as <b>{1}</b>';
$l['flyover_settings_could_not_fetch'] = '<span class="name_not_present">Could not fetch your <b>{1}</b> name</span>';
$l['flyover_settings_link_providers'] = 'Click on one of the following providers to connect your account with it.';

// Registration page
$l['flyover_register_title'] = "Register with {1}";
$l['flyover_register_basic_info'] = "Choose your basic informations on your right. They are already filled with your {1} data, but you can change them accordingly. Your MyBB account will be linked to your {1}'s automatically.";
$l['flyover_register_what_to_sync'] = "Select your preferences about data synchronization. You can always change your preferences from your User Control Panel at a later stage.";
$l['flyover_register_username'] = "Username:";
$l['flyover_register_email'] = "Email:";
$l['flyover_register_email_already_registered'] = "Your {1} email matches the following registered account(s): {2}. It is advised to <a href='member.php?action=login'>log in</a> and link your existing account(s) instead of creating a new one.";
$l['flyover_register_cannot_fetch_email'] = "<img src='images/error.png' /> We could not fetch your {1} email, so you must specify it manually";
$l['flyover_register_custom_fields'] = "Please fill the following fields";

// Choose account
$l['flyover_choose_account_title'] = "Choose account";
$l['flyover_choose_account_desc'] = "Select one of the following accounts which are associated with your {1}'s. You will be logged in immediately.";
$l['flyover_choose_account_match_by_email'] = "Match by email";
$l['flyover_choose_account_match_by_id'] = "Match by {1} identifier";

// Login box
$l['flyover_login_box_connect_with'] = 'Or login with a social network below';
$l['flyover_login_with'] = 'Login with ';

// Popup mode
$l['flyover_popup_on_your_way_to'] = "On your way to ' + provider + '";
$l['flyover_popup_description'] = "We are redirecting you to ' + provider + ', where you will be asked to authorize our application in order to authenticate and log you in.";

// Who's Online
$l['flyover_viewing_logging_in'] = "<a href=\"flyover.php?action=login&provider={1}\">Logging in with {1}</a>";
$l['flyover_viewing_registering'] = "<a href=\"flyover.php?action=register&provider={1}\">Registering with {1}</a>";

// Miscellaneous
$l['flyover_male'] = "Male";
$l['flyover_female'] = "Female";
$l['logindata_flyoveremptypassword'] = "You did not enter a password. Please enter one. If your account does not have a password because you registered using a social network, please use the appropriate login links.";
$l['redirect_email_password_updated'] = "Your password has been successfully set.<br />You will be now returned to your email settings.";