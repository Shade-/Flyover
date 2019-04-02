<?php

namespace Flyover\User;

class Collect
{
	use \Flyover\Helper\MybbTrait;

	public function __construct(
		$user
	)
	{
		$this->user = $user;
		$this->traitConstruct();
	}

    public function settings()
	{
		$query = $this->db->simple_select(
			'flyover_users',
			$this->provider . '_settings',
			'uid = ' . $this->user['uid']
		);

		return (array) my_unserialize(
			$this->db->fetch_field($query, $this->provider . '_settings')
		);
	}

    public function usernames()
	{
		$query = $this->db->simple_select(
			'flyover_users',
			'usernames',
			'uid = ' . $this->user['uid']
		);

		return (array) my_unserialize(
			$this->db->fetch_field($query, 'usernames')
		);
	}

	public function enabledProviders()
	{
		$query = $this->db->simple_select(
			'flyover_users',
			'*',
			'uid = ' . $this->user['uid']
		);
		$user = $this->db->fetch_array($query);

		unset ($user['uid'], $user['usernames']);

		$user = array_filter($user);

		foreach ($user as $key => $value) {

			if (strpos($key, '_settings') !== false) {
				unset($user[$key]);
			}

		}

		return (array) $user;
	}

}