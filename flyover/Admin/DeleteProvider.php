<?php

namespace Flyover\Admin;

use Flyover\Session\Cache;

class DeleteProvider extends ActionLoader
{
	public function __construct()
	{
		$this->traitConstruct();
		$this->cache = new Cache();

		// Get settings
		$providers = $this->cache->read('settings', $this->provider);

		// Drop columns
		if ($this->db->field_exists($this->provider, 'flyover_settings_data')) {
			$this->db->drop_column('flyover_settings_data', $this->provider);
		}

		if ($this->db->field_exists($this->provider . '_settings', 'flyover_settings_data')) {
			$this->db->drop_column('flyover_settings_data', $this->provider . '_settings');
		}

		// Save
		$options = [
			'enabled' => 0,
			'provider' => $this->provider,
			'id' => '',
			'key_token' => '',
			'secret' => '',
			'usergroup' => '',
			'settings' => []
		];

		$this->cache->update('settings', $this->provider, $options);

		// Redirect
		flash_message($this->lang->sprintf($this->lang->flyover_setup_complete_inactive, $this->provider), 'success');
		admin_redirect(MAINURL);
	}
}