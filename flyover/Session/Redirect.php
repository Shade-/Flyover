<?php

namespace Flyover\Session;

class Redirect
{
	use \Flyover\Helper\MybbTrait;

	public function __construct($callback = '')
	{
		$this->traitConstruct();

		if (!session_id()) {
			session_start();
		}

		if ($callback) {
			$this->setCallback($callback);
		}
	}

	public function setCallback($callback = '')
	{
		$callback = $callback ?? $_SERVER['HTTP_REFERER'];

		return $this->set(['callback' => $callback]);
	}

	public function show(
		$title = '',
		$message = ''
	)
	{
		$localSession = $_SESSION['flyover'];
		$url = $localSession['callback'];
		$type = $localSession['type'] ?? 'success';

		// Ensure we're not redirecting to Flyover itself
		if (strpos($url, "flyover.php") === false and strpos($url, "action=login") === false) {
			$url = htmlspecialchars_uni($url);
		}
		else {
			$url = "index.php";
		}

		if (!$url) {
			$url = "index.php";
		}

		$this->unset();

		if ($this->mybb->input['ajax']) {

			echo json_encode([
				'title' => $title,
				'message' => $message
			]);

			exit;

		}
		else {
			redirect($url, $message, $title);
		}
	}

	public function goTo($url)
	{
		if ($this->mybb->input['ajax']) {
			echo json_encode([
				'redirect' => $url
			]);
		}
		else {
			header('Location: ' . $url);
		}

		exit;
	}

	public function toCallback()
	{
		if (!$_SESSION['flyover']) {
			return false;
		}

		$url = $_SESSION['flyover']['callback'];
		$this->unset();

		return $this->goto($url);
	}

	public function set($data = [])
	{
		if (!$_SESSION['flyover']) {
			$_SESSION['flyover'] = [];
		}

		return $_SESSION['flyover'] = array_filter(array_merge($_SESSION['flyover'], (array) $data));
	}

	public function unset()
	{
		unset($_SESSION['flyover']);
	}

}