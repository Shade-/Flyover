<?php

namespace Flyover\Admin;

use Flyover\Helper\Utilities;

class Manage
{
	use \Flyover\Helper\MybbTrait;

	public function __construct(
		$errors = ''
	)
	{
		$this->traitConstruct(['page', 'sub_tabs']);

		$this->sub_tabs['add'] = [
			'title' => $this->lang->flyover_add,
			'link' => MAINURL,
			'description' => $this->lang->flyover_add_desc
		];

		$exceptions = [
			'StackExchange' => ['id', 'secret', 'key'],
			'Steam' => ['secret'],
			'Tumblr' => ['key', 'secret'],
			'Twitter' => ['key', 'secret'],
			'Yahoo' => ['key', 'secret']
		];

		// Get settings
		$provider = $this->cache->read('settings', $this->provider);

		// Save the incoming settings
		if ($this->mybb->request_method == 'post') {

			$options = [
				'enabled' => (int) $this->mybb->input['enabled'],
				'provider' => $this->provider,
				'id' => $this->mybb->input['identifier'],
				'key_token' => $this->mybb->input['key'],
				'secret' => $this->mybb->input['secret'],
				'scopes' => trim($this->mybb->input['scopes']),
				'usergroup' => (int) $this->mybb->input['usergroup']
			];

			foreach (Utilities::getUserfields() as $field) {

				$options['settings'][$field] = (int) $this->mybb->input['syncOptions'][$field];
				$options['customfields'][$field] = (int) $this->mybb->input['syncCustomField'][$field];

			}

			// Save
			$this->cache->update('settings', $this->provider, $options);

			// Create/delete column
			if ((int) $this->mybb->input['enabled']) {

				// User ID
				if (!$this->db->field_exists($this->provider, 'flyover_users')) {
					$this->db->add_column('flyover_users', $this->provider, "VARCHAR(255) NOT NULL DEFAULT ''");
				}

				// User settings
				if (!$this->db->field_exists($this->provider . '_settings', 'flyover_users')) {
					$this->db->add_column('flyover_users', $this->provider . '_settings', "TEXT");
				}

			}
			else {

				// User ID
				if ($this->db->field_exists($this->provider, 'flyover_users')) {
					$this->db->drop_column('flyover_users', $this->provider);
				}

				// User settings
				if ($this->db->field_exists($this->provider . '_settings', 'flyover_users')) {
					$this->db->drop_column('flyover_users', $this->provider . '_settings');
				}

			}

			// Redirect
			$message = ($this->mybb->input['enabled'])
				? $this->lang->sprintf($this->lang->flyover_setup_complete_active, $this->provider)
				: $this->lang->sprintf($this->lang->flyover_setup_complete_inactive, $this->provider);

			flash_message($message, 'success');
			admin_redirect(MAINURL);

		}

		$this->page->add_breadcrumb_item($this->lang->flyover_add, MAINURL . '&action=add');

		$this->page->output_header($this->lang->flyover);

		$this->page->output_nav_tabs($this->sub_tabs, 'add');

		$form = new \Form(MAINURL . "&action=manage", "post", "manage");

		// Hidden provider
		echo $form->generate_hidden_field("provider", $this->provider);

		if ($errors) {
			$this->page->output_inline_error($errors);
		}

		// Question image
		$questionImage = " <a href='https://www.mybboost.com/thread-flyover-documentation'><img src='../images/icons/question.png' alt='What is this?' /></a>";

		$container = new \FormContainer($this->provider . " integration setup");

		// Enable/disable
		$container->output_row(
			$this->lang->flyover_setup_enabled,
			$this->lang->flyover_setup_enabled_desc,
			$form->generate_on_off_radio(
				'enabled',
				$provider['enabled'],
				1,
				['class' => 'setting_enabled'],
				['class' => 'setting_enabled']
			)
		);

		// Id
		if (!$exceptions[$this->provider] or in_array('id', $exceptions[$this->provider])) {

			$container->output_row(
				$this->lang->flyover_setup_id . $questionImage,
				$this->lang->flyover_setup_id_desc,
				$form->generate_text_box('identifier', $provider['id'], ['id' => 'id']),
				'id',
				[],
				['id' => 'row_id']
			);

			$count++;

		}

		// Key
		if ($exceptions[$this->provider] and in_array('key', $exceptions[$this->provider])) {

			$container->output_row(
				$this->lang->flyover_setup_key . $questionImage,
				$this->lang->flyover_setup_key_desc,
				$form->generate_text_box('key', $provider['key_token'], ['id' => 'key']),
				'key',
				[],
				['id' => 'row_key']
			);

			$count++;

		}

		// Secret
		if (!$exceptions[$this->provider] or in_array('secret', $exceptions[$this->provider])) {

			$container->output_row(
				$this->lang->flyover_setup_secret . $questionImage,
				$this->lang->flyover_setup_secret_desc,
				$form->generate_text_box('secret', $provider['secret'], ['id' => 'secret']),
				'secret',
				[],
				['id' => 'row_secret']
			);

			$count++;

		}

		// OpenID/Redirect URI notice
		if (!$count) {

			$container->output_row(
				$this->lang->flyover_setup_openid,
				'',
				$this->lang->flyover_setup_openid_desc,
				'openid',
				[],
				['id' => 'row_openid']
			);

		}
		else {

			$container->output_row(
				$this->lang->flyover_setup_redirect_uri . $questionImage,
				$this->lang->sprintf($this->lang->flyover_setup_redirect_uri_desc, $this->provider),
				$this->mybb->settings['bburl'] . '/flyover.php?action=login&provider=' . $this->provider .
				$this->lang->sprintf($this->lang->flyover_setup_redirect_uri_alternative, $this->mybb->settings['bburl']),
				'redirect_uri',
				[],
				['id' => 'row_redirect_uri']
			);

		}

		// Usergroup
		$provider['usergroup'] = $provider['usergroup'] ?? 2;

		$container->output_row(
			$this->lang->flyover_setup_usergroup,
			$this->lang->flyover_setup_usergroup_desc,
			$form->generate_group_select('usergroup', [$provider['usergroup']], ['id' => 'usergroup']),
			'usergroup',
			[],
			['id' => 'row_usergroup']
		);

		// Scopes
		$placeholder = '';
		if (!$provider['scopes']) {

			$plainFile = file(__DIR__ . '/../Hybridauth/Provider/' . $this->provider . '.php', FILE_SKIP_EMPTY_LINES);

			foreach ($plainFile as $line) {

				if (strpos($line, '$scope') !== false and preg_match_all('/\'([^\']+)\'/', $line, $m)) {
					$placeholder = $m[1][1] ?? $m[1][0];
					break;
				}

			}

		}

		$container->output_row(
			$this->lang->flyover_setup_scopes,
			$this->lang->flyover_setup_scopes_desc,
			$form->generate_text_box('scopes', $provider['scopes'], ['id' => 'scopes" placeholder="' . $placeholder . '"']),
			'scopes',
			[],
			['id' => 'row_scopes']
		);

		// Sync options
		$syncOptions = $syncOptionsPeekers = '';

        $profileFields = [0 => $this->lang->flyover_setup_sync_options_use_default_cpf];

		$query = $this->db->simple_select('profilefields', 'name, fid');
		while ($field = $this->db->fetch_array($query)) {
			$profileFields[$field['fid']] = $field['name'];
		}

		$table = new \Table;

		$table->construct_header($this->lang->flyover_setup_sync_options_header_field);
		$table->construct_header($this->lang->flyover_setup_sync_options_header_description);
		$table->construct_header($this->lang->flyover_setup_sync_options_header_active);
		$table->construct_header($this->lang->flyover_setup_sync_options_header_customfield);

		foreach(Utilities::getUserfields() as $field) {

			if (!$this->mybb->settings['flyover_' . $field . 'field'] and !in_array($field, ['avatar', 'website'])) {
				continue;
			}

			$tempName = 'flyover_setup_' . $field;
			$tempDesc = $tempName . '_desc';

			$localProfileFields = $profileFields;
			$localProfileFields[0] = str_replace(
			    '{CURRENT}',
			    $profileFields[$this->mybb->settings['flyover_' . $field . 'field']],
			    $localProfileFields[0]
            );

            // Name
            $table->construct_cell($this->lang->$tempName);

            // Description
            $table->construct_cell($this->lang->$tempDesc);

            // Active checkbox
			$table->construct_cell(
			    $form->generate_check_box(
    				'syncOptions[' . $field . ']',
    				1,
    				'',
    				[
        				'id' => 'syncOptions_' . $field,
    					'checked' => (int) $provider['settings'][$field]
    				]
    			)
			);

			// Provider-specific custom profile fields
			if (!in_array($field, ['avatar', 'website'])) {

    			$table->construct_cell(
    			    $form->generate_select_box(
        			    'syncCustomField[' . $field . ']',
        			    $localProfileFields,
        			    $provider['customfields'][$field],
        			    ['id' => 'row_custom_field_' . $field]
                    )
                );

                $syncOptionsPeekers .= "new Peeker($('#syncOptions_{$field}'), $('#row_custom_field_{$field}'), 1, true);\n";

            }
            else {
                $table->construct_cell('');
            }

            $table->construct_row();

		}

		$container->output_row(
			$this->lang->flyover_setup_sync_options . $questionImage,
			'',
			$table->output('', 1, 'general', true),
			'sync_options',
			[],
			['id' => 'row_sync_options']
		);

		// End container
		$container->end();

		// Peekers
		echo <<<HTML
<script type="text/javascript" src="./jscripts/peeker.js"></script>
<script type="text/javascript">
	$(document).ready(function() {
		new Peeker($(".setting_enabled"), $("#row_id"), 1, true);
		new Peeker($(".setting_enabled"), $("#row_secret"), 1, true);
		new Peeker($(".setting_enabled"), $("#row_key"), 1, true);
		new Peeker($(".setting_enabled"), $("#row_openid"), 1, true);
		new Peeker($(".setting_enabled"), $("#row_usergroup"), 1, true);
		new Peeker($(".setting_enabled"), $("#row_scopes"), 1, true);
		new Peeker($(".setting_enabled"), $("#row_sync_options"), 1, true);
		new Peeker($(".setting_enabled"), $("#row_redirect_uri"), 1, true);
		{$syncOptionsPeekers}
	});
</script>
HTML;

		// Close form wrapper
		$buttons[] = $form->generate_submit_button($this->lang->flyover_setup_save);
		$form->output_submit_wrapper($buttons);
		$form->end();
	}
}