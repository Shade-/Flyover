<?php

namespace Flyover\User;

use Flyover\Helper;

class User
{
	use \Flyover\Helper\MybbTrait;

	protected $user = [];
	protected $settings;

	public $update;

	public function __construct(
		$user = []
	)
	{
		$this->traitConstruct();

		$this->providerSettings = $this->settings[$this->provider];
		$this->syncOptions = $this->providerSettings['settings'];

		$this->get = new Collect($user);

		return $this->load($user);
	}

	public function load(array $user = [])
	{
		if (!$user['uid']) {
			return false;
		}

		$this->user = $user;

		$query = $this->db->simple_select(
			'flyover_users',
			'*',
			'uid = ' . (int) $this->user['uid'],
			['limit' => 1]
		);
		$this->user += (array) $this->db->fetch_array($query);

		return $this->update = new Update($this->user);
	}

	public function synchronize($profile)
	{
		if (!$this->user or !$profile) {
			throw new \Exception('No user is available to synchronize.');
		}

		// Get this provider's settings for the current user
		$userSettings = !is_array($this->user[$this->provider . '_settings'])
		    ? (array) my_unserialize($this->user[$this->provider . '_settings'])
		    : $this->user[$this->provider . '_settings'];

		$this->update->customFieldsIdentifier();
		$this->update->providerUsername($profile->displayName);

		// Avatar and cover
		if ($this->syncOptions['avatar'] and $userSettings['avatar']) {

			$this->update->avatar($profile->photoURL);

			if ($this->db->field_exists("profilepic", "users")) {
				$this->update->cover($profile->coverURL);
			}

		}

		// Sex
		if ($this->syncOptions['sex'] and $userSettings['sex']) {

			if ($profile->gender == "male") {
				$this->update->standardField('sex', $this->lang->flyover_male);
			}
			else if ($profile->gender == "female") {
				$this->update->standardField('sex', $this->lang->flyover_female);
			}

		}

		$standardFields = [
			'username' => $profile->displayName,
			'bio' => htmlspecialchars_decode(my_substr($profile->description, 0, 400, true)),
			'location' => $profile->country,
			'website' => $profile->webSiteURL,
			'identifier' => $profile->identifier,
			'language' => $profile->language,
			'email' => $profile->email,
			'profileurl' => $profile->profileURL
		];

		foreach ($standardFields as $name => $value) {

			if ($this->syncOptions[$name] and $userSettings[$name]) {
				$this->update->standardField($name, $value);
			}

		}

		// Finally update
		return $this->update->finalize();
	}

	public function register($user = [])
	{
		if (!$user) {
			throw new \Exception('No user is available to register.');
		}

		require_once MYBB_ROOT . "inc/datahandlers/user.php";
		$userhandler = new \UserDataHandler("insert");

		$newUser = [
			"username" => htmlspecialchars_uni($user['username']),
			"regip" => $this->session->packedip,
			"profile_fields" => (array) $user['profile_fields'],
			"options" => [
				"hideemail" => 1
			]
		];

		// Password and email
		$plength = 8;
		if ($this->mybb->settings['minpasswordlength']) {
			$plength = (int) $this->mybb->settings['minpasswordlength'];
		}

		if ($user['email']) {
			$newUser['email'] = $newUser['email2'] = htmlspecialchars_uni($user['email']);
		}

		// Fixes https://www.mybboost.com/thread-password-is-empty-by-registration-with-twitch
		if (!$this->mybb->settings['flyover_passwordless']) {
			$newUser['password'] = random_str($plength, true);
		}

		$newUser['usergroup'] = (int) $this->providerSettings['usergroup'] ?? (int) $this->mybb->settings['flyover_usergroup'] ?? 2;

		$userhandler->set_data($newUser);
		if ($userhandler->validate_user()) {

			global $user_info, $plugins;

			$user_info = $userhandler->insert_user();

			$plugins->run_hooks("member_do_register_end");

			// Deliver a welcome PM
			if ($this->mybb->settings['flyover_passwordpm']) {

				require_once MYBB_ROOT . "inc/datahandlers/pm.php";
				$pmhandler                 = new \PMDataHandler();
				$pmhandler->admin_override = true;

				// Make sure admins haven't done something bad
				$fromUid = (int) $this->mybb->settings['flyover_passwordpm_fromid'];
				if (!$this->mybb->settings['flyover_passwordpm_fromid']
					or !user_exists($this->mybb->settings['flyover_passwordpm_fromid'])) {
					$fromUid = 0;
				}

				$subject = $this->mybb->settings['flyover_passwordpm_subject'];

				$thingsToReplace = [
					"{user}" => $user_info['username'],
					"{password}" => $newUser['password'], // Fixes https://www.mybboost.com/thread-no-password
					"{provider}" => $this->provider
				];

				// Replace what needs to be replaced
				$message = str_replace(
					array_keys($thingsToReplace),
					array_values($thingsToReplace),
					$this->mybb->settings['flyover_passwordpm_message']
				);

				$pm = [
					'subject' => $subject,
					'message' => $message,
					'fromid' => $fromUid,
					'toid' => [
						$user_info['uid']
					],
					'options' => [
						'signature' => 1
					]
				];

				$pmhandler->set_data($pm);

				// Now let the PM handler do all the hard work
				if ($pmhandler->validate_pm()) {
					$pmhandler->insert_pm();
				} else {
					error(
						$this->lang->sprintf(
							$this->lang->flyover_error_report,
							$pmhandler->get_friendly_errors()
						)
					);
				}
			}

			// Finally return our new user data
			return $user_info;

		}
		else {
			throw new \Exception(implode('###', $userhandler->get_friendly_errors()));
		}

	}

	public function login()
	{
		if (!$this->user['uid'] or !$this->user['loginkey'] or !$this->session) {
			throw new \Exception('No user is available to log in.');
		}

		// Delete all the old sessions
		$this->db->delete_query(
			"sessions",
			"ip='" . $this->db->escape_string($this->session->ipaddress) . "' AND sid != '" . $this->session->sid . "'"
		);

		// Create a new session
		$this->db->update_query("sessions", [
			"uid" => $this->user['uid']
		], "sid='" . $this->session->sid . "'");

		// Set up the login cookies
		my_setcookie("mybbuser", $this->user['uid'] . "_" . $this->user['loginkey'], null, true);
		my_setcookie("sid", $this->session->sid, -1, true);

		return true;
	}

	public function link($identifier = '')
	{
		if (!$identifier) {
			throw new \Exception('No user is available to link.');
		}

		// Hash the identifier (ensures maximum privacy for users in case of database dumps
		// and standardizes identifiers to a default length)
		$identifier = Helper\Utilities::hashIdentifier($identifier, $this->provider);

		$prefix = TABLE_PREFIX;

		$query = <<<SQL
			INSERT INTO {$prefix}flyover_users ({$this->provider}, uid)
			VALUES ('{$identifier}', {$this->user['uid']})
			ON DUPLICATE KEY UPDATE {$this->provider} = '{$identifier}'
SQL;

		return $this->db->write_query($query);
	}

	public function unlink()
	{
		// Remove user provider name
		$usernames = $this->get->usernames();

		unset($usernames[$this->provider]);

		$update = [
			$this->provider => '',
			$this->provider . '_settings' => '',
			'usernames' => serialize($usernames)
		];

		return $this->db->update_query("flyover_users", $update, "uid = {$this->user['uid']}");
	}
}