<?php

use Abraham\TwitterOAuth\TwitterOAuth;

/**
 * Class SocialMediaExtension
 * @property DataObject $owner
 * @property string $TwitterID
 * @property string $FacebookID
 * @property string $LinkedInID
 */
class SocialMediaExtension extends Extension
{

	const TwitterMaxStatusLength = 140;
	const LinkedInMaxStatusLength= 400; //LinkedIn updates are posted as title/description pairs (description max length is 400 characters), not as comments (max length would be 700 characters).

	private static $db = array(
		'TwitterID'		=> 'Text',
		'FacebookID'		=> 'Text',
		'LinkedInID'		=> 'Text',
		'SoMeAuthor'		=> 'Text',
		'SoMeUsername'		=> 'Text',
		'Source'		=> 'Varchar(20)',
		'PublishInSocialMedia'	=> 'Boolean',
	);


	private $plaintext			= null;

	public function UpdateCMSFields(FieldList $fields)
	{
		if ($this->IsInSocialMedia())
		{
			$fields->addFieldToTab('Root.Main', $text = new CompositeField(), 'Content');
			$text->setDescription('Tämä blogiartikkeli on julkaistu sosiaalisessa mediassa. Muutokset blogiin eivät enää päivity sosiaaliseen mediaan. Jos poistat blogin, se ei poistu somesta automaattisesti.');
		}
		else
		{
			$fields->addFieldToTab('Root.Main', $checkbox = new CheckboxField('PublishInSocialMedia', 'Julkaise sosiaalisessa mediassa'), 'Content');
			$checkbox->setDescription('Julkaisu tapahtuu kun klikkaat "Tallenna ja julkaise". Jos teet muutoksia sisältöön ensimmäisen some-julkaisun jälkeen, muutokset päivittyvät ainoastaan blogiin, eivätkä mene someen. Jos poistat artikkelin, se täytyy käydä erikseen poistamassa somessa.');
		}
	}


	public function ToSocialMedia()
	{
		$this->plaintext = trim(Convert::html2raw($this->owner->Content, false, 9999));
		if (empty($this->plaintext)) return;
		if (empty($this->owner->TwitterID)) $this->ToTwitter();
		//if (empty($this->owner->LinkedInID)) $this->ToLinkedIn();
	}

	public function isInSocialMedia()
	{
		//return !empty($this->owner->TwitterID) or !empty($this->owner->FacebookID) or !empty($this->owner->LinkedInID);
		return !empty($this->owner->TwitterID);
	}

	public function onBeforePublish()
	{
		if ($this->owner->PublishInSocialMedia) $this->ToSocialMedia();
	}


	private function ToTwitter()
	{
		$link = $this->owner->AbsoluteLink();
		if (mb_strlen($this->plaintext) > (self::TwitterMaxStatusLength - mb_strlen($link) - 1))
		{
			$length		= self::TwitterMaxStatusLength - mb_strlen($link);
			$truncate	= mb_substr($this->plaintext,0,$length-4).'... ';
			$content	= $truncate.$link;
		}
		else
		{
			$content = $this->plaintext.' '.$link;
		}
		$data = SocialMediaTwitter::Connection()->post('statuses/update', array(
			'status'	=> $content,
			'trim_user'	=> true,	//Returns less user data in the response. Does not affect anything, but decreases traffic.
		));
		if (SocialMediaTwitter::Error()) return false;
		$this->owner->TwitterID = $data->id;
		return true;
	}

	public function Content($decorate=true)
	{
		if (!$decorate or strtolower($this->owner->Source) != 'twitter') return $this->owner->Content;
		Requirements::javascript('socialmedia/javascript/Twitter.js');
		return $this->owner->renderWith('SocialMedia_Tweet');
	}

	/**
	 * Two characters long version of Locale
	 */
	public function Locale2C()
	{
		return strtolower(substr($this->owner->Locale,0,2));
	}

	private function ToLinkedIn()
	{
		return false; //TODO
		if (mb_strlen($this->plaintext) > (self::LinkedInMaxStatusLength))
		{
			$description = mb_substr($this->plaintext,0,self::LinkedInMaxStatusLength-3).'...';
		}
		else
		{
			$description = $this->plaintext;
		}
		$content = array(
			'title'		=> $this->owner->Title,
			'description'	=> $description,
			'submitted-url'	=> $this->owner->AbsoluteLink(),
		);
		$response = SocialMediaLinkedIn::Connection()->share('new', $content, false, false);
		if (SocialMediaLinkedIn::Error($response)) return false;
		$this->owner->LinkedInID = $response['updateKey'];
		return true;
	}

	/**
	 * Deny editing if the DataObject is originally from any Social Media feed.
	 * @return bool
	 */
	public function canEdit($member = null)
	{
		//TODO: Not working! This function does not get called :(
		if ('Blog' != $this->owner->Source && !empty($this->owner->Source)) return false;
		return null; //null = don't affect the outcome.
	}




}