<?php

if (!defined("IN_MYBB")) {
	header("HTTP/1.0 404 Not Found");
	exit;
}

define('MODULE', 'flyover');
define('MAINURL', 'index.php?module=config-flyover');

include FLYOVER;

$lang->load(MODULE);

$gid = flyover_settings_gid();

$sub_tabs['general']  = [
	'title' => $lang->flyover_general,
	'link' => MAINURL,
	'description' => $lang->flyover_general_desc
];
$sub_tabs['settings'] = [
	'title' => $lang->flyover_settings,
	'link' => "index.php?module=config-settings&action=change&gid={$gid}"
];
$sub_tabs['cache'] = [
	'title' => $lang->flyover_cache,
	'link' => MAINURL . '&action=rebuildCache',
];
$sub_tabs['export'] = [
	'title' => $lang->flyover_export,
	'link' => MAINURL . '&action=export',
];
$sub_tabs['import'] = [
	'title' => $lang->flyover_import,
	'link' => MAINURL . '&action=import',
];
$sub_tabs['migration'] = [
	'title' => $lang->flyover_migration,
	'link' => MAINURL . '&action=migration',
	'description' => $lang->flyover_migration_desc
];

$className = ($mybb->input['action'])
	? 'Flyover\Admin\\' . ucfirst($mybb->input['action'])
	: 'Flyover\Admin\Main';

try {
	new $className;
}
catch (\Exception $e) {
	new \Flyover\Admin\Main($e->getMessage());
}
catch (\Error $e) {
	admin_redirect(MAINURL);
}

$page->output_footer();