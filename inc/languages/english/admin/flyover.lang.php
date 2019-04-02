<?php

// Installation
$l['flyover'] = "Flyover";
$l['flyover_pluginlibrary_missing'] = "<a href=\"http://mods.mybb.com/view/pluginlibrary\">PluginLibrary</a> is missing. Please install it before doing anything else with Flyover.";

// Settings 
$l['setting_group_flyover'] = "Flyover Settings";
$l['setting_group_flyover_desc'] = "Here you can manage the whole stuff related to Flyover and integration with social networks. Manage API keys and options to enable or disable certain aspects of Flyover plugin.";
$l['setting_flyover_enable'] = "Master switch";
$l['setting_flyover_enable_desc'] = "Do you want to let your users login and register with the configured social networks?";
$l['setting_flyover_fastregistration'] = "One-click registration";
$l['setting_flyover_fastregistration_desc'] = "If this option is disabled, when an user wants to register with a social network he will be shown a (short) registration page, otherwise he will be registered and logged in immediately.";
$l['setting_flyover_passwordpm'] = "Send PM upon registration";
$l['setting_flyover_passwordpm_desc'] = "If this option is enabled, the user will be notified with a PM telling his randomly generated password upon his registration.";
$l['setting_flyover_passwordpm_subject'] = "PM subject";
$l['setting_flyover_passwordpm_subject_desc'] = "Choose a default subject to use in the generated PM.";
$l['setting_flyover_passwordpm_message'] = "PM message";
$l['setting_flyover_passwordpm_message_desc'] = "Write down a message which will be sent to users when they register using a social network. HTML and BBCode are allowed. The following variables are available:<br>
<br>
{user} = username<br>
{password} = randomly generated password (empty if passwordless mode is enabled)<br>
{provider} = provider an user has registered with";
$l['setting_flyover_passwordpm_fromid'] = "PM sender";
$l['setting_flyover_passwordpm_fromid_desc'] = "Specify the user identifier (uid) of the PM sender. Leave blank to use MyBB Engine (default).";
$l['setting_flyover_login_box_type'] = "Login box style";
$l['setting_flyover_login_box_type_desc'] = "Choose the style you want the login box to appear. You can add <b>&lt;flyover_login_box&gt;</b> wherever you want in your theme's templates and it will be replaced with the login box. Big buttons work best when you use few providers. If you use more than 4-5 providers, it is recommended to switch this to icons only.";
$l['setting_flyover_passwordless'] = "Passwordless mode";
$l['setting_flyover_passwordless_desc'] = "Enable this option to let your users register without a password, which IS NOT generated, so you must adjust the sent PM accordingly. When the user will unlink from his last provider he will be asked to set a password if not already set.";
$l['setting_flyover_keeprunning'] = "Force operational status";
$l['setting_flyover_keeprunning_desc'] = "Enable this option to let Flyover run even if registrations are disabled. This is particularly useful if you want to allow new registrations only with social networks.";

// Custom fields
$l['setting_flyover_locationfield'] = "Location Custom Profile Field";
$l['setting_flyover_locationfield_desc'] = "Select the Custom Profile Field that will be filled with providers' location.";
$l['setting_flyover_biofield'] = "Biography Custom Profile Field";
$l['setting_flyover_biofield_desc'] = "Select the Custom Profile Field that will be filled with providers' biography.";
$l['setting_flyover_sexfield'] = "Sex Custom Profile Field";
$l['setting_flyover_sexfield_desc'] = "Select the Custom Profile Field that will be filled with providers' sex.";
$l['setting_flyover_usernamefield'] = "Username Custom Profile Field";
$l['setting_flyover_usernamefield_desc'] = "Select the Custom Profile Field that will be filled with providers' username.";
$l['setting_flyover_websitefield'] = "Website Custom Profile Field";
$l['setting_flyover_websitefield_desc'] = "Select the Custom Profile Field that will be filled with providers' website.";
$l['setting_flyover_identifierfield'] = "Identifier Custom Profile Field";
$l['setting_flyover_identifierfield_desc'] = "Select the Custom Profile Field that will be filled with providers' identifier. <b>Note that usually you should NOT expose users identifiers in public places, although this does not pose any security threat.</b> Some providers might have third party libraries intended to work with their identifiers. Flyover lets you store the plain identifier into a Custom Profile Field which you can handle as you wish. Note that Flyover uses a separate database table to store the hashed identifier which is used to authenticate users.";
$l['setting_flyover_languagefield'] = "Language Custom Profile Field";
$l['setting_flyover_languagefield_desc'] = "Select the Custom Profile Field that will be filled with providers' language.";

// Default PM text
$l['flyover_default_passwordpm_subject'] = "New password";
$l['flyover_default_passwordpm_message'] = "Welcome on our Forums, dear {user}!

We appreciate that you have registered with {provider}. We have generated a random password for you which you should take note somewhere if you would like to change your personal infos. We require for security reasons that you specify your password when you change things such as the email, your username and the password itself, so keep it secret!

Your password is: [b]{password}[/b]

Best regards,
our Team";

// Errors
$l['flyover_error_needtoupdate'] = "You seem to have currently installed an outdated version of Flyover. Please <a href=\"index.php?module=config-settings&amp;update=flyover\">click here</a> to run the upgrade script.";
$l['flyover_error_nothingtodohere'] = "Ooops, Flyover is already up to date! Nothing to do here...";

// Success
$l['flyover_success_updated'] = "Flyover has been updated correctly from version {1} to {2}. Good job!";

/**
 * ACP Module
 */
$l['flyover_providers'] = "Providers";
$l['flyover_overview'] = "Overview";
$l['flyover_controls'] = "Other options";
$l['flyover_setup'] = "Configure";
$l['flyover_disable'] = "Disable";
$l['flyover_add'] = "Setup provider";
$l['flyover_active_providers'] = "Active providers";
$l['flyover_inactive_providers'] = "Inactive providers";
$l['flyover_homepage'] = "<div style='font-size: 23px'>Welcome to <b>Flyover</b></div>
Start configuring a provider by clicking on a brand icon below. <div style='padding: 10px 0'>";

// Rebuild cache
$l['flyover_cache'] = "Rebuild cache";
$l['flyover_cache_desc'] = "This process will rebuild Flyover's cache, grabbing saved tokens from the database and putting them in your cache engine.<br>
<small>Use this in case you can not login with some providers because tokens are not loaded. Although this tool should not produce errors, you may eventually experience Flyover's token loss. Use with caution.</small>";
$l['flyover_cache_rebuilt'] = "Cache has been rebuilt correctly.";

// Export
$l['flyover_export'] = "Export";

// Import
$l['flyover_import'] = "Import";
$l['flyover_import_settings'] = "Import settings";
$l['flyover_import_from_desc'] = "You can upload a Flyover generated settings file to restore your settings, including saved API tokens. It is usually saved as flyover-settings.xml.";
$l['flyover_import_from'] = "Upload a settings file";
$l['flyover_import_button'] = "Import";
$l['flyover_import_successful'] = "The settings have been imported successfully.";

// Setup
$l['flyover_setup_enabled'] = "Enabled";
$l['flyover_setup_enabled_desc'] = "Enable or disable the usage of this provider.";
$l['flyover_setup_id'] = "ID Token";
$l['flyover_setup_id_desc'] = "Specify the ID token for this provider.";
$l['flyover_setup_secret'] = "Secret Token";
$l['flyover_setup_secret_desc'] = "Specify the Secret token for this provider.";
$l['flyover_setup_key'] = "Key Token";
$l['flyover_setup_key_desc'] = "Specify the Key token for this provider.";
$l['flyover_setup_openid'] = "Powered by OpenID";
$l['flyover_setup_openid_desc'] = "This provider is powered by OpenID, an identification service that allows users to be authenticated without the need to register an application and messing around with tokens.";
$l['flyover_setup_usergroup'] = $l['setting_flyover_usergroup'] = "After registration usergroup";
$l['flyover_setup_usergroup_desc'] = $l['setting_flyover_usergroup_desc'] = "Select the after-registration usergroup. The user will be inserted directly into this usergroup upon registering. Also, if an existing user links his account to this provider, this usergroup will be added to his additional groups list.";
$l['flyover_setup_sync_options'] = "Synchronization options";
$l['flyover_setup_redirect_uri'] = "Redirect URI";
$l['flyover_setup_redirect_uri_desc'] = "Whilst creating a new application on <b>{1}</b>, use this value as the redirect URI, if and when asked.";
$l['flyover_setup_redirect_uri_alternative'] = "<br><br><span class='smalltext'>If the URI above does not work or is rejected, please try to use:</span><br><br>{1}/flyover.php";
$l['flyover_setup_scopes'] = "Scopes";
$l['flyover_setup_scopes_desc'] = "Overwrite the scopes of this provider which will be sent upon authentication requests. Check the provider's API documentation to know the available scopes. Note that some providers require scopes to be separated by comas, some with spaces, some with comas and spaces. Always refer to the API docs if unsure. If you don't know what scopes are, do not add anything to this value unless told you so.";
$l['flyover_setup_avatar'] = $l['setting_flyover_avatar'] = "Avatar";
$l['flyover_setup_avatar_desc'] = $l['setting_flyover_avatar_desc'] = "Decide to allow or not the import of the avatar from this social network.";
$l['flyover_setup_location'] = $l['setting_flyover_location'] = "Location";
$l['flyover_setup_location_desc'] = $l['setting_flyover_location_desc'] = "Decide to allow or not the import of the location from this social network.";
$l['flyover_setup_sex'] = $l['setting_flyover_sex'] = "Sex";
$l['flyover_setup_sex_desc'] = $l['setting_flyover_sex_desc'] = "Decide to allow or not the import of the sex from this social network.";
$l['flyover_setup_bio'] = $l['setting_flyover_bio'] = "Biography";
$l['flyover_setup_bio_desc'] = $l['setting_flyover_bio_desc'] = "Decide to allow or not the import of the biography from this social network.";
$l['flyover_setup_username'] = $l['setting_flyover_username'] = "Username";
$l['flyover_setup_username_desc'] = $l['setting_flyover_username_desc'] = "Decide to allow or not the import of the username from this social network.";
$l['flyover_setup_website'] = $l['setting_flyover_website'] = "Website";
$l['flyover_setup_website_desc'] = $l['setting_flyover_website_desc'] = "Decide to allow or not the import of the website from this social network.";
$l['flyover_setup_identifier'] = $l['setting_flyover_identifier'] = "Identifier";
$l['flyover_setup_identifier_desc'] = $l['setting_flyover_identifier_desc'] = "Decide to allow or not the import of the identifier from this social network.";
$l['flyover_setup_language'] = $l['setting_flyover_language'] = "Language";
$l['flyover_setup_language_desc'] = $l['setting_flyover_language_desc'] = "Decide to allow or not the import of the language from this social network.";
$l['flyover_setup_save'] = "Save";
$l['flyover_setup_complete_active'] = "You have successfully set up {1} integration with your board. Your users can now login and register with {1}. If you are using the <i>&lt;flyover_login_box&gt;</i> variable in your templates, the login button has been added automatically; if you do not use that variable, you may add the login button manually to your templates. Here's the HTML:<br><br>
<span style='padding-left: 40px'>&lt;a href='flyover.php?action=login&provider={1}'&gt;Login with {1}&lt;a&gt;</span><br><br>
You can replace <i>Login with {1}</i> with whatever you want (even with an image).";
$l['flyover_setup_complete_inactive'] = "You have successfully deactivated {1} integration with your board.";
$l['flyover_setup_complete_synced_usergroups'] = "Usergroups have been synced correctly.";

$l['flyover_status'] = "Status";
$l['flyover_general'] = "General";
$l['flyover_general_desc'] = "View and configure the available providers to login and register with them.";
$l['flyover_settings'] = "Settings";

// Migration
$l['flyover_migration'] = "Migration";
$l['flyover_migration_plugin'] = "Plugin";
$l['flyover_migration_status'] = "Status";
$l['flyover_migration_no_plugins_available'] = "Currently there are no plugins enabled from which to perform a migration.";
$l['flyover_migration_migrate'] = "Migrate";
$l['flyover_migration_nextpage'] = "Migration in progress";
$l['flyover_migration_nextpage_notice'] = "Flyover successfully processed {3} users. <a href='index.php?module=config-flyover&amp;action=migration&amp;migrate={2}&amp;start={1}'>Click here</a> to continue processing the next {4} users.";
$l['flyover_migration_successful'] = "Flyover migrated successfully {1} users from {2}. You can now uninstall {2} safely!";
$l['flyover_migration_configure_provider'] = "<a href='index.php?module=config-flyover&amp;action=manage&amp;provider={1}'>Configure {1}</a> before migrating";

// Errors
$l['flyover_error_not_ready'] = "This provider is not available to configure yet.";
$l['flyover_error_invalid_settings_file'] = "The uploaded file is not a valid Flyover settings file.";
$l['flyover_error_not_on_https'] = "This provider requires the redirect URL to have an https prefix, and you seem to be using a non-https website.";
$l['flyover_error_port_443_not_open'] = "A connection test has been made, and your server's 443 port seems to be closed. {1} needs port 443 open to communicate and authenticate users. If:<br /><br />
<li>you are running on a <b>dedicated or premium hosting</b>, you most probably have access to a port manager or something similar. You can easily open 443 port on TCP protocol by accessing the manager.</li>
<li>you are running on a <b>shared hosting</b>, or you don't have access to a port manager, you must contact your host and ask for port 443 to be opened for you. This is the only way to let your users login and register with {1}.</li>";
$l['flyover_error_no_reports'] = "There are not any reports generated by Flyover's usage. All systems are working well!";

// Miscellaneous
$l['flyover_select_nofieldsavailable'] = "<span style='color: red'>There are no profile fields available. <b><a href='index.php?module=config-profile_fields'>Create one</a></b> to use this functionality.</span>";
$l['flyover_login_box_configure'] = "<span style='color: red'>There are no providers currently active. Configure one before using this functionality.</span>";