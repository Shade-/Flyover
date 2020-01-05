<?php

namespace Flyover\Admin;

use Flyover\Session\Cache;

class Export
{
	use \Flyover\Helper\MybbTrait;

	public function __construct()
	{
		$this->traitConstruct();

		$xml = "<?xml version=\"1.0\" encoding=\"{$this->lang->settings['charset']}\"?".">\r\n";
		$xml .= "<settings name=\"Flyover\" version=\"".$this->mybb->version_code."\">\r\n";
		$xml .= "\t<providers>\r\n";

		$providers = $this->cache->read('settings');

		foreach ($providers as $key => $value) {

			$xml .= "\t\t<provider name=\"{$key}\">\r\n";

			foreach ($value as $k => $v) {

				if ($k == 'sid') {
					continue;
				}

				$content = (is_array($v)) ? serialize($v) : htmlspecialchars_uni($v);

				$xml .= "\t\t\t<setting name=\"{$k}\">{$content}</setting>\r\n";

			}

			$xml .= "\t\t</provider>\r\n";

		}

		$xml .= "\t</providers>\r\n";
		$xml .= "</settings>";

		header("Content-disposition: attachment; filename=flyover-settings.xml");
		header("Content-type: application/octet-stream");
		header("Content-Length: ".strlen($xml));
		header("Pragma: no-cache");
		header("Expires: 0");
		echo $xml;

		exit;
	}
}