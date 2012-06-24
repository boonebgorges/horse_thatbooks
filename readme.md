== horse_thatbooks ==

This is the source code for [@horse_thatbooks](http://twitter.com/horse_thatbooks). Created at THATCamp CHNM 2012.

In a nutshell, it does the following:

* Pulls the latest tweets with the #thatcamp hashtag
* Stores them in its tweet cache (tweets.txt, which holds up to 500 tweets)
* Runs them through a Markov chain process to create some new text
* Adds the #thatcamp hashtag, and sends the tweet

Thanks to the following libraries:

* [MarkovBigram](https://github.com/robertkleffner/MarkovBigram)
* [twitteroauth](https://github.com/abraham/twitteroauth)

== How to use ==

1. Create a file called tweets.txt
1. Copy config.sample.php to config.php
1. Get creds from Twitter
1. Put those creds in config.php
1. Set a cronjob to call `php /path/to/twitter.php`
1. ???
1. Profit
