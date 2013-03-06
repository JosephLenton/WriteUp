<?php if ( ! defined('ACCESS_OK')) exit('Can\'t access scripts directly!');
    /**
     * String.php
     * 
     * Extra string functions. Things like 'endsWith', 'truncate', 'relativeDate',
     * and other random functions which can be useful.
     */

    /**
     * Converts whitespace in the string into HTML alternatives.
     * This is useful for preserving whitespace, past CSS and past bugs (FireFox).
     * 
     * WARNING! This does not make the content HTML safe!
     */
    function htmlWhitespace( $str ) {
        // The order on this is important, this must be first,
        // or it breaks the new lines (don't know why).
        $str = preg_replace(
                '/ /',
                '&nbsp;',
                $str
        );
        
        $str = nl2br( $str, true );
        
        $str = preg_replace(
                '/\r|\n/',
                '',
                $str
        );
        
        return $str;
    }

    /**
     * Shorthand for htmlspecialchars.
     * 
     * Returns the given string, after it has been run through 'htmlspecialchars'.
     * This is so you can easily print strings in HTML.
     * 
     * Example Usage:
     * 
     *  <h1><?= h( $title ) ?></h1>
     * 
     * This returns an empty string if a false-y value is given.
     * 
     * @param str The string to wrap.
     * @return The string run through htmlspecialchars, or an empty string if it's false-y.
     */
    function h( $str ) {
        if ( $str ) {
            return htmlspecialchars( $str );
        } else {
            return '';
        }
    }

    function endsWith($string, $test, $caseInSensetive = true) {
        $strlen = strlen($string);
        $testlen = strlen($test);
        if ($testlen > $strlen) return false;
        return substr_compare($string, $test, -$testlen, $testlen, $caseInSensetive) === 0;
    }
    
    function truncate( $str, $length, $truncateMaxLines = 1, $appendString = "..." )
    {
		if( is_string($str) ) {
            // ensure content is no longer then 3 lines long
            $contentLines = explode( "\n", trim($str) );
            
            // The output is no more then 3 lines long,
            // so we go through each line we might use and ensure it's not
            // going to wrap around. 
            // Cos if it wraps around then it's 2 lines, not 1.
            $singleLineLength = floor( $length / $truncateMaxLines );
            $maxLines = $truncateMaxLines;
            
            $iMax = min( $maxLines, count($contentLines) );
            for ( $i = 0; $i < $iMax; $i++ ) {
                $maxLines -= floor( strlen($contentLines[$i]) / $singleLineLength );
            }
            $maxLines = max( $maxLines, 1 );
            $totalNumLines = count( $contentLines );
            
            // then we repack the lines we've counted
            $content = implode(
                    "\n",
                    array_slice( $contentLines, 0, $maxLines )
            );
            
            // Finally, just ensure our resulting content isn't one giant line
            if ( strlen($content) > $length ) {
                $appendStringLen = strlen( $appendString );
                return substr($content, 0, $length - $appendStringLen).$appendString;
            } else if ( $totalNumLines > $maxLines ) {
                return trim( $content ) . $appendString;
            } else {
                return $content;
            }
		} else {
			throw new Exception("non-string supplied to truncate()");
		}
	}
    
    function relativeDate( $date )
    {
        // all of these are in seconds
        $ONE_MINUTE = 60;
        $ONE_HOUR   = $ONE_MINUTE * 60;
        $ONE_DAY    = $ONE_HOUR   * 24;
        $ONE_WEEK   = $ONE_DAY    * 7 ;
        
        $ONE_MONTH  = $ONE_DAY * 30.4 ; // average length of a month in seconds
        $ONE_YEAR   = $ONE_DAY * 365.25; // average length of a month in seconds
        
        // maximum age is 14 months ago
        $MAX_AGE    = $ONE_MONTH * 13;
        
        $DATE_FORMAT = 'D M j, G:i';
        
        $time = strtotime( $date );
        $age = time() - $time;
        
        if ( $age == 0 ) {
            return 'just now';
        } else {
            // The multiplier (i.e. * 0.9) is so there is a little leeway on the test.
            $TEST_MULT = 0.95;
            
			if ( $age < 30 ) {
				return 'just now!';
			} else if ( $age < $ONE_MINUTE ) {
                return pluralTest( $age, 'second ago', 'seconds ago' );
            } else if ( $age < $ONE_HOUR ) {
                $val = round( $age / $ONE_MINUTE );
                return pluralTest( $val, 'minute ago', 'minutes ago' );
            } else if ( $age < $ONE_DAY*$TEST_MULT ) {
                $val = round( $age / $ONE_HOUR );
                return pluralTest( $val, 'hour ago', 'hours ago' );
            } else if ( $age < $ONE_WEEK*$TEST_MULT ) {
                $val = round( $age / $ONE_DAY );
                
                if ( $val == 1 ) {
                    return 'yesterday';
                } else {
                    return $val . ' days ago';
                }
            } else if ( $age < $ONE_MONTH*$TEST_MULT ) {
                $val = round( $age / $ONE_WEEK );
                return pluralTest( $val, 'week ago', 'weeks ago' );
            } else if ( $age < $ONE_YEAR*$TEST_MULT ) {
                $val = round( $age / $ONE_MONTH );
                return pluralTest( $val, 'month ago', 'months ago' );
            } else if ( $age < $MAX_AGE ) {
                return '1 year ago';
            } else {
                return date( $DATE_FORMAT, $time );
            }
        }
    }
    
    function pluralTest( $amount, $singular, $plural )
    {
        if ( $amount <= 1 ) {
            return $amount . ' ' . $singular;
        } else {
            return $amount . ' ' . $plural;
        }
    }
?>