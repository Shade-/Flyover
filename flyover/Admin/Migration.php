<?php

namespace Flyover\Admin;

class Migration
{
	use \Flyover\Helper\MybbTrait;

	public function __construct()
	{
		$this->traitConstruct(['page', 'sub_tabs']);

		$allowedPlugins = [
			'myfbconnect.php' => [
				'name' => 'Facebook',
				'field' => 'myfb_uid'
			],
			'mytwconnect.php' => [
				'name' => 'Twitter',
				'field' => 'mytw_uid'
			],
			'steamlogin.php' => [
				'name' => 'Steam',
				'field' => 'loginname'
			]
		];

		$fieldExists = false;

		if ($this->mybb->input['migrate']) {

			$codename = (string) $this->mybb->input['migrate'];
			$migration_info = $allowedPlugins[$codename];

			$fieldExists = $db->field_exists($migration_info['name'], 'flyover_users');

			if ($migration_info and $fieldExists) {

				$perpage = 100;
				$start = (isset($this->mybb->input['start'])) ? (int) $this->mybb->input['start'] : 0;

				// Set this provider's name
				$flyover->provider = $migration_info['name'];

				// Load the core API
				$flyover->load();

				$counter = $start;

				// Loop through this provider's linked users
				$query = $db->simple_select('users', "uid, usergroup, additionalgroups, {$migration_info['field']}", "{$migration_info['field']} <> ''", ['limit_start' => $start, 'limit' => $perpage]);
				while ($user = $db->fetch_array($query)) {

					$counter++;
					$flyover->linkUser($user, $user[$migration_info['field']]);

				}

				$numusers = $db->num_rows($query);

				// Display next page notice
				if ($numusers == $perpage) {

					$this->page->add_breadcrumb_item($this->lang->flyover_migration, MAINURL . '&action=migration');

					$this->page->output_header($this->lang->flyover_migration);

					$this->page->output_nav_tabs($this->sub_tabs, 'migration');

					$table = new Table;
					$table->construct_cell($this->lang->sprintf($this->lang->flyover_migration_nextpage_notice, $start + $perpage, $codename, $counter, $perpage));
					$table->construct_row();
					$table->output($this->lang->flyover_migration_nextpage);

				}
				else {

					require_once MYBB_ROOT . "inc/plugins/" . $codename;
					$infofunc = str_replace('.php', '', $codename) . '_info';
					$info = $infofunc();

					flash_message($this->lang->sprintf($this->lang->flyover_migration_successful, $counter, $info['name']), 'success');
					admin_redirect(MAINURL);

				}

			}

		}
		else {

			$this->page->add_breadcrumb_item($this->lang->flyover_migration, MAINURL . '&action=migration');

			$this->page->output_header($this->lang->flyover_migration);

			$this->page->output_nav_tabs($this->sub_tabs, 'migration');

			$table = new \Table;
			$table->construct_header($this->lang->flyover_migration_plugin);
			$table->construct_header($this->lang->flyover_migration_status, [
				'width' => '25%'
			]);

			$dir = @opendir(MYBB_ROOT."inc/plugins/");
			if ($dir) {

				while ($file = readdir($dir)) {

					$ext = get_extension($file);

					if ($ext == "php" and in_array($file, array_keys($allowedPlugins))) {
						$pluginsList[] = $file;
					}

				}

				@sort($pluginsList);

			}

			@closedir($dir);

			$codenames = $GLOBALS['cache']->read('plugins');
			$activePlugins = $codenames['active'];

			$active = false;

			if ($pluginsList) {

				foreach ($pluginsList as $plugin) {

					require_once MYBB_ROOT . "inc/plugins/" . $plugin;
					$codename = str_replace('.php', '', $plugin);

					if (!$activePlugins[$codename]) {
						continue;
					}

					$active = true;

					$infofunc = $codename . '_info';

					if (!function_exists($infofunc)) {
						continue;
					}

					$info = $infofunc();

					// Get number of users to process
					$query = $db->simple_select('users', 'uid', $allowedPlugins[$plugin]['field'] . " <> ''");
					$numUsers = $db->num_rows($query);

					$migrationLink = ($fieldExists) ? '<a href="' . MAINURL . '&amp;action=migration&amp;migrate=' . $plugin . '">' . $this->lang->flyover_migration_migrate . '</a>' : $this->lang->sprintf($this->lang->flyover_migration_configure_provider, $allowedPlugins[$plugin]['name']);

					$table->construct_cell($info['name'] . ' (' . $numUsers . ' users)');
					$table->construct_cell($migrationLink);
					$table->construct_row();

				}

			}

			if (!$active) {

				$table->construct_cell($this->lang->flyover_migration_no_plugins_available, [
					'colspan' => 2
				]);
				$table->construct_row();

			}

			$table->output($this->lang->flyover_migration);

		}
	}
}