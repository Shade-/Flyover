<?php

namespace Flyover\Usercp;

use Flyover\Flyover;
use Flyover\Session\Redirect;
use Flyover\User\User;

class Sync extends Usercp
{
	public function __construct()
	{
		$this->traitConstruct();

		$flyover = new Flyover();
		$redirect = new Redirect('usercp.php?action=sync&provider=' . $this->provider);

		try {

			if (!$flyover->getAdapter($this->provider)->isConnected()) {

				header('Location: flyover.php?action=login&provider=' . $this->provider);
				exit;

			}

		}
		catch (\Exception $e) {
			throw new \Exception($e->getMessage());
		}

		$profile = $flyover->getUserProfile();

		if ($profile->identifier) {

			$user = new User($this->mybb->user);
			$user->synchronize($profile);

			$redirect->set(['callback' => 'usercp.php?action=flyover']);

			$redirect->show(
				$this->lang->flyover_success_synced_title,
				$this->lang->sprintf($this->lang->flyover_success_synced, $this->provider)
			);

		}
		else {			
			throw new \Exception($this->lang->flyover_error_noauth);
		}

	}

}