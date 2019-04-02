<?php

/**
 * Integrates MyBB with many social networks, featuring login and registration.
 *
 * @package Main API class
 * @version 2.0
 */

namespace Flyover;

include 'Hybridauth/autoload.php';

use Flyover\Session\Cache;
use Flyover\User\UserInterface;
use Flyover\User\User;
use Flyover\User\Usergroup;
use Flyover\Helper\MybbTrait;
use Hybridauth\Hybridauth;
use Hybridauth\Storage\Session;
use Hybridauth\Adapter;
use Hybridauth\Logger\Logger;

class Flyover extends Hybridauth
{
	use MybbTrait;

	protected $config;

	public $cache;
	public $settings;
	public $user;

	public function __construct()
	{
		$this->traitConstruct();

		$this->cache = new Cache;
		$this->user = new User;
		$this->usergroup = new Usergroup;

		if (!$this->lang->flyover) {
			$this->lang->load('flyover');
		}

		if ($this->provider) {

			$configuration = [
				'callback' => $this->mybb->settings['bburl'] . '/flyover.php?action=login&provider=' . $this->provider,
				'providers' => []
			];

			$settings = $this->cache->read('settings');
			$setting = $settings[$this->provider];

			$keys = [];

			if ($setting['id']) {
				$keys['id'] = $setting['id'];
			}

			if ($setting['key_token']) {
				$keys['key'] = $setting['key_token'];
			}

			if ($setting['secret']) {
				$keys['secret'] = $setting['secret'];
			}

			if ($keys or $setting['enabled']) {

				$configuration['providers'][$this->provider] = [
					'keys' => $keys,
					'enabled' => true
				];

				// Provider-specific adjustments
				if ($this->provider == 'Facebook') {
					$configuration['providers'][$this->provider]['trustForwarded'] = false;
				}

				// Custom scopes
				if ($setting['scopes']) {
					$configuration['providers'][$this->provider]['scope'] = $setting['scopes'];
				}

			}

			$this->providerSettings = $setting;
			$this->settings = $this->providerSettings['settings'];
			$this->config = $configuration;

			if ($this->mybb->input['debug']) {
				$this->logger = new Logger('debug', MYBB_ROOT . 'flyover/error.log');
			}

		}
		else {

			if (THIS_SCRIPT == 'usercp.php' and $this->mybb->input['action'] == 'flyover') {
				return;
			}
			else {
				error($this->lang->flyover_error_no_provider);
			}
		}
	}

	public function getUserProfile()
	{
		if (!$this->adapter) {

			try {
				$this->adapter = $this->getAdapter($this->provider);
			}
			catch (\Exception $e) {
				error($e->getMessage());
			}

		}

		try {
			$this->user->profile = $this->adapter->getUserProfile();
		}
		catch (Hybridauth\Exception\HttpRequestFailedException $e) {
		    error($this->adapter->getHttpClient()->getResponseBody());
		}
		catch (\Exception $e) {
			error($e->getMessage());
		}

		return $this->user->profile;
	}

	public function getMatchedAccounts(
		$identifier,
		$email = ''
	)
	{
		$escapedPlainIdentifier = $this->db->escape_string($identifier);
		$identifier = md5(md5($identifier).md5($this->provider));

		if ($email) {
			$extraSql = " OR u.email = '" . $this->db->escape_string($email) . "'";
		}

		// Multiple accounts – this is set when an account is chosen
		$uid = (int) $this->mybb->input['uid'];

		if ($uid > 0) {
			$uidCheckStart = "u.uid = '{$uid}' AND (";
			$uidCheckEnd = ")";
		}

		$prefix = TABLE_PREFIX;
		$accounts = [];

		// Are you already with us?
		$sql = <<<SQL
			SELECT u.*, m.{$this->provider}, m.{$this->provider}_settings
			FROM {$prefix}users u
			LEFT JOIN {$prefix}flyover_users m ON m.uid = u.uid
			WHERE {$uidCheckStart}m.{$this->provider} = '{$identifier}'
				OR m.{$this->provider} = '{$escapedPlainIdentifier}'{$extraSql}{$uidCheckEnd}
SQL;
		$query = $this->db->write_query($sql);

		while ($account = $this->db->fetch_array($query)) {
			$accounts[] = $account;
		}

		return $accounts;
	}

}