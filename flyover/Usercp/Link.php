<?php

namespace Flyover\Usercp;

use Flyover\Flyover;
use Flyover\Session\Redirect;
use Flyover\User\User;
use Flyover\User\Usergroup;

class Link extends Usercp
{
	public function __construct()
	{
		$this->traitConstruct();

		$flyover = new Flyover();
		$redirect = new Redirect('usercp.php?action=link&provider=' . $this->provider);

		try {

			if (!$flyover->getAdapter($this->provider)->isConnected()) {
				$redirect->goTo('flyover.php?action=login&provider=' . $this->provider);
			}

		}
		catch (\Exception $e) {
			throw new \Exception($e->getMessage());
		}

		$profile = $flyover->getUserProfile();

		if ($profile->identifier) {

			// Link and sync
			$user = new User($this->mybb->user);
			$user->link($profile->identifier);
			$user->synchronize($profile);

			// Join usergroup
			$settings = $flyover->cache->read('settings');
			$setting = $settings[$this->provider];

			$gid = (int) $setting['usergroup'] ?? (int) $this->mybb->settings['flyover_usergroup'];

			if ($gid) {

				$usergroup = new Usergroup();
				$usergroup->join($gid);

			}

			// Redirect
			$redirect->set(['callback' => 'usercp.php?action=flyover']);

			$redirect->show(
				$this->lang->flyover_success_linked_title,
				$this->lang->sprintf($this->lang->flyover_success_linked, $this->provider)
			);

		}
		else {			
			throw new \Exception($this->lang->flyover_error_noauth);
		}

	}

}