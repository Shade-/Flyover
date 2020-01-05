<?php

namespace Flyover\Admin;

class RebuildCache
{
	use \Flyover\Helper\MybbTrait;

	public function __construct()
	{
		$this->traitConstruct(['page', 'sub_tabs']);

		if ($this->mybb->request_method == 'post') {

			if ($this->mybb->input['no']) {
				admin_redirect(MAINURL);
			}

			$folder = MYBB_ROOT . 'flyover/Hybridauth/Provider';

			// Rebuild cache – 1st attempt
			$this->cache->rebuild('settings');

			$providers = $this->cache->read('settings');

			// Fix for providers that don't exist anymore
			if (is_dir($folder)) {

				$files = (array) scandir($folder);

				// These will return . and .., unset them
				unset($files[0], $files[1]);

				foreach ($providers as $file => $arr) {

					$check = $file . '.php';

					if (!in_array($check, $files)) {

						// User ID
						if ($this->db->field_exists($file, 'flyover_users')) {
							$this->db->drop_column('flyover_users', $file);
						}

						// User settings
						if ($this->db->field_exists($file . '_settings', 'flyover_users')) {
							$this->db->drop_column('flyover_users', $file . '_settings');
						}

						// Internal settings
						$this->db->delete_query('flyover_settings', "provider = '" . $this->db->escape_string($file) . "'");

					}

				}

				foreach ($files as $key => $file) {

					$check = str_replace('.php', '', $file);

					if ($providers[$check]['enabled']) {

						// User ID
						if (!$this->db->field_exists($check, 'flyover_users')) {
							$this->db->add_column('flyover_users', $check, "VARCHAR(255) NOT NULL DEFAULT ''");
						}

						// User settings
						if (!$this->db->field_exists($check . '_settings', 'flyover_users')) {
							$this->db->add_column('flyover_users', $check . '_settings', "TEXT");
						}

					}

				}

			}

			// Rebuild cache - 2nd attempt
			$this->cache->rebuild('settings');

			flash_message($this->lang->flyover_cache_rebuilt, 'success');
			admin_redirect(MAINURL);

		}

		$this->page->add_breadcrumb_item($this->lang->flyover, MAINURL);
		$this->page->add_breadcrumb_item($this->lang->flyover_cache, MAINURL . '&action=rebuildCache');

		$this->page->output_confirm_action(
			MAINURL . '&action=rebuildCache',
			$this->lang->flyover_cache_desc,
			$this->lang->flyover_cache
		);
	}
}