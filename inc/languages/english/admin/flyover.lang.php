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
$l['setting_flyover_fastregistration_desc'] = "If this option is disabled, when an user wants to register with a social network he will be asked for permissions for your app if it's the first time he is logging in, otherwise he will be registered and logged in immediately without asking for username changes and what data to sync.";
$l['setting_flyover_passwordpm'] = "Send PM upon registration";
$l['setting_flyover_passwordpm_desc'] = "If this option is enabled, the user will be notified with a PM telling his randomly generated password upon his registration.";
$l['setting_flyover_passwordpm_subject'] = "PM subject";
$l['setting_flyover_passwordpm_subject_desc'] = "Choose a default subject to use in the generated PM.";
$l['setting_flyover_passwordpm_message'] = "PM message";
$l['setting_flyover_passwordpm_message_desc'] = "Write down a default message which will be sent to the registered users when they register with a social network. {user} and {password} are variables and the former refer to the username and the latter to the randomly generated password: they should be there even if you modify the default message. HTML and BBCode are permitted here. {provider} will be replaced with the actual provider an user has registered with.";
$l['setting_flyover_passwordpm_fromid'] = "PM sender";
$l['setting_flyover_passwordpm_fromid_desc'] = "Insert the UID of the user who will be the sender of the PM. By default it is set to 0 which is MyBB Engine, but you can change it to whatever you like.";
$l['setting_flyover_popup_mode'] = "Popup mode";
$l['setting_flyover_popup_mode_desc'] = "If this is enabled, the login page will be served as a popup without redirecting the entire page. This option is powered by plain JavaScript.";

$l['setting_flyover_login_box_type'] = "Login box style";
$l['setting_flyover_login_box_type_desc'] = "Choose the style you want the login box to appear. You can add <b>&lt;flyover_login_box&gt;</b> wherever you want in your theme's templates and it will be replaced with the login box.<br />
Some suggestions: <b>icons + text</b> and <b>buttons</b> look best when you are using few providers (4-5 or less) due to their relatively big dimensions. If you are using 5-6 or more providers, it is recommended to switch to the <b>icons only</b> mode.";
$l['setting_flyover_email_pw_less'] = "Email and passwordless";
$l['setting_flyover_email_pw_less_desc'] = "Enable this option to let your users login without an email and a password. The account will always have at least one provider linked, otherwise the user will be asked to setup an email and a password when they try to unlink their last provider. They can always set up an email and a password. Note that a random password IS NOT generated, so you must adjust the PM sent accordingly.";

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

// Default PM text
$l['flyover_default_passwordpm_subject'] = "New password";
$l['flyover_default_passwordpm_message'] = "Welcome on our Forums, dear {user}!

We appreciate that you have registered with {provider}. We have generated a random password for you which you should take note somewhere if you would like to change your personal infos. We require for security reasons that you specify your password when you change things such as the email, your username and the password itself, so keep it secret!

Your password is: [b]{password}[/b]

With regards,
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

// Bug reports
$l['flyover_reports'] = "Bug reports";
$l['flyover_reports_desc'] = "View and manage bug reports generated by Flyover's usage.";
$l['flyover_reports_date'] = "Date";
$l['flyover_reports_line'] = "Line";
$l['flyover_reports_file'] = "File";
$l['flyover_reports_code'] = "Code";
$l['flyover_reports_download'] = "Download";
$l['flyover_reports_delete'] = "Delete";
$l['flyover_reports_delete_all'] = "Delete all reports";

// Migration
$l['flyover_migration'] = "Migration";
$l['flyover_migration_plugin'] = "Plugin";
$l['flyover_migration_status'] = "Status";
$l['flyover_migration_no_plugins_available'] = "Currently there are no plugins enabled from which to perform a migration.";
$l['flyover_migration_migrate'] = "Migrate";
$l['flyover_migration_nextpage'] = "Migration in progress";
$l['flyover_migration_nextpage_notice'] = "Flyover successfully processed {3} users. <a href='index.php?module=config-flyover&amp;action=migration&amp;migrate={2}&amp;start={1}'>Click here</a> to continue processing the next {4} users.";
$l['flyover_migration_successful'] = "Flyover migrated successfully {1} users from {2}. You can now uninstall {2} safely!";
$l['flyover_migration_configure_provider'] = "<a href='index.php?module=config-flyover&action=add_provider&provider={1}'>Configure {1}</a> before migrating";

// Docs
$l['flyover_documentation'] = "Documentation";
$l['flyover_documentation_desc'] = "View the official docs of Flyover.";
$l['flyover_documentation_title'] = "Title";
$l['flyover_documentation_description'] = "Description";
$l['flyover_documentation_general'] = "Overview";
$l['flyover_documentation_general_desc'] = "Hello. This is a brief documentation on how to use and configure Flyover properly, getting the most out of your purchase.<br><br>
First of all, my name is Filippo, I am italian and I am a student in Medicine & Surgery with a lot of passion for everything that goes under my sight. I have been developing since I was a kid and I never stopped updating my knowledges in order to ensure the highest quality possible for my plugins and snippets. I've been focusing towards the authenticating protocols over the years, and managed to release 3 popular (and free) plugins to seamlessly integrate Facebook, Twitter and Google+ with MyBB. They have been downloaded more than 200,000 times and have been appreciated by countless users who still use them regularly for their projects. You have purchased my masterpiece, Flyover, which is basically the same thing as the plugins in the MyConnect serie, but on steroids.<br><br>
Flyover is a plugin which lets you integrate more than 20 social networks with your MyBB board. This is possible thanks to the OAuth protocol which is a standardized method to authenticate an user used by almost every important website in the world, such as Facebook, Twitter, Google and many others. I have chosen to use an Open Source project, <i>HybridAuth</i>, which works behind the scenes to exchange all the important stuff needed to authenticate users. You may now ask yourself: \"But why the hell should I pay for... Open Source software?\". HybridAuth does not come with a MyBB integration, it's just an intermediator between providers and your site, but you still have to develop an interface that lets you exploit HybridAuth's authentication methods. That's what Flyover does, you basically pay for the interface between MyBB and HybridAuth.<br><br>
Please, <b>do not redistribute this product in any way.</b> This is my only income and I am a student: some work in a shop, others may harvest grapes. I develop applications. This is a side job and I partly live with the money I make from this. If, for any reasons, you are caught redistributing this product I will immediately ban you from my site and I will not provide any assistance nor updates for your purchase. Thank you for your comprehension.";
$l['flyover_documentation_tokens'] = "Tokens";
$l['flyover_documentation_tokens_desc'] = "In order to know how to authenticate users, most of third party sites require you to create an application on their development subdomains. The application usually comes with two long alphanumeric strings, or <b>tokens</b>, designed to act as sort of 'usernames' and 'passwords' between your site and theirs. There are typically 3 kind of tokens:<br>
<ul>
	<li><b>Client ID</b>: this is the 'username' equivalent of your application;</li>
	<li><b>Secret</b>: this is the 'password' equivalent of your application;</li>
	<li><b>Key</b>: this can replace or integrate the secret token, and acts the same way.</li>
</ul>
Creating an application is easy as third party sites usually provide detailed instructions and a tour-structured navigation through their sites. If you still encounter some troubles, you can consult <a href='http://hybridauth.github.io/hybridauth/userguide.html#index'>HybridAuth's documentation</a> which provides step by step instructions to create the application you need. If a provider has a 'key' token and Flyover requires you to specify a 'secret' token, don't panic: it is not a mistake. It means that the provider lists the 'secret' token as 'key' but in the authenticating process it does need it labeled as 'secret', and due to Flyover's modularity feature I could not add provider-specific patches for this (still uncommon) behavior. Add the 'key' token in the 'secret' field and you are ready to go!<br><br>
Some providers, such as AOL and Steam, are powered by OpenID. They do not require an application, and you can enable them without doing anything else than activating them in Flyover's homepage.";
$l['flyover_documentation_sync'] = "Synchronization";
$l['flyover_documentation_sync_desc'] = "Several data points are available when authenticating an user with an external provider. Currently, these data points are available:<br>
<ul>
	<li>Avatar</li>
	<li>Cover (if Profile Picture plugin is installed)</li>
	<li>Biography</li>
	<li>Location</li>
	<li>Sex</li>
	<li>Website</li>
	<li>Identifier</li>
</ul>
You can choose whether to allow or not to import these data points from every provider's settings page. If you enable a data point, you give users the ability to choose whether to import their data upon every login with a particular provider. If you disable a data point, they will not be able to import that particular data point at all. Please note that the Cover data point works only if Profile Picture is installed (otherwise it will degrade gracefully without prompting any error to your users) and it is glued within the avatar option, meaning that this option controls the importing of both the avatar and the cover.<br><br>
The identifier has been added recently. This is particularly useful for specific providers such as Steam for which there are plenty of APIs based upon the user unique identifier. Flyover also uses identifiers to authenticate users, but it hashes and stores them in a separate table out of sight to prevent its editing or dumping by malicious users. I'd recommend using <a href='http://mybbhacks.zingaburga.com/showthread.php?tid=1271'>this awesome plugin</a> to set up a fully customized Custom Profile Field and handle the user identifier as you may like.";

// Errors
$l['flyover_error_not_ready'] = "This provider is not available to configure yet.";
$l['flyover_error_invalid_settings_file'] = "The uploaded file is not a valid Flyover settings file.";
$l['flyover_error_not_on_https'] = "This provider requires the redirect URL to have an https prefix, and you seem to be using a non-https website.";
$l['flyover_error_port_443_not_open'] = "A connection test has been made, and your server's 443 port seems to be closed. {1} needs port 443 open to communicate and authenticate users. If:<br /><br />
<li>you are running on a <b>dedicated or premium hosting</b>, you most probably have access to a port manager or something similar. You can easily open 443 port on TCP protocol by accessing the manager.</li>
<li>you are running on a <b>shared hosting</b>, or you don't have access to a port manager, you must contact your host and ask for port 443 to be opened for you. This is the only way to let your users login and register with {1}.</li>";
$l['flyover_error_no_reports'] = "There are not any reports generated by Flyover's usage. All systems are working well!";

// Success messages
$l['flyover_success_deleted_reports'] = "The bug report(s) have been deleted successfully.";

// Miscellaneous
$l['flyover_select_nofieldsavailable'] = "<span style='color: red'>There are no profile fields available. <b><a href='index.php?module=config-profile_fields'>Create one</a></b> to use this functionality.</span>";
$l['flyover_login_box_configure'] = "<span style='color: red'>There are no providers currently active. Configure one before using this functionality.</span>";