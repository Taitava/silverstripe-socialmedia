# silverstripe-socialmedia
This module provies a basic interface for fetching/pushing content to/from Twitter, Facebook and LinkedIn.

THIS MODULE IS NOT YET STABLE! Use with caution.
What works (if works):
 - Twitter: send and receive tweets. Should work ok, may have tiny problems, but building blocks are in palce :).
 - LinkedIn: Incomplete. Sender part has been started, not working though. No clue about whether fetching updates from LinkedIn will be possible. LinkedIn has removed that featrure from their basic API. Need to establish some kind of API partnership with them.
 - Facebook: Not even started developing.


## Sample config file
Put this to mysite/_config/socialmedia.yml:

	---
	Name: socialmedia
	---
	SocialMediaTwitter:
	  title_length: 5
	  username: username
	  consumer_key: key
	  consumer_secret: secret
	  oauth_token: token
	  oauth_token_secret: secret
	
	SocialMediaLinkedIn:
	  app_key: key
	  app_secret: secret
	  user_token: token
	  user_token_secret: secret
	
	BlogPost:
	  extensions:
	    - SocialMediaExtension
