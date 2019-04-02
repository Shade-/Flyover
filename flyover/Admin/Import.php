<?php

namespace Flyover\Admin;

class Import
{
	use \Flyover\Helper\MybbTrait;

	public function __construct()
	{
		$this->traitConstruct(['page', 'sub_tabs']);

		if ($this->mybb->request_method == "post") {

			if (!$_FILES['local'] and !$this->mybb->input['url']) {
				$errors[] = $this->lang->error_missing_url;
			}

			if (!$errors) {

				// Find out if there was an uploaded file
				if ($_FILES['local']['error'] != 4) {

					// Find out if there was an error with the uploaded file
					if ($_FILES['local']['error'] != 0) {

						$errors[] = $this->lang->error_uploadfailed.$this->lang->error_uploadfailed_detail;
						switch ($_FILES['local']['error']) {
							case 1: // UPLOAD_ERR_INI_SIZE
								$errors[] = $this->lang->error_uploadfailed_php1;
								break;
							case 2: // UPLOAD_ERR_FORM_SIZE
								$errors[] = $this->lang->error_uploadfailed_php2;
								break;
							case 3: // UPLOAD_ERR_PARTIAL
								$errors[] = $this->lang->error_uploadfailed_php3;
								break;
							case 6: // UPLOAD_ERR_NO_TMP_DIR
								$errors[] = $this->lang->error_uploadfailed_php6;
								break;
							case 7: // UPLOAD_ERR_CANT_WRITE
								$errors[] = $this->lang->error_uploadfailed_php7;
								break;
							default:
								$errors[] = $this->lang->sprintf($this->lang->error_uploadfailed_phpx, $_FILES['local']['error']);
								break;

						}

					}

					if (!$errors) {

						// Was the temporary file found?
						if (!is_uploaded_file($_FILES['local']['tmp_name'])) {
							$errors[] = $this->lang->error_uploadfailed_lost;
						}

						// Get the contents
						$contents = @file_get_contents($_FILES['local']['tmp_name']);

						// Delete the temporary file if possible
						@unlink($_FILES['local']['tmp_name']);

						// Are there contents?
						if (!trim($contents)) {
							$errors[] = $this->lang->error_uploadfailed_nocontents;
						}

					}
				}
				else {
					// UPLOAD_ERR_NO_FILE
					$errors[] = $this->lang->error_uploadfailed_php4;
				}

				if (!$errors) {

					require_once  MYBB_ROOT."inc/class_xml.php";

					$parser = new XMLParser($contents);

					$tree = $parser->get_tree();

					if ($tree['settings']['attributes']['name'] != 'Flyover') {
						$errors[] = $this->lang->flyover_error_invalid_settings_file;
					}

					if (!$errors) {

						foreach ($tree['settings']['providers']['provider'] as $provider) {

							$options = [];

							foreach ($provider['setting'] as $val) {

								$options[$val['attributes']['name']] = trim($val['value']);

							}

							$options['settings'] = my_unserialize($options['settings']);

							// Rebuild internal settings
							$fcache->update('settings', $provider['attributes']['name'], $options);

							// Rebuild users table
							if ($options['enabled']) {

								// User ID
								if (!$db->field_exists($provider['attributes']['name'], 'flyover_users')) {
									$db->add_column('flyover_users', $provider['attributes']['name'], "VARCHAR(255) NOT NULL DEFAULT ''");
								}

								// User settings
								if (!$db->field_exists($provider['attributes']['name'] . '_settings', 'flyover_users')) {
									$db->add_column('flyover_users', $provider['attributes']['name'] . '_settings', "TEXT");
								}

							}

						}	

						flash_message($this->lang->flyover_import_successful, 'success');
						admin_redirect(MAINURL);

					}

				}

			}

		}

		$this->page->add_breadcrumb_item($this->lang->flyover_import, MAINURL . '&action=import');

		$this->page->output_header($this->lang->flyover);

		$this->page->output_nav_tabs($this->sub_tabs, 'import');

		if ($errors) {
			$this->page->output_inline_error($errors);
		}

		$form = new \Form(MAINURL . "&action=import", "post", "", 1);

		$container = new \FormContainer($this->lang->flyover_import_settings);
		$container->output_row(
			$this->lang->flyover_import_from,
			$this->lang->flyover_import_from_desc,
			$form->generate_file_upload_box("local", ['style' => 'width: 300px;']),
			'file'
		);
		$container->end();

		$buttons[] = $form->generate_submit_button($this->lang->flyover_import_button);

		$form->output_submit_wrapper($buttons);

		$form->end();

		$this->page->output_footer();
	}
}