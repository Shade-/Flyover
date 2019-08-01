<?php

namespace Flyover\Session;

class Cache
{
	protected $prefix;

	public function __construct()
	{
		global $db, $PL;

		$PL or require_once PLUGINLIBRARY;

		$this->prefix = 'flyover_';
		$this->db = $db;
		$this->pl = $PL;
	}

	public function update($table, $provider, $params = [])
	{
		if (!$table or !$provider or !$table) {
			return false;
		}

		// Cache content
		$content = $this->pl->cache_read($this->prefix . $table);

		// Escape/serialize things before inserting into the db
		foreach ($params as $key => $param) {

			// Add to PL cache, overwriting old references
			$content[$provider][$key] = $param;

			if (is_array($param)) {
				$params[$key] = serialize($param);
			}
			else {
				$params[$key] = $this->db->escape_string($param);
			}
		}

		// DB fallback
		if ($flyover_settings['provider'] == $provider) {
			$this->db->update_query($this->prefix . $table, $params, "provider = '$provider'");
		} else {
			$this->db->insert_query($this->prefix . $table, $params);
		}

		return $this->pl->cache_update($this->prefix . $table, $content);
	}

	public function read($table, $key = '')
	{
		if (!$table) {
			return false;
		}

		$content = (array) $this->pl->cache_read($this->prefix . $table);

		if ($key == 'enabled') {
			return array_filter($content, function($a)
			{
				if ($a['enabled']) {
					return $a;
				}
			});
		}
		else if ($key) {
			return (array) $content[$key];
		}
		else {
			return $content;
		}
	}

	public function rebuild($table)
	{
		if (!$table) {
			return false;
		}

		$settings = [];

		$query = $this->db->simple_select($this->prefix . $table, '*');

		while ($setting = $this->db->fetch_array($query)) {

			$setting['settings'] = (array) my_unserialize($setting['settings']);

			$settings[$setting['provider']] = $setting;

		}

		return $this->pl->cache_update($this->prefix . $table, $settings);
	}
}