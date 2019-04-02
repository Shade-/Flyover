<?php

namespace Flyover\Helper;

class Utilities {

	public static function getUserfields()
	{
		return [
			'avatar',
			'website',
			'sex',
			'bio',
			'location',
			'username',
			'identifier',
			'language'
		];
	}

	public static function hashIdentifier($id = '', $provider = '')
	{
		return md5(md5($id).md5($provider));
	}

}
