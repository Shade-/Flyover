<?php

/**
 * Integrates MyBB with many social networks, featuring login and registration.
 *
 * @package Main API class
 * @version 1.5
 */

class Flyover
{
	public $flyover;
	public $flyover_settings = [];
	public $provider_settings = [];
	public $provider = '';
	public $lower_provider = '';
	public $parameters = [];
	public $user = [];

	public function __construct()
	{
		global $mybb, $lang;

		if (!$lang->flyover) {
			$lang->load('flyover');
		}

		$this->flyover_settings  = $this->readCache('settings');
		$this->provider          = htmlspecialchars_uni($mybb->input['provider']);
		$this->provider_settings = $this->flyover_settings[$this->provider];

		$this->lower_provider = strtolower($this->provider);

	}

	public function load($without_provider = false)
	{
		global $mybb, $lang;

		if ($this->flyover) {
			return false;
		}

		// There isn't any allowed provider to use
		if ((!$this->provider_settings or !$this->provider_settings['enabled'] or !$this->provider) and !$without_provider) {
			error($lang->flyover_error_no_provider);
		}

		$configuration = [
			'base_url' => $mybb->settings['bburl'] . '/flyover.php?auth=true'
		];

		// Build the keys
		$keys = [];

		if ($this->provider_settings['id']) {
			$keys['id'] = $this->provider_settings['id'];
		}

		if ($this->provider_settings['key_token']) {
			$keys['key'] = $this->provider_settings['key_token'];
		}

		if ($this->provider_settings['secret']) {
			$keys['secret'] = $this->provider_settings['secret'];
		}

		// Add the keys to the main array
		if ($keys) {
			$configuration['providers'][$this->provider] = [
				'keys' => $keys
			];
		}

		if (!$without_provider) {
			$configuration['providers'][$this->provider]['enabled'] = true;
		}

		// Debug mode, the hard way
		if ($mybb->input['debug_mode']) {

			$configuration['debug_mode'] = true;
			$configuration['debug_file'] = MYBB_ROOT . 'inc/plugins/Flyover/error_log.txt';

		}

		$_temp = [];

		// Provider-specific adjustments
		switch ($this->lower_provider) {

			case 'facebook':

				$_temp = [
					'trustForwarded' => false
				];

				break;

			// OpenID is not supported in the first version. Placing this here for future reference
			case 'openid':

				if (!$mybb->input['openid_identifier']) {
					error("OpenID Identifier missing.");
				}

				$this->parameters['openid_identifier'] = htmlspecialchars_uni($mybb->input['openid_identifier']);

				break;

		}

		$this->parameters['hauth_return_to'] = $this->getCallbackURI();

		if ($_temp) {
			$configuration['providers'][$this->provider] = array_merge($configuration['providers'][$this->provider], $_temp);
		}

		// Load API
		require_once MYBB_ROOT . 'inc/plugins/Flyover/hybridauth/Hybrid/Auth.php';

		try {
			$this->flyover = new Hybrid_Auth($configuration);
		}
		catch (Exception $e) {
			$this->generateReport($e);
		}

		// Set the internal uid
		$this->setUID($mybb->user['uid']);

		return true;
	}

	public function authenticate()
	{
		global $mybb;

		if (!$this->flyover) {
			return false;
		}

		// Build the adapter wrapper
		try {
			$this->adapter = $this->flyover->authenticate($this->lower_provider, $this->parameters);
		}
		catch (Exception $e) {
			$this->generateReport($e);
		}

		if ($mybb->settings['flyover_popup_mode'] and $mybb->input['popup']) {
			$mybb->input['login_success'] = 1;
		}

		return true;
	}

	public function getUser()
	{
		if (!$this->adapter) {
			return false;
		}

		// Get user profile
		try {
			$this->user = (array) $this->adapter->getUserProfile();
		}
		catch (Exception $e) {

			// If tokens are invalid, reask for permissions
			if (in_array($e->getCode(), [6, 7])) {

				try {
					$this->user = (array) $this->adapter->getUserProfile();
				}
				catch (Exception $e) {

					$this->logoutFromCurrentProvider();
					$this->generateReport($e);

				}

			} else {

				$this->logoutFromCurrentProvider();
				$this->generateReport($e);

			}

		}

		return true;
	}

	public function login($user = '')
	{
		global $mybb, $session, $db;

		if (!$user) {
			$user = $mybb->user;
		}

		if (!$user['uid'] or !$user['loginkey'] or !$session) {
			return false;
		}

		// Delete all the old sessions
		$db->delete_query("sessions", "ip='" . $db->escape_string($session->ipaddress) . "' and sid != '" . $session->sid . "'");

		// Create a new session
		$db->update_query("sessions", [
			"uid" => $user['uid']
		], "sid='" . $session->sid . "'");

		// Set up the login cookies
		my_setcookie("mybbuser", $user['uid'] . "_" . $user['loginkey'], null, true);
		my_setcookie("sid", $session->sid, -1, true);

		// Set up the internal uid
		$this->setUID($user['uid']);

		return true;
	}

	public function register($user)
	{
		if (!$user) {
			return false;
		}

		global $mybb, $session, $plugins, $lang;

		require_once MYBB_ROOT . "inc/datahandlers/user.php";
		$userhandler = new UserDataHandler("insert");

		$new_user = [
			"username" => htmlspecialchars_uni($user['displayName']),
			"regip" => $session->ipaddress,
			"profile_fields" => (array) $user['profile_fields'],
			"longregip" => my_ip2long($session->ipaddress),
			"options" => [
				"hideemail" => 1
			]
		];

		// ## Customizations ##
		// Password and email
		$plength = 8;
		if ($mybb->settings['minpasswordlength']) {
			$plength = (int) $mybb->settings['minpasswordlength'];
		}

		if (!$mybb->settings['flyover_email_pw_less']) {

			$new_user['password'] = random_str($plength, true); // Fixes https://www.mybboost.com/thread-password-is-empty-by-registration-with-twitch

			if ($user['email']) {
				$new_user['email'] = $new_user['email2'] = htmlspecialchars_uni($user['email']);
			}

		}

		// Usergroup
		$usergroup = 2;
		if ($mybb->settings['flyover_usergroup'] or $this->provider_settings['usergroup']) {

			$usergroup = $mybb->settings['flyover_usergroup'];

			if ($this->provider_settings['usergroup']) {
				$usergroup = $this->provider_settings['usergroup'];
			}

		}

		$new_user['usergroup'] = $usergroup;

		$userhandler->set_data($new_user);
		if ($userhandler->validate_user()) {

			global $user_info;

			$user_info = $userhandler->insert_user();

			$plugins->run_hooks("member_do_register_end");

			// Deliver a welcome PM
			if ($mybb->settings['flyover_passwordpm']) {

				require_once MYBB_ROOT . "inc/datahandlers/pm.php";
				$pmhandler                 = new PMDataHandler();
				$pmhandler->admin_override = true;

				// Make sure admins haven't done something bad
				$fromid = (int) $mybb->settings['flyover_passwordpm_fromid'];
				if (!$mybb->settings['flyover_passwordpm_fromid'] or !user_exists($mybb->settings['flyover_passwordpm_fromid'])) {
					$fromid = 0;
				}

				$message = $mybb->settings['flyover_passwordpm_message'];
				$subject = $mybb->settings['flyover_passwordpm_subject'];

				$thingsToReplace = [
					"{user}" => $user_info['username'],
					"{password}" => $new_user['password'], // Fixes https://www.mybboost.com/thread-no-password
					"{provider}" => $this->provider
				];

				// Replace what needs to be replaced
				foreach ($thingsToReplace as $find => $replace) {
					$message = str_replace($find, $replace, $message);
				}

				$pm = [
					"subject" => $subject,
					"message" => $message,
					"fromid" => $fromid,
					"toid" => [
						$user_info['uid']
					]
				];

				// Some defaults :)
				$pm['options'] = [
					"signature" => 1
				];

				$pmhandler->set_data($pm);

				// Now let the PM handler do all the hard work
				if ($pmhandler->validate_pm()) {
					$pmhandler->insert_pm();
				} else {
					error($lang->sprintf($lang->flyover_error_report, $pmhandler->get_friendly_errors()));
				}
			}

			// Finally return our new user data
			return $user_info;

		} else {
			return [
				'error' => $userhandler->get_friendly_errors(),
				'action' => 'register'
			];
		}

		return true;
	}

	public function process($uid = '')
	{
		global $mybb, $db, $lang;

		$user = $this->user;

		if (!$user['identifier']) {
			error($lang->sprintf($lang->flyover_error_no_id_provided, $this->provider));
		}

		// Used for backwards compatibility with the old id storing method
		$escaped_plain_id = $db->escape_string($user['identifier']);
		$id = md5(md5($user['identifier']).md5($this->provider));

		if ($user['email']) {
			$sql = " OR u.email = '" . $db->escape_string($user['email']) . "'";
		}

		if (is_int($uid) and $uid > 0) {
			$uid_check = "u.uid = '{$uid}' AND (";
			$uid_check_end = ")";
		}

		$prefix = TABLE_PREFIX;

		// Are you already with us?
		$sql   = <<<SQL
			SELECT u.*, m.{$this->provider}, m.{$this->provider}_settings
			FROM {$prefix}users u
			LEFT JOIN {$prefix}flyover_settings_data m ON m.uid = u.uid
			WHERE {$uid_check}m.{$this->provider} = '{$id}' OR m.{$this->provider} = '{$escaped_plain_id}'{$sql}{$uid_check_end}
SQL;
		$query = $db->write_query($sql);

		$accounts = [];

		while ($acc = $db->fetch_array($query)) {
			$accounts[] = $acc;
		}

		if ($uid_check and !$accounts) {
			return [
				'error' => 'function_misused',
				'action' => 'register'
			];
		}

		// If there's only one match, use one
		if (count($accounts) == 1) {
			return $this->proceedWithAuthentication($accounts[0]);
		}
		else if (!$accounts) {

			if (!$mybb->settings['flyover_fastregistration']) {
				return ['action' => 'register'];
			}

			$account = $this->register($user);

			if ($account['error']) {
				return $account;
			}
			else {

				// Set some defaults
				$toCheck = $this->getActiveUserfieldList();

				foreach ($toCheck as $setting) {

					$tempKey                = 'flyover_' . $setting;
					$new_settings[$setting] = $this->provider_settings[$tempKey];

				}

			}

			$new_settings = [
				$this->provider . '_settings' => $new_settings
			];

			$this->updateUserSettings($new_settings);
			$this->linkUser($account, $user['identifier']);

			return $this->proceedWithAuthentication($account, $lang->sprintf($lang->flyover_redirect_registered, $this->provider));

		}
		else {
			return [
				'action' => 'choose_account',
				'accounts' => $accounts
			];
		}

	}

	public function proceedWithAuthentication($account, $message = '')
	{
		global $mybb, $db, $lang;

		$user = $this->user;

		if (!$user['identifier']) {
			error($lang->sprintf($lang->flyover_error_no_id_provided, $this->provider));
		}

		// If a match is found but the identifier looks like the old one (== not hashed), we've got to update it [versions affected: 1.0, 1.1]
		if ($account[$this->provider] == $user['identifier']) {
			$this->updateUserProviderIdentifier($user['identifier']);
		}

		$message = ($message) ? $message : $lang->sprintf($lang->flyover_redirect_loggedin, $this->provider);

		// Link account if missing
		if ($user['email'] and $account['email'] == $user['email'] and !$account[$this->provider] and $user['identifier']) {
			$this->linkUser($account, $user['identifier']);
		}

		$title = $lang->sprintf($lang->flyover_redirect_title, $account['username']);

		$this->login($account);
		$this->sync($account);
		$this->redirect('callback', $title, $message);

		return true;
	}

	public function sync($user)
	{
		if (!$this->uid) {
			return false;
		}

		$data = $this->user;

		if (!$data) {
			return false;
		}

		global $mybb, $db, $session, $lang;

		$update    = [];
		$userfield = [];

		foreach ($this->getActiveUserfieldList() as $field) {

			$tempKey = $field . 'id';
			$$tempKey = ($mybb->settings['flyover_' . $field . 'field']) ? 'fid' . (int) $mybb->settings['flyover_' . $field . 'field'] : false;

		}

		$query = $db->simple_select("userfields", "ufid", "ufid = {$this->uid}");
		$check = $db->fetch_field($query, "ufid");

		$securesite = (strpos($mybb->settings['bburl'], 'https') !== false);

		// If the user hasn't got any field registered in the db, add it
		if (!$check) {
			$userfield['ufid'] = $this->uid;
		}

		// Get the setting this user has for this specific provider
		$user['internal_settings'] = (array) my_unserialize($user[$this->provider . '_settings']);
		unset($user[$this->provider . '_settings']);

		// Update user name for this provider
		$this->updateUserCurrentProviderName($data['displayName']);

		##
		## Start checking for settings and build the final array of data to import
		##

		// Avatar
		if ($user['internal_settings']['avatar'] and $data['photoURL']) {

			// Support for https (avoids mixed content)
			$skip = ($securesite and strpos($data['photoURL'], 'https') === false);

			if (!$skip) {

				list($maxwidth, $maxheight) = explode('x', my_strtolower($mybb->settings['maxavatardims']));

				$update["avatar"]     = $db->escape_string($data['photoURL']);
				$update["avatartype"] = "remote";

				// Copy the avatar to the local server (work around remote URL access disabled for getimagesize)
				$file     = fetch_remote_file($update["avatar"]);
				$tmp_name = $mybb->settings['avataruploadpath'] . "/remote_" . md5(random_str());
				$fp       = @fopen($tmp_name, "wb");

				if ($fp) {

					fwrite($fp, $file);
					fclose($fp);
					list($width, $height, $type) = @getimagesize($tmp_name);
					@unlink($tmp_name);

					if (!$type) {
						$avatar_error = true;
					}

				}

				if (!$avatar_error) {

					if ($width and $height and $mybb->settings['maxavatardims'] != "") {

						if (($maxwidth and $width > $maxwidth) or ($maxheight and $height > $maxheight)) {
							$avatardims = $maxheight . "|" . $maxwidth;
						}

					}

					if ($width > 0 and $height > 0 and !$avatardims) {
						$avatardims = $width . "|" . $height;
					}

					$update["avatardimensions"] = $avatardims;

				} else {
					$update["avatardimensions"] = $maxheight . "|" . $maxwidth;
				}

			}

		}

		// Cover, if Profile Picture plugin is installed
		if ($user['internal_settings']['avatar'] and $data['coverURL'] and $db->field_exists("profilepic", "users")) {

			// Support for https (avoids mixed content)
			$skip = ($securesite and strpos($data['photoURL'], 'https') === false);

			if (!$skip) {

				$update["profilepic"]     = $data['coverURL'];
				$update["profilepictype"] = "remote";

				if ($mybb->usergroup['profilepicmaxdimensions']) {

					list($maxwidth, $maxheight) = explode("x", my_strtolower($mybb->usergroup['profilepicmaxdimensions']));
					$update["profilepicdimensions"] = $maxwidth . "|" . $maxheight;

				}

			}

		}

		// Sex
		if ($user['internal_settings']['sex'] and $data['gender']) {

			if ($db->field_exists($sexid, "userfields")) {

				if ($data['gender'] == "male") {
					$userfield[$sexid] = $lang->flyover_male;
				} else if ($data['gender'] == "female") {
					$userfield[$sexid] = $lang->flyover_female;
				}

			}
		}

		// Username
		if ($usernameid and $user['internal_settings']['username'] and $data['displayName']) {

			if ($db->field_exists($usernameid, "userfields")) {
				$userfield[$usernameid] = $db->escape_string($data['displayName']);
			}

		}

		// Bio
		if ($user['internal_settings']['bio'] and $data['description']) {

			if ($db->field_exists($bioid, "userfields")) {
				$userfield[$bioid] = $db->escape_string(htmlspecialchars_decode(my_substr($data['description'], 0, 400, true)));
			}

		}

		// Location
		if ($user['internal_settings']['location'] and $data['country']) {

			if ($db->field_exists($locationid, "userfields")) {
				$userfield[$locationid] = $db->escape_string($data['country']);
			}

		}

		// Website
		if ($user['internal_settings']['website'] and $data['webSiteURL']) {
			$update['website'] = $db->escape_string($data['country']);
		}

		// Identifier
		if ($user['internal_settings']['identifier'] and $data['identifier']) {

			if ($db->field_exists($identifierid, "userfields")) {
				$userfield[$identifierid] = $db->escape_string($data['identifier']);
			}

		}

		// Update profile
		if ($update) {
			$query = $db->update_query("users", $update, "uid = {$this->uid}");
		}

		// Update userfields
		if ($userfield) {

			if ($userfield['ufid']) {
				$query = $db->insert_query("userfields", $userfield);
			} else {
				$query = $db->update_query("userfields", $userfield, "ufid = {$this->uid}");
			}

		}

		// Clear the queue
		$this->performUserUpdate();

		// Close popup if using popup mode
		$this->closePopup();

		return true;
	}

	public function linkUser($user = '', $id)
	{
		global $mybb, $db;

		if (!$id) {
			return false;
		}

		if (!$user) {
			$user = $mybb->user;
		}

		// Still no user?
		if (!$user) {
			return false;
		}

		// Hash the identifier (ensures maximum privacy for users in case of database dumps from hackers and standardizes identifiers to a default length)
		$id = md5(md5($id).md5($this->provider));

		$prefix = TABLE_PREFIX;

		$query = <<<SQL
			INSERT INTO {$prefix}flyover_settings_data ({$this->provider}, uid)
			VALUES ('{$id}', {$user['uid']})
			ON DUPLICATE KEY UPDATE {$this->provider} = '{$id}'
SQL;

		$db->write_query($query);

		// Add to the usergroup
		if ($mybb->settings['flyover_usergroup'] or $this->provider_settings['usergroup']) {

			$usergroup = $mybb->settings['flyover_usergroup'];
			if ($this->provider_settings['usergroup'] and $this->provider_settings['usergroup'] != 2) {
				$usergroup = $this->provider_settings['usergroup'];
			}

			if (!$usergroup) {
				$usergroup = 2;
			}

			$this->joinUsergroup($user, $usergroup);

		}

		return true;
	}

	public function unlinkUser($user = '')
	{
		global $mybb, $db;

		if (!$user) {
			$user = $mybb->user;
		}

		// Still no user?
		if (!$user) {
			return false;
		}

		// Remove user provider name
		$usernames = $this->getUsernames();

		unset($usernames[$this->provider]);

		$update = [
			$this->provider => '',
			'usernames' => serialize($usernames)
		];

		$db->update_query("flyover_settings_data", $update, "uid = {$this->uid}");

		// Remove from the usergroup
		if ($mybb->settings['flyover_usergroup']) {

			$usergroup = $mybb->settings['flyover_usergroup'];
			if ($this->provider_settings['usergroup']) {
				$usergroup = $this->provider_settings['usergroup'];
			}

			$this->leaveUsergroup($user, $usergroup);

		}

		// Remove his data from HybridAuth
		if ($this->flyover->getAdapter($this->provider)) {
			$this->flyover->getAdapter($this->provider)->logout();
		}

		return true;
	}

	public function joinUsergroup($user, $gid)
	{
		global $mybb, $db;

		if (!$gid) {
			return false;
		}

		if (!$user) {
			$user = $mybb->user;
		}

		if (!$user) {
			return false;
		}

		$gid = (int) $gid;

		// Is this user already in that group?
		if ($user['usergroup'] == $gid) {
			return false;
		}

		$groups = explode(",", $user['additionalgroups']);

		if (!in_array($gid, $groups)) {

			$groups[] = $gid;
			$update   = [ 
				"additionalgroups" => implode(",", array_filter($groups))
			];
			$db->update_query("users", $update, "uid = {$user['uid']}");

		}

		return true;
	}

	public function leaveUsergroup($user, $gid)
	{
		global $mybb, $db;

		if (!$gid) {
			return false;
		}

		if (!$user) {
			$user = $mybb->user;
		}

		if (!$user) {
			return false;
		}

		$gid = (int) $gid;

		// If primary group coincide, just return
		if ($user['usergroup'] == $gid) {
			return false;
		}

		$groups = (array) explode(",", $user['additionalgroups']);

		if (in_array($gid, $groups)) {

			// Flip the array so we have gid => keys
			$groups = array_flip($groups);

			unset($groups[$gid]);

			// Restore the array flipping it again (and filtering it)
			$groups = array_filter(array_flip($groups));

			$update = [
				"additionalgroups" => implode(",", $groups)
			];

			$db->update_query("users", $update, "uid = {$user['uid']}");

		}

		return true;
	}

	public function getUserSettings()
	{
		global $db;

		$query = $db->simple_select('flyover_settings_data', $this->provider . '_settings', "uid = {$this->uid}");

		return $db->fetch_field($query, $this->provider . '_settings');
	}

	public function getUsernames()
	{
		global $db;

		if (!$this->uid) {
			return false;
		}

		$query = $db->simple_select('flyover_settings_data', 'usernames', "uid = {$this->uid}");

		return (array) my_unserialize($db->fetch_field($query, 'usernames'));
	}

	public function updateUserSettings($settings)
	{	
		foreach ($settings as $key => $value) {

			if (is_array($value)) {
				$this->internal_data[$key] = serialize($value);
			}

		}
	}

	public function updateUserCurrentProviderName($name)
	{	
		$usernames = $this->getUsernames($uid);

		if ($name == $usernames[$this->provider]) {
			return true;
		}

		$usernames[$this->provider] = htmlspecialchars_uni($name);

		$usernames = array_filter($usernames);

		$this->internal_data['usernames'] = serialize($usernames);
	}

	public function updateUserProviderIdentifier($id)
	{
		$this->internal_data[$this->provider] = md5(md5($id).md5($this->provider));
	}

	public function performUserUpdate()
	{
		if ($this->internal_data) {

			global $db;

			$db->update_query('flyover_settings_data', $this->internal_data, "uid = {$this->uid}");

		}
	}

	public function setUID($uid = '')
	{
		global $mybb;

		if (!$uid) {
			$uid = $mybb->user['uid'];
		}

		$this->uid = (int) $uid;
	}

	public function getCallbackURI()
	{
		if ($this->callbackURI) {
			return $this->callbackURI;
		}

		global $mybb;

		$url = parse_url($_SERVER['REQUEST_URI']);

		$this->callbackURI = 'http';

		if ($_SERVER["HTTPS"] == "on") {
			$this->callbackURI .= "s";
		}

		$this->callbackURI .= "://";

		if ($_SERVER["SERVER_PORT"] != "80") {
			$this->callbackURI .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
		} else {
			$this->callbackURI .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
		}

		// Check if parameters are already present
		$query_string_character = '?';
		if (isset($url['query'])) {
			$query_string_character = '&';
		}

		if ($mybb->settings['flyover_popup_mode'] and $mybb->input['popup']) {
			$this->callbackURI .= $query_string_character . 'login_success=1';
		}

		return $this->callbackURI;
	}

	public function closePopup($url = '', $type = '', $title = '', $message = '')
	{
		global $mybb;

		if ($mybb->settings['flyover_popup_mode'] and $mybb->input['popup']) {

			if (!$url or $message) {
				$snippet = 'window.opener.location.reload();';
			}
			else {
				$snippet = "window.opener.location.href = '{$mybb->settings['bburl']}/{$url}'";
			}

			if ($message) {
				$this->loadDataInPHPSession($type, $title, $message);
			}

			echo "<script type='text/javascript'>

	            window.close();

	            {$snippet}

	            </script>";

	        // Exit immediately
	        if ($type == 'exit') {
		        exit;
	        }
	        else {
		        $this->popup_closed = true;
	        }

		}

		return true;
	}

	public function loadDataInPHPSession($type = '', $title = '', $message = '')
	{
		if (!$type) {
			return false;
		}

		if (!session_id()) {
			session_start();
		}

		$_SESSION['flyover'] = [
			'type' => $type,
			'message' => $message,
			'title' => $title
		];

		return true;
	}

	public function redirect($url = '', $title = '', $message = '')
	{
		if ($this->popup_closed) {

			$this->loadDataInPHPSession('success', $title, $message);

			exit;

		}

		if (!session_id()) {
			session_start();
		}

		if (!$url and $_SESSION['flyover']['return_to_page']) {
			$url = $_SESSION['flyover']['return_to_page'];
		}
		else if (!$url or $url == 'callback') {
			$url = $this->callbackURI;
		}

		if (strpos($url, "flyover.php") === false and strpos($url, "action=login") === false) {
			$url = htmlspecialchars_uni($url);
		}
		else {
			$url = "index.php";
		}

		redirect($url, $message, $title);

		return true;
	}

	public function logoutFromCurrentProvider()
	{
		if (!$this->adapter) {
			return false;
		}

		return $this->adapter->logout();
	}

	public function logoutFromAllProviders()
	{
		if (!$this->flyover) {
			return false;
		}

		return $this->flyover->logoutAllProviders();
	}

	public function userIsConnectedWith($provider = '')
	{
		if (!$this->flyover or !$provider) {
			return false;
		}

		return $this->flyover->isConnectedWith($provider);
	}

	public function getEnabledProviders()
	{
		return $this->readCache('settings', 'enabled');
	}

	public function getUserEnabledProviders()
	{
		global $db;

		if (!$this->uid) {
			return false;
		}

		$query = $db->simple_select('flyover_settings_data', "*", "uid = {$this->uid}");
		$user = $db->fetch_array($query);

		unset ($user['uid'],
			   $user['usernames']);

		$user = array_filter($user);

		foreach ($user as $key => $value) {

			if (strpos($key, '_settings') !== false) {
				unset($user[$key]);
			}

		}

		return (array) $user;

	}

	public function updateCache($table, $provider, $params = [])
	{
		global $db, $PL, $flyover_settings;

		$PL or require_once PLUGINLIBRARY;

		if (!$params or !$provider or !$table) {
			return false;
		}

		$table = 'flyover_' . $table;

		// Cache content
		$content = $PL->cache_read($table);

		$content[$provider] = $params;

		// Escape/serialize things before inserting into the db
		foreach ($params as $key => $param) {
			if (is_array($param)) {
				$params[$key] = serialize($param);
			}
			else {
				$params[$key] = $db->escape_string($param);
			}
		}

		// DB fallback
		if ($flyover_settings['provider'] == $provider) {
			$db->update_query($table, $params, "provider = '$provider'");
		} else {
			$db->insert_query($table, $params);
		}

		return $PL->cache_update($table, $content);

	}

	public function readCache($table, $key = '')
	{
		global $PL;

		$PL or require_once PLUGINLIBRARY;

		if (!$table) {
			return false;
		}

		$table = 'flyover_' . $table;

		$content = (array) $PL->cache_read($table);

		if ($key == 'enabled') {
			return array_filter($content, function($a)
			{
				if ($a['enabled']) {
					return $a;
				}
			});
		}
		else if ($key) {
			return (array) $content[$key];
		}
		else {
			return $content;
		}
	}

	public function rebuildCache($table)
	{
		global $db, $PL;

		$PL or require_once PLUGINLIBRARY;

		if (!$table) {
			return false;
		}

		$settings = [];

		$table = 'flyover_' . $table;

		$query = $db->simple_select($table, '*');

		while ($setting = $db->fetch_array($query)) {

			$setting['settings'] = (array) my_unserialize($setting['settings']);

			$settings[$setting['provider']] = $setting;

		}

		return $PL->cache_update($table, $settings);

	}

	public function rememberCurrentPage()
	{	
		if (!session_id()) {
			session_start();
		}

		$_SESSION['flyover']['return_to_page'] = $_SERVER['HTTP_REFERER'];

		return true;
	}

	public function getActiveUserfieldList($excludeProtected = false)
	{	
		return flyover_get_active_userfield_list($excludeProtected);
	}

	public function generateReport($e)
	{
		global $db, $lang, $mybb;

		$report = [
			'dateline' => TIME_NOW,
			'code' => (int) $e->getCode(),
			'file' => $db->escape_string($e->getFile()),
			'line' => (int) $e->getLine(),
			'message' => $db->escape_string($e->getMessage()),
			'trace' => $db->escape_string($e->getTraceAsString())
		];

		$db->insert_query('flyover_reports', $report);

		if ($mybb->settings['flyover_popup_mode']) {
			$this->closePopup('', 'error', '', $lang->flyover_error_report_generated);
		}
		else {
			error($lang->flyover_error_report_generated);
		}

		return true;
	}

}