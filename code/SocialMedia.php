<?php

class SocialMedia extends Object
{
	public static function Fetch($echo=false)
	{
		if ($echo) echo "Fetching updates from Twitter...<br />";
		$twitter_updates	= SocialMediaTwitter::Fetch();
		/*if ($echo) echo "Fetching updates from Facebook...<br />";
		$facebook_updates	= SocialMediaFacebook::Fetch();
		if ($echo) echo "Fetching updates from LinkedIn...<br />";
		$linkedin_updates	= SocialMediaLinkedIn::Fetch();*/
		$linkedin_updates	= array();
		$facebook_updates	= array();
		if (false === $twitter_updates) $twitter_updates = array();	//If errors happen, don't let them
		if (false === $facebook_updates) $facebook_updates = array();	//interrupt the array_merge operation.
		if (false === $linkedin_updates) $linkedin_updates = array();
		$updates = array_merge($twitter_updates, $facebook_updates, $linkedin_updates);
		if ($echo) echo count($updates) > 0 ? "Writing ".count($updates)." updates to the database...<br />" : 'No updates to write to the database.<br />';

		/**
		 * @var SocialMediaImporterInterface $importer
		 */
		foreach (ClassInfo::implementorsOf('SocialMediaImporterInterface') as $importer)
		{
			$importer::ImportUpdates($updates);
		}
	}


}

/**
 * Classes implementing SocialMediaImporterInterface are used to save imported social media updates to dataobjects, in
 * whatever way the programmer want's to..
 * Interface SocialMediaImporter
 */
interface SocialMediaImporterInterface
{
	public static function ImportUpdates(array $updates);
}



/**
 * TODO
 * Class SocialMediaFacebook
 */
class SocialMediaFacebook
{
	public static function Fetch()
	{
		return array();
	}
}



interface SocialMediaInterface
{
	public static function Fetch();

	public static function Connection();

}





/**
 * A class/database table that is used to keep track of how much stuff we have already fetched from the social media feeds
 * in order to prevent fetching them again. Nevertheless, this table does not store data about individual social media
 * updates, so there are an additional check that ensures that no duplicate updates are fetched from the social media.
 * Class SocialMediaSettings
 */
class SocialMediaSettings extends DataObject
{

	private static $object;

	private static $db = array(
		'TwitterIndex'		=> 'Int',
	);

	public static function getValue($property)
	{
		return self::getObject()->$property;
	}

	public static function setValue($property,$value)
	{
		$object = self::getObject();
		$object->$property = $value;
		$object->write();
	}

	private static function getObject()
	{
		if (self::$object) return self::$object;
		self::$object = DataObject::get_one(__CLASS__);
		if (!self::$object) self::$object = new self;
		return self::$object;
	}
}

class SocialMediaFetchTask extends BuildTask
{
	protected $title	= 'Social Media Fetch';

	protected $description	= 'Imports updates from Twitter, Facebook and LinkedIn.';

	protected $enabled	= true;

	public function run($request)
	{
		echo 'Begin (if you do not see "Finished" below, the import has crashed!)<br />';
		SocialMedia::Fetch(true);
		echo 'Finished';

	}
}