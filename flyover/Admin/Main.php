<?php

namespace Flyover\Admin;

use Flyover\Session\Cache;

class Main
{
	use \Flyover\Helper\MybbTrait;

	public function __construct()
	{
		$this->traitConstruct(['page', 'sub_tabs']);
		$this->cache = new Cache();

		if ($this->mybb->input['ajaxSave']) {

			$options = [
				'provider' => $this->mybb->input['provider']
			];

			if (!$options['provider']) {
				echo 0;
				exit;
			}

			foreach (['key_token', 'secret'] as $token) {

				if (isset($this->mybb->input[$token])) {
					$options[$token] = $this->mybb->input[$token];
				}

			}

			// _id instead of id, $this->mybb->input sanitized id to int and we need to bypass that
			if (isset($this->mybb->input['_id'])) {
				$options['id'] = $this->mybb->input['_id'];
			}

			if (count($options) > 1 and $this->cache->update('settings', $options['provider'], $options)) {
				echo 1;
			}
			else {
				echo 0;
			}

			exit;

		}

		$exceptions = [
			'StackExchange' => ['id', 'secret', 'key'],
			'Steam' => ['secret'],
			'Tumblr' => ['key', 'secret'],
			'Twitter' => ['key', 'secret'],
			'Yahoo' => ['key', 'secret']
		];

		$this->page->add_breadcrumb_item($this->lang->flyover, MAINURL);

		$this->page->extra_header .= PHP_EOL . '<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/brands.css" integrity="sha384-1KLgFVb/gHrlDGLFPgMbeedi6tQBLcWvyNUN+YKXbD7ZFbjX6BLpMDf0PJ32XJfX" crossorigin="anonymous">';
		$this->page->extra_header .= PHP_EOL . '<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/solid.css" integrity="sha384-+0VIRx+yz1WBcCTXBkVQYIBVNEFH1eP6Zknm16roZCyeNg2maWEpk/l/KsyFKs7G" crossorigin="anonymous">';
		$this->page->extra_header .= PHP_EOL . '<link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/fontawesome.css" integrity="sha384-jLuaxTTBR42U2qJ/pm4JRouHkEDHkVqH0T1nyQXn1mZ7Snycpf6Rl25VBNthU4z0" crossorigin="anonymous">';

		$this->page->output_header($this->lang->flyover);
		$this->page->output_nav_tabs($this->sub_tabs, 'general');

		$folder = MYBB_ROOT . 'flyover/Hybridauth/Provider';

		$providers = $this->cache->read('settings');

		$form = new \Form('index.php', 'get');
		echo $form->generate_hidden_field('module', 'config-flyover');
		echo $form->generate_hidden_field('action', 'manage');

		$table = new \Table;

		$table->construct_header($this->lang->flyover_overview);
		$table->construct_header('Enabled');
		$table->construct_header('Users');
		$table->construct_header('Id token');
		$table->construct_header('Key token');
		$table->construct_header('Secret token');

		$mainurl = MAINURL;

		// Get the available providers
		if (is_dir($folder)) {

			$files = scandir($folder);

			// These will return . and .., unset them
			unset($files[0], $files[1]);

			foreach ($files as $file) {

				if ($file == '.DS_Store') continue;

				$file = str_replace('.php', '', $file);
				$lowercaseProvider = strtolower($file);

				$inactive = (empty($providers[$file]['enabled']) or !$providers[$file]) ? true : false;
				$class = ($inactive) ? ' class="inactive"' : '';

				// Adjustments for icons
				if ($file == 'TwitchTV') {
					$lowercaseProvider = 'twitch';
				}
				else if ($file == 'Vkontakte') {
					$lowercaseProvider = 'vk';
				}
				else if ($file == 'WindowsLive') {
					$lowercaseProvider = 'windows';
				}
				else if ($file == 'StackExchange') {
					$lowercaseProvider = 'stack-exchange';
				}

				$table->construct_cell(<<<HTML
	<a{$class} href="{$mainurl}&action=manage&provider={$file}"><i class="icon fab fa-{$lowercaseProvider} {$lowercaseProvider}"></i> {$file}</a>
HTML
);

				// Enabled or disabled
				$enabled = (!$inactive)
					? '<i class="fas fa-check-circle" style="color: #6bad08; font-size: 1.2rem"></i>'
					: '<i class="fas fa-times-circle" style="color: #c15c3b; font-size: 1.2rem"></i>';
				$table->construct_cell($enabled, [
					'class' => 'align_center'
				]);

				// Number of users
				if (!$inactive) {

					$query = $this->db->simple_select('flyover_users', 'count(' . $file . ') AS number', $file . " <> ''");

					$table->construct_cell(
						$this->db->fetch_field($query, 'number'),
						['class' => 'align_center']
					);

				}
				else {
					$table->construct_cell('–', ['class' => 'align_center']);
				}

				// Tokens
				if (!$exceptions[$file] or in_array('id', $exceptions[$file])) {

					$table->construct_cell(
						$form->generate_text_box('_id', $providers[$file]['id'], ['style' => 'width: 250px'])
					);

				}
				else {
					$table->construct_cell('');
				}

				if ($exceptions[$file] and in_array('key', $exceptions[$file])) {

					$table->construct_cell(
						$form->generate_text_box('key_token', $providers[$file]['key_token'], ['style' => 'width: 250px'])
					);

				}
				else {
					$table->construct_cell('');
				}

				if (!$exceptions[$file] or in_array('secret', $exceptions[$file])) {

					$table->construct_cell(
						$form->generate_text_box('secret', $providers[$file]['secret'], ['style' => 'width: 250px'])
					);

				}
				else {
					$table->construct_cell('');
				}

				$table->construct_row(['id' => $file]);

			}

		}

		$table->output($this->lang->flyover_providers, 1, 'general flyover');

		$form->end();

		echo <<<HTML
<script type="text/javascript">

$(document).ready(function() {

	var tokens = {};

	$('.flyover tr').each(function() {

		var input = $(this).find('input');
		var provider = $(this).attr('id');
		var tok = {};

		$(this).find('[name="_id"], [name="key_token"], [name="secret"]').each(function() {
			tok[$(this).attr('name')] = $(this).val();
		});

		tokens[provider] = tok;

		if ($(this).find('.inactive').length) {
			return input.attr('disabled', true);
		}

	});

	$('[name="_id"], [name="key_token"], [name="secret"]').on('blur', function() {

		var type = $(this).attr('name');
		var provider = $(this).closest('tr').attr('id');
		var value = $(this).val();

		if (tokens[provider][type] == value) {
			return false;
		}

		var data = {
			ajaxSave: 1,
			provider: provider,
		};

		data[type] = value;

		$.when(

			$.ajax('index.php?module=config-flyover', {
				data: data
			})

		).then((response) => {

			if (Number(response) === 1) {

				tokens[provider][type] = value;

				$.jGrowl(provider + ' token has been saved successfully.', {theme: 'jgrowl_success'});

			}
			else {
				$.jGrowl('The token could not be saved due to an unknown reason. Please retry.', {theme: 'jgrowl_error'});
			}

		});

	});

});

</script>
<style type="text/css">
	input[disabled] {
		opacity: 0.5
	}
</style>
HTML;

		echo $GLOBALS['inline_style'];
	}
}