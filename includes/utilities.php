<?php

/**
 * Utility functions for @horse_thatbooks
 */

/**
 * Throttle links
 *
 * Tweets full of links are not all that funny. Let's throttle them a bit (1/4)
 *
 * @param str $output The raw tweet
 * @return str $output The tweet, possibly with some links replaced
 */
function horse_thatbooks_throttle_links( $output ) {
	$output_a = explode( ' ', $output );

	foreach( $output_a as $k => $word ) {
		if ( 0 === strpos( $word, 'http' ) ) {
			// Roll the dice
			$rand = rand( 0, 3 );
			if ( 0 != $rand ) {
				unset( $output_a[$k] );
			}
		}
	}

	$output = implode( ' ', $output_a );

	return $output;
}

/**
 * Balance parentheses
 *
 * Note: This is not a true parentheses balancer. I didn't want anything too fancy, so I just made
 * it pick a random parenthesis, and add a mate for it. Don't use this for real balancing!
 *
 * @param str $output The raw tweet
 * @return str $output The tweet with parens balanced
 */
function horse_thatbooks_balance_parens( $output ) {
	if ( false !== strpos( $output, '(' ) || false !== strpos( $output, ')' ) ) {
		preg_match_all( '|\(|', $output, $os, PREG_OFFSET_CAPTURE );
		preg_match_all( '|\)|', $output, $cs, PREG_OFFSET_CAPTURE );

		$count_os = isset( $os[0] ) && is_array( $os[0] ) ? count( $os[0] ) : 0;
		$count_cs = isset( $cs[0] ) && is_array( $cs[0] ) ? count( $cs[0] ) : 0;

		// Case 1: We have both open and close parens
		if ( $count_os && $count_cs ) {
			// Nuke all the close parens
			$output = str_replace( ')', '', $output );

			// Take just the first open paren
			$output_a = explode( '(', $output );
			$output = $output_a[0] . '(' . implode( '', array_slice( $output_a, 1 ) );

			// Reset counts. This'll make it fall through to Case 2
			$count_cs = 0;
			$count_os = 1;
		}

		// Case 2: We have only open parens
		if ( $count_os && !$count_cs ) {
			// If we have more than one open paren, nuke all but the first
			if ( $count_os > 1 ) {
				$output_a = explode( '(', $output );
				$output = $output_a[0] . '(' . implode( '', array_slice( $output_a, 1 ) );
			}

			$output_a = explode( '(', $output );

			// Attach a close paren to a random word after the open paren
			// Sanity check. We should always pass this test
			if ( isset( $output_a[1] ) ) {
				// Break into words
				$output_a_1 = explode( ' ', $output_a[1] );
				$rand_key = array_rand( $output_a_1 );
				$output_a_1[$rand_key] .= ')';
				$output_a[1] = implode( ' ', $output_a_1 );
			}

			$output = implode( '(', $output_a );

			// Reset counts. This will ensure that the next case does *not* run
			$count_cs = 0;
			$count_os = 0;
		}

		// Case 3: We have only close parens
		if ( !$count_os && $count_cs ) {
			// If we have more than one close paren, nuke all but the last
			if ( $count_cs > 1 ) {
				$output_a = explode( ')', $output );
				$last_word = array_pop( $output_a );
				$output = implode( '', $output_a ) . ')' . $last_word;
			}

			$output_a = explode( ')', $output );

			// Attach an open paren to a random word before the close paren
			// Sanity check. We should always pass this test
			if ( isset( $output_a[1] ) ) {
				// Break into words
				$output_a_0 = explode( ' ', $output_a[0] );
				$rand_key = array_rand( $output_a_0 );
				$output_a_0[$rand_key] = '(' . $output_a_0[$rand_key];
				$output_a[0] = implode( ' ', $output_a_0 );
			}

			$output = implode( ')', $output_a );

			// Reset counts
			$count_cs = 0;
			$count_os = 0;
		}

		// Cleanup <3
		unset( $count_cs, $count_os, $cs, $os, $output_a );
	}

	return $output;
}

/**
 * Balance quotes in a tweet
 *
 * This is not a true quote balancer. It's very lazy. It tries to match orphan parens by mirroring
 * them on the other end of the word. It is very buggy and works poorly with multiple sets of
 * quotation marks. Use at your own risk.
 *
 * @param str $output
 * @return str $output
 */
function horse_thatbooks_balance_quotes( $output ) {
	if ( false !== strpos( $output, '"' ) ) {

		// Do it twice: once forward and once backward
		$counter = 0;
		while ( $counter < 2 ) {
			$pos = 0;
			$rpos = 0;
			$len = strlen( $output );

			// In brief:
			// - Walk through the string to look for open-quotes
			// - When one is found, look for a paired close-quote from the end of the
			//   string
			// - If none is found, throw a quote at the end of the word and keep walkin'
			while ( $pos < $len ) {
				if ( $pos === 0 && '"' == substr( $output, 0, 1 ) ) {
					$o_pos = 0;
				} else {
					$o_pos = strpos( $output, ' "', $pos );
				}
				if ( false !== $o_pos ) {
					$c_pos = strrpos( $output, '" ', $rpos );

					if ( false === $c_pos || $o_pos > $c_pos ) {
						preg_match( '/\s/', $output, $eow, PREG_OFFSET_CAPTURE, $o_pos + 1 );
						$eow_pos = isset( $eow[0][1] ) ? $eow[0][1] : false;

						if ( false !== $eow_pos ) {
							$output = substr( $output, 0, $eow_pos ) . '"' . substr( $output, $eow_pos );
						}

						// Reset the string length
						$len++;
					} else {
						$rpos = $rpos - $c_pos;
					}

					$pos = $o_pos + 1;
				} else {
					$pos = $len;
				}
			}

			$output = strrev( $output );
			$counter++;
		}

	}

	return $output;
}

?>