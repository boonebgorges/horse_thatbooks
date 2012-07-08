<?php

/**
 * Utility functions for @horse_thatbooks
 */

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

?>