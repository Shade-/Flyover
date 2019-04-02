<?php

namespace Flyover\User;

class Usergroup {

	use \Flyover\Helper\MybbTrait;

	public function __construct()
	{
		$this->traitConstruct();
	}

	public function join($gid = 0)
	{
		if (!$gid) {
			return false;
		}

		$user = $this->mybb->user;

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

			return $this->db->update_query("users", $update, "uid = {$user['uid']}");

		}

		return false;
	}

	public function leave($gid = 0)
	{
		if (!$gid) {
			return false;
		}

		$user = $this->mybb->user;

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

			return $this->db->update_query("users", $update, "uid = {$user['uid']}");

		}

		return false;
	}

}