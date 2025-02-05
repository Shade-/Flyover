<?php

namespace Flyover\Usercp;

use Flyover\User\User;
use Flyover\User\Usergroup;

class Unlink extends Usercp
{
	public function __construct()
	{
		$this->traitConstruct();

        // Unlinking is disabled
		if (!$this->mybb->settings['flyover_unlink']) {
			throw new \Exception($this->lang->flyover_error_unlinking_is_disabled);
		}

		$user = new User($this->mybb->user);

		if (count($user->get->enabledProviders()) == 1) {

			// No email or password (emailless before 2.0)
			if (!$this->mybb->user['email'] or !$this->mybb->user['password']) {

				throw new \Exception(
					$this->lang->sprintf(
						$this->lang->flyover_error_need_to_change_email_password,
						$this->provider
					)
				);

			}

			// Leave usergroup if this is the last linked provider
			$currentProviderSettings = $this->settings[$this->provider];

			$gid = (int) $currentProviderSettings['usergroup'] ?? (int) $this->mybb->settings['flyover_usergroup'];

			if ($gid) {

				$usergroup = new Usergroup;
				$usergroup->leave($gid);

			}

		}

		$user->unlink();

		redirect(
			'usercp.php?action=flyover',
			$this->lang->sprintf($this->lang->flyover_success_unlinked, $this->provider),
			$this->lang->flyover_success_unlinked_title
		);

	}

}