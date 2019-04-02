<?php

namespace Flyover\Usercp;

use Flyover\Flyover;

class Usercp
{
	use \Flyover\Helper\MybbTrait;

	public function __construct()
	{
		$this->traitConstruct();

		$allowedPages = ['link', 'unlink', 'sync', 'flyover'];

		if (in_array($this->mybb->input['action'], $allowedPages)) {

			$className = 'Flyover\Usercp\\' . ucfirst($this->mybb->input['action']);

			try {
				new $className;
			}
			catch (\Exception $e) {
				new \Flyover\Usercp\Flyover($e->getMessage());
			}

		}

	}

}