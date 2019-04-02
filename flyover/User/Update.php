<?php

namespace Flyover\User;

use Flyover\Helper\Utilities;

class Update
{
	use \Flyover\Helper\MybbTrait;

	public $profile;
	public $userfields;
	public $data;

	public function __construct(
		$user
	)
	{
		$this->traitConstruct();

		$this->secureSite = (bool) (strpos($this->mybb->settings['bburl'], 'https') !== false);
		$this->get = new Collect($user);

		$this->user = $user;

		// Sort out userfields permissions
		foreach (Utilities::getUserfields() as $field) {

			$tempKey = ($field . 'Identifier');

			$this->$tempKey = ($this->mybb->settings['flyover_' . $field . 'field'])
				? 'fid' . (int) $this->mybb->settings['flyover_' . $field . 'field']
				: false;

		}
	}

    public function providerUsername($name = '')
	{
		$usernames = $this->get->usernames();

		if ($name == $usernames[$this->provider]) {
			return true;
		}

		$usernames[$this->provider] = htmlspecialchars_uni($name);

		return $this->data['usernames'] = $this->db->escape_string(serialize(array_filter($usernames)));
	}

    public function loginIdentifier($id = '')
	{
		return $this->data[$this->provider] = Utilities::hashIdentifier($id, $this->provider);
	}

	public function avatar($avatar = '')
	{
		if (!$avatar) {
			return false;
		}

		// Support for https (avoids mixed content)
		$skip = ($this->secureSite and strpos($avatar, 'https') === false);

		if (!$skip) {

			$this->profile["avatar"]     = $this->db->escape_string($avatar);
			$this->profile["avatartype"] = "remote";

			list($maxwidth, $maxheight) = explode('x', my_strtolower($this->mybb->settings['maxavatardims']));

			// Copy the avatar to the local server (work around remote URL access disabled for getimagesize)
			$file     = fetch_remote_file($update["avatar"]);
			$tmpName = $this->mybb->settings['avataruploadpath'] . "/remote_" . md5(random_str());
			$fp       = @fopen($tmpName, "wb");

			if ($fp) {

				fwrite($fp, $file);
				fclose($fp);
				list($width, $height, $type) = @getimagesize($tmpName);
				@unlink($tmpName);

				if (!$type) {
					$avatarError = true;
				}

			}

			if (!$avatarError) {

				if ($width and $height and $this->mybb->settings['maxavatardims'] != "") {

					if (($maxwidth and $width > $maxwidth) or ($maxheight and $height > $maxheight)) {
						$avatardims = $maxheight . "|" . $maxwidth;
					}

				}

				if ($width > 0 and $height > 0 and !$avatardims) {
					$avatardims = $width . "|" . $height;
				}

				$this->profile["avatardimensions"] = $avatardims;

			} else {
				$this->profile["avatardimensions"] = $maxheight . "|" . $maxwidth;
			}

		}
	}

	public function cover($cover = '')
	{
		if (!$cover) {
			return false;
		}

		$skip = ($this->secureSite and strpos($cover, 'https') === false);

		if (!$skip) {

			$this->profile["profilepic"]     = $cover;
			$this->profile["profilepictype"] = "remote";

			if ($this->mybb->usergroup['profilepicmaxdimensions']) {

				list($maxwidth, $maxheight) = explode("x", my_strtolower($this->mybb->usergroup['profilepicmaxdimensions']));
				$this->profile["profilepicdimensions"] = $maxwidth . "|" . $maxheight;

			}

		}
	}

	public function website($website = '')
	{
		if (!$website) {
			return false;
		}

		$this->profile['website'] = $this->db->escape_string($website);
	}

	public function standardField($type = '', $value = '')
	{
		if (!$value) {
			return false;
		}

		$temp = $type . 'Identifier';

		if ($this->db->field_exists($this->$temp, "userfields")) {
			$this->userfields[$this->$temp] = $this->db->escape_string($value);
		}
	}

    public function settings($settings = [])
	{
		foreach ($settings as $key => $value) {

			if (is_array($value)) {
				$value = serialize($value);
			}

			$this->data[$key] = $value;

		}
	}

	public function customFieldsIdentifier()
	{
		$query = $this->db->simple_select('userfields', 'ufid', 'ufid = ' . (int) $this->user['uid']);

		// If the user hasn't got any field registered in the db, add it
		if (!$this->db->fetch_field($query, 'ufid')) {
			return $this->userfields['ufid'] = $this->user['uid'];
		}
	}

	public function finalize()
	{
		// Update Flyover's data
		if ($this->data) {
			$this->db->update_query(
				'flyover_users',
				$this->data,
				'uid = ' . (int) $this->user['uid']
			);
		}

		// Update profile
		if ($this->profile) {
			$query = $this->db->update_query('users', $this->profile, 'uid = ' . (int) $this->user['uid']);
		}

		// Update userfields
		if ($this->userfields) {

			if ($this->userfields['ufid']) {
				$query = $this->db->insert_query('userfields', $this->userfields);
			} else {
				$query = $this->db->update_query('userfields', $this->userfields, 'ufid = ' . (int) $this->user['uid']);
			}

		}
	}

}