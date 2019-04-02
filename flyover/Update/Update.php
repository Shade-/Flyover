<?php

namespace Flyover\Update;

use Flyover\Helper\Utilities;

class Update
{
    use \Flyover\Helper\MybbTrait;

    private $version;
    private $oldVersion;
    private $shadePlugins;
    private $info;

    public function __construct()
    {
        $this->traitConstruct(['cache']);

        $this->info = flyover_info();
        $this->shadePlugins = $this->cache->read('shade_plugins');
        $this->oldVersion = $this->shadePlugins[$this->info['name']]['version'];
        $this->version = $this->info['version'];

        if ($this->checkUpdate() and $this->mybb->input['update'] == 'flyover') {
            $this->update();
        }
    }

    private function checkUpdate()
    {
        if (version_compare($this->oldVersion, $this->version, "<")) {

            if ($this->mybb->input['update']) {
                return true;
            } else {
                flash_message($this->lang->flyover_error_needtoupdate, "error");
            }

        }

        return false;
    }

    private function update()
    {
        global $PL;

        $newSettings = $dropSettings = [];
        $updateTemplates = $updateFieldSettings = 0;

        // Get the gid
        $query = $this->db->simple_select("settinggroups", "gid", "name='flyover'");
        $gid   = (int) $this->db->fetch_field($query, "gid");

        // 1.2
        if (version_compare($this->oldVersion, '1.2', "<")) {

            $newSettings[] = [
                "name" => "flyover_email_pw_less",
                "title" => $this->db->escape_string($this->lang->setting_flyover_email_pw_less),
                "description" => $this->db->escape_string($this->lang->setting_flyover_email_pw_less_desc),
                "optionscode" => "yesno",
                "value" => 0,
                "disporder" => 8,
                "gid" => $gid
            ];

            $updateTemplates = 1;

        }

        // 1.3
        if (version_compare($this->oldVersion, '1.3', "<")) {

            $updateFieldsSettings = 1;
            $updateTemplates = 1;

        }

        // 1.4.1
        if (version_compare($this->oldVersion, '1.4.1', "<")) {

            $this->db->modify_column('flyover_settings', 'provider', 'TEXT');
            $this->db->modify_column('flyover_settings', 'enabled', "TINYINT(1) NOT NULL DEFAULT '1'");
            $this->db->modify_column('flyover_settings', 'id', "VARCHAR(255) NOT NULL DEFAULT ''");
            $this->db->modify_column('flyover_settings', 'secret', "VARCHAR(255) NOT NULL DEFAULT ''");
            $this->db->modify_column('flyover_settings', 'key_token', "VARCHAR(255) NOT NULL DEFAULT ''");
            $this->db->modify_column('flyover_settings', 'usergroup', "TINYINT(5) NOT NULL DEFAULT '2'");
            $this->db->modify_column('flyover_settings_data', 'usernames', 'TEXT');
            $this->db->modify_column('flyover_reports', 'file', 'TEXT');
            $this->db->modify_column('flyover_reports', 'message', 'TEXT');
            $this->db->modify_column('flyover_reports', 'trace', 'TEXT');
            $this->db->modify_column('flyover_reports', 'dateline', "VARCHAR(15) NOT NULL DEFAULT ''");
            $this->db->modify_column('flyover_reports', 'code', "VARCHAR(10) NOT NULL DEFAULT ''");
            $this->db->modify_column('flyover_reports', 'line', "INT(6) NOT NULL DEFAULT '0'");

            $folder = MYBB_ROOT . 'inc/plugins/Flyover/hybridauth/Hybrid/Providers';

            if (is_dir($folder)) {

                $files = (array) scandir($folder);

                // These will return . and .., unset them
                unset($files[0], $files[1]);

                foreach ($files as $key => $file) {

                    $check = str_replace('.php', '', $file);

                    if ($this->db->field_exists($check, 'flyover_settings_data')) {
                        $this->db->modify_column('flyover_settings_data', $check, "VARCHAR(255) NOT NULL DEFAULT ''");
                    }

                    if ($this->db->field_exists($check . '_settings', 'flyover_settings_data')) {
                        $this->db->modify_column('flyover_settings_data', $check . '_settings', 'TEXT');
                    }

                }

            }

        }

        // 2.0
        if (version_compare($this->oldVersion, '2.0', "<")) {

            if ($this->db->table_exists('flyover_settings_data')) {
                $this->db->rename_table('flyover_settings_data', 'flyover_users');
            }

            if ($this->db->table_exists('flyover_reports')) {
                $this->db->drop_table('flyover_reports');
            }

            if ($this->db->table_exists('flyover_settings') and !$this->db->field_exists('scopes', 'flyover_settings')) {
                $this->db->add_column('flyover_settings', 'scopes', 'TEXT AFTER key_token');
            }

            $dropSettings[] = 'popup_mode';

            $newSettings[] = [
                "name" => "flyover_keeprunning",
                "title" => $this->db->escape_string($this->lang->setting_flyover_keeprunning),
                "description" => $this->db->escape_string($this->lang->setting_flyover_keeprunning_desc),
                "optionscode" => "yesno",
                "value" => 0,
                "disporder" => 9,
                "gid" => $gid
            ];

            $this->db->update_query('settings', ['name' => 'flyover_passwordless'], "name = 'flyover_email_pw_less'");

            $updateFieldsSettings = 1;
            $updateTemplates = 1;

        }

        if ($updateFieldsSettings) {

            $i = 100;

            foreach (Utilities::getUserfields() as $field) {

                $tempKey = $field . 'field';

                if (
                    $this->db->num_rows($this->db->simple_select('settings', 'gid', "name = 'flyover_{$tempKey}'"), 'gid') != 0
                    or in_array($field, ['avatar', 'website'])
                ) {
                    continue;
                }

                $tempTitle = 'setting_flyover_' . $tempKey;
                $tempDesc = $tempTitle . '_desc';

                $newSettings[] = [
                    "name" => 'flyover_' . $tempKey,
                    "title" => $this->db->escape_string($this->lang->$tempTitle),
                    "description" => $this->db->escape_string($this->lang->$tempDesc),
                    'optionscode' => 'text',
                    "value" => '',
                    "disporder" => $i,
                    "gid" => $gid
                ];

                $i++;

            }

        }

        if ($newSettings) {
            $this->db->insert_query_multiple('settings', $newSettings);
        }

        if ($dropSettings) {
            $this->db->delete_query('settings', "name IN ('flyover_". implode("','flyover_", $dropSettings) ."')");
        }

        rebuild_settings();

        if ($updateTemplates) {

            $PL or require_once PLUGINLIBRARY;

            // Update templates       
            $dir       = new \DirectoryIterator(dirname(dirname(dirname(__FILE__))) . '/inc/plugins/Flyover/templates');
            $templates = [];
            foreach ($dir as $file) {
                if (!$file->isDot() and !$file->isDir() and pathinfo($file->getFilename(), PATHINFO_EXTENSION) == 'html') {
                    $templates[$file->getBasename('.html')] = file_get_contents($file->getPathName());
                }
            }

            $PL->templates('flyover', 'Flyover', $templates);

        }

        // Update the current version number and redirect
        $this->shadePlugins[$this->info['name']]['version'] = $this->version;

        $this->cache->update('shade_plugins', $this->shadePlugins);

        flash_message($this->lang->sprintf($this->lang->flyover_success_updated, $this->oldVersion, $this->version), "success");
        admin_redirect('index.php');
    }
}