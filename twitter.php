<?php

require( dirname(__FILE__) . '/config.php' );

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

// Don't include tweets from the following users
$exclude_users = array(
	'horse_thatbooks'
);

// Create our twitter API object
require_once( dirname(__FILE__) . '/lib/php-twitteroauth/twitteroauth/twitteroauth.php' );
$oauth = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);

// Get search
$tweets_found = $oauth->get(
	'http://search.twitter.com/search.json',
	array(
		'q' => $query,
		//'since_id' => $since_id,
		'include_entities' => false,
		'page' => 1,
		'rpp' => 100
	) )->results;

// Parse them into the textdump
$existing_tweets = (array) json_decode( file_get_contents( 'tweets.txt' ) );

if ( empty( $existing_tweets ) ) {
	$existing_tweets = array();
}

foreach( $tweets_found as $tweet ) {
	if ( in_array( $tweet->from_user, $exclude_users ) ) {
		continue;
	}

	if ( !isset( $existing_tweets[$tweet->id] ) ) {

		// Process the tweet text
		$tweet_text = $tweet->text;

		$things_to_remove = array(
			'#thatcamp'
		);

		foreach( $things_to_remove as $tor ) {
			$tweet_text = str_ireplace( $tor, '', $tweet_text );
		}

		$existing_tweets[$tweet->id] = $tweet_text;
	}
}


$etcount = count( $existing_tweets );

if ( $etcount > 500 ) {
	$existing_tweets = array_slice( $existing_tweets, $etcount - 500 );
}

file_put_contents( 'tweets.txt', json_encode( $existing_tweets ) );

// Assemble raw text for markov before unsetting temp var
$raw_text = implode( '. ', $existing_tweets );
//echo $raw_text; die();

unset( $existing_tweets );

// Get the markov tool
require_once( dirname(__FILE__) . '/lib/MarkovBigram/markov.php' );

$markov = new MarkovBigram();
$output = $markov->GenerateModelResult($raw_text);

// The markov generator strips some URL formats
$botched_url_snippets = array(
	'/tco/' => '/t.co/'
);
foreach( $botched_url_snippets as $bus => $r ) {
	$output = str_replace( $bus, $r, $output );
}

// Send an API request to verify credentials
$credentials = $oauth->get("account/verify_credentials");

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

$output .= ' #thatcamp';
/*echo $output . '
'; die();*/

// Post status
$oauth->post('statuses/update', array('status' => $output));

?>
