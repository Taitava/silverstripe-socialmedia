<?php

use Abraham\TwitterOAuth\TwitterOAuth;


class SocialMediaTwitter extends Object implements SocialMediaInterface
{

	/**
	 * @var null|TwitterOAuth $connection
	 */
	private static $connection = null;

	public static function Fetch($echo = false)
	{

		$since_id = (int) SocialMediaSettings::getValue('TwitterIndex');
		if ($since_id == 0) $since_id = 1; //Twitter gives an error message if since_id is zero. This happens when tweets are fetched the first time.
		$tweets   = self::Connection()->get('statuses/user_timeline', array(
			'since_id'		=> $since_id,
			'count'			=> 200,        //200 is a maximum that is allowed by Twitter
			'include_rts'		=> true,//'rts' means 'retweets'
			'exclude_replies'	=> true,
			'screen_name'		=> self::config()->username,
		));

		if (self::Error())
		{
			user_error("Twitter error: ".self::ErrorMessage($tweets));
			return false;
		}

		$updates	= array();
		$max_id		= (int) SocialMediaSettings::getValue('TwitterIndex');
		$skipped	= 0;
		foreach ($tweets as $tweet)
		{
			if (!DataObject::get('BlogPost', 'TwitterID = "' . Convert::raw2sql($tweet->id).'"')->exists()) //TODO: Make the check not class specific (don't hard code 'BlogPost' here).
			{
				//Do not supply the tweet if a DataObject with the same Twitter ID already exists.
				//However, $max_id can still be updated with the already existing ID number. This will
				//Ensure we do not have to loop over this tweet again.
				$updates[] = self::tweet_to_array($tweet);
			}
			else
			{
				$skipped++;
			}
			$max_id = max($max_id, (int) $tweet->id);
		}
		if ($echo and $skipped > 0) echo "Twitter: Skipped $skipped existing records. This is normal if its only a few records occasionally, but if it happens every time and the number is constant or raises, then the Twitter's 'since_id' parameter is not working correctly. If the count reaches 200, it might be that you won't get any new records at all, because Twitter's maximum count for returned records per query is 200 at the moment when writing this (January 2016). Also constant skipping might affect performance.<br />";
		if ($echo) echo "Twitter: since_id was $since_id and is now $max_id.<br />";
		SocialMediaSettings::setValue('TwitterIndex', $max_id); //Keep track of where we left, so we don't get the same tweets next time.
		return $updates;
	}

	public static function Connection()
	{
		if (self::$connection) return self::$connection;
		$config			= self::config();
		$consumer_key		= $config->consumer_key;
		$consumer_secret	= $config->consumer_secret;
		$oauth_token		= $config->oauth_token;
		$oauth_token_secret	= $config->oauth_token_secret;
		if (	empty($consumer_key) ||
			empty($consumer_secret) ||
			empty($oauth_token) ||
			empty($oauth_token_secret))
			throw new Exception(__METHOD__.'(): Twitter credentials do not exist in the configuration.');
		self::$connection = new TwitterOAuth(
			$consumer_key,
			$consumer_secret,
			$oauth_token,
			$oauth_token_secret
		);
		self::$connection->setTimeouts(30,30);

		//Test the connection
		self::$connection->get('account/verify_credentials');
		if (self::Error()) throw new Exception(__METHOD__.'(): Connection failed.');
		return self::$connection;
	}

	public static function Error()
	{
		return self::Connection()->getLastHttpCode() != 200;
	}


	/**
	 * This method is copied 2015-11-24 from  theplumpss/twitter module (https://github.com/plumpss/twitter/blob/master/code/PlumpTwitterFeed.php)
	 * Also some other parts of this module has used theplumpss/twitter as an example, but tweet_to_array() is the
	 * only direct copy - although it has some modifications too, regarding to format of the return value.
	 * @param $tweet
	 * @return mixed
	 */
	private static function tweet_to_array($tweet)
	{
		$date = new SS_Datetime();
		$date->setValue(strtotime($tweet->created_at));

		$html = $tweet->text;

		if ($tweet->entities) {

			//url links
			if ($tweet->entities->urls) {
				foreach ($tweet->entities->urls as $url) {
					$html = str_replace($url->url, '<a href="' . $url->url .'" target="_blank">' . $url->url . '</a>', $html);
				}
			}

			//hashtag links
			if ($tweet->entities->hashtags) {
				foreach ($tweet->entities->hashtags as $hashtag) {
					$html = str_replace('#' . $hashtag->text, '<a target="_blank" href="https://twitter.com/search?q=%23' . $hashtag->text . '">#' . $hashtag->text . '</a>', $html);
				}
			}

			//user links
			if ($tweet->entities->user_mentions) {
				foreach ($tweet->entities->user_mentions as $mention) {
					$html = str_replace('@' . $mention->screen_name, '<a target="_blank" href="https://twitter.com/' . $mention->screen_name . '">@' . $mention->screen_name . '</a>', $html);
				}
			}

		}

		$title = new Text();
		$title->setValue($tweet->text);

		return array(
			'LastEdited'	=> (string) $date,
			'Created'	=> (string) $date,
			'Fetched'	=> SS_Datetime::now(),
			'SoMeAuthor' 	=> $tweet->user->screen_name,
			'SoMeUsername'	=> self::config()->username,
			'Avatar'	=> $tweet->user->profile_image_url,
			'Content'	=> $html,
			'Title'		=> preg_replace('/\.$/','', $title->Summary(self::config()->title_length)), //For some reason the Summary method adds a trailing dot. Remove it.
			'TwitterID'	=> $tweet->id,
			'Locale'	=> $tweet->lang,
			'Source'	=> 'Twitter',
		);
	}

	private static function ErrorMessage($data)
	{
		$errors = array();
		foreach ($data->errors as $error)
		{
			$errors[] = "Code ".$error->code.": ".$error->message;
		}
		return implode(', ', $errors);
	}
}
