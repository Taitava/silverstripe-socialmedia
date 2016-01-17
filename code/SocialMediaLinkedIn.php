<?php

/**
 * Class SocialMedia
 */
class SocialMediaLinkedIn extends Object implements SocialMediaInterface
{

	private static $connection		= null;

	public static function Fetch()
	{
		return array();

		#self::Connection()->updates()

	}

	/**
	 * @return LinkedIn|null
	 */
	public static function Connection()
	{
		if (self::$connection) return self::$connection;

		$configuration = array(
			'appKey'	=> self::config()->app_key,
			'appSecret'	=> self::config()->app_secret,
			'callbackUrl'	=> Director::absoluteBaseURL(),
		);

		self::$connection = new LinkedIn($configuration);

		self::$connection->setToken(array(
			'oauth_token'		=> self::config()->user_token,
			'oauth_token_secret'	=> self::config()->user_token_secret,
		));

		return self::$connection;
	}

	public static function Error($response)
	{
		return $response['success'] == false;
	}
}