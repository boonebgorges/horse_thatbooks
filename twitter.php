<?php

require( dirname(__FILE__) . '/config.php' );
require( dirname(__FILE__) . '/includes/utilities.php' );

// Sometimes I turn off the throttle if I want to fire a tweet manually
define( 'THROTTLE', 1 );

// On my setup, cron hits this script once every minute. I only run it one out of every 480 times (roughly every 8 hours).
// Adjust as necessary
if ( defined( 'THROTTLE' ) && THROTTLE ) {
	$rand = rand( 0, 60*8 );
	if ( 4 != $rand ) {
		die();
	}
}

$query = "#thatcamp";

// Create our twitter API object
require_once( dirname(__FILE__) . '/lib/php-twitteroauth/twitteroauth/twitteroauth.php' );
$oauth = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);

// Get search. Go back and get the most recent few pages, for better completeness
$tweets_found = array();
for ( $i = 1; $i <= 5; $i++ ) {
	$these_tweets = $oauth->get(
		'http://search.twitter.com/search.json',
		array(
			'q' => $query,
			//'since_id' => $since_id,
			'include_entities' => false,
			'page' => $i,
			'rpp' => 100
		) )->results;
	$tweets_found = array_merge( $tweets_found, $these_tweets );
}

// Parse them into the textdump
$existing_tweets = (array) json_decode( file_get_contents( 'tweets.txt' ) );

if ( empty( $existing_tweets ) ) {
	$existing_tweets = array();
}

// Don't include tweets from the following users
$exclude_users = array(
	'horse_thatbooks' // THE RECURSION IS KILLING ME
);

foreach( $tweets_found as $tweet ) {

	// Exclude from the user blacklist
	if ( in_array( $tweet->from_user, $exclude_users ) ) {
		continue;
	}

	// Don't even bother looking at it if we've already stored it
	if ( isset( $existing_tweets[$tweet->id_str] ) ) {
		continue;
	}

	// Process the tweet text
	$tweet_text = $tweet->text;

	$things_to_remove = array(
		'#thatcamp'
	);

	foreach( $things_to_remove as $tor ) {
		$tweet_text = str_ireplace( $tor, '', $tweet_text );
	}

	// Now that we've got prepared text, check to make sure it's not a duplicate (mostly this
	// means many-times-retweeted tweets, which gum up the works a bit)
	$maybe_key = array_search( $tweet_text, $existing_tweets );

	if ( $maybe_key ) {
		continue;
	}

	$existing_tweets[$tweet->id_str] = $tweet_text;
}

$etcount = count( $existing_tweets );

if ( $etcount > 500 ) {
	$existing_tweets = array_slice( $existing_tweets, $etcount - 500 );
}

file_put_contents( 'tweets.txt', json_encode( $existing_tweets ) );

// Assemble raw text for markov before unsetting temp var
$raw_text = implode( '. ', $existing_tweets );
unset( $existing_tweets );

// Get the markov tool
require_once( dirname(__FILE__) . '/lib/MarkovBigram/markov.php' );

$markov = new MarkovBigram();
$output = $markov->GenerateModelResult($raw_text);

// Send an API request to verify credentials
$credentials = $oauth->get("account/verify_credentials");

/**
 * Do some text transformations
 */

// Too many links makes it not so funny
$output = horse_thatbooks_throttle_links( $output );

// Make sure we're short enough
if ( strlen( $output ) > 130 ) {
	$output_a = explode( ' ', $output );
	$new_output = '';
	foreach( $output_a as $o ) {
		$new_output .= $new_output . ' ';

		if ( strlen( $new_output ) > 120 ) {
			$output = $new_output;
			break;
		}
	}
}

// Balance parentheses
$output = horse_thatbooks_balance_parens( $output );

// Balance quotes
$output = horse_thatbooks_balance_quotes( $output );

// Miscellaneous cleanup
$output = horse_thatbooks_misc_cleanup( $output );

// Add the #thatcamp hash tag
$output .= ' #thatcamp';

// Post status
$oauth->post('statuses/update', array('status' => $output));

?>
