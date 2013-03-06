<?php
    /**
     * Browser
     * 
     * Using the user agent string, this will attempt to detect what platform
     * and browser the user is using.
     * 
     * This does _NOT_ guarantee the platform/browser combination,
     * because there is nothing to stop the user from lying.
     * Also some browsers, such as Opera, sometimes advertise themselves as
     * more popular browsers (such as IE).
     * 
     * In a typical use case, these functions are for deciding on the layout
     * and styling for your page, so you can offer a mobile specific version.
     */
    
    /**
     * To a large degree, you never need to use this function.
     * Instead you should use the other helper functions in browser
     * which work on top of this.
     * 
     * @param agent Optional, the user agent string to parse. If not provided then this will grab the information from the server.
     * @return An array of browser details.
     */
    function getBrowser( $user_agent )
    {
        $bname    = 'Unknown';
        $platform = 'Unknown';
        $isMobile = false;
        $isTouch  = false;
        $version  = "";

        // The below checks should be ordered in terms of likelyhood.
        
        if ( $user_agent !== '' ) {
            // First get the platform
            if (preg_match('/windows|win32/i', $user_agent)) {
                $platform = 'windows';
            /* 
             * Mac, and 'like mac' devices
             * 
             * iOS reports as being 'mac-like'.
             */
            } else if (preg_match('/macintosh|mac os x/i', $user_agent)) {
                if (
                        stripos($user_agent, 'iphone') !== false ||
                        stripos($user_agent, 'ipod') !== false
                ) {
                    $platform = 'iphone';
                    $isMobile = true;
                    $isTouch  = true;
                } else if ( stripos($user_agent, 'ipad') !== false ) {
                    $platform = 'ipad';
                    $isTouch  = true;
                } else {
                    $platform = 'mac';
                }
            } else if ( stripos($user_agent, 'android') !== false ) {
                $platform = 'android';
                $isTouch  = true;
                
                if ( stripos($user_agent, 'mobile') !== false ) {
                    $isMobile = true;
                }
            } else if (preg_match('/linux/i', $user_agent)) {
                $platform = 'linux';
            } else if (preg_match('/(windows ce; ppc;|windows ce; smartphone;|windows ce; iemobile)/i', $user_agent)) {
                $platform = 'windows_mobile';
                $isMobile = true;
                $isTouch  = true;
            } else if ( stripos($user_agent, 'blackberry') !== false ) {
                $platform = 'blackberry';
                $isMobile = true;
                $isTouch  = true;
            } else if (preg_match('/(palm os|hiptop|avantgo|blazer|xiino|plucker|palm|elaine)/i', $user_agent)) {
                $platform = 'palm';
                $isMobile = true;
                $isTouch  = true;
            // search engines
            } else if ( stripos($user_agent, 'Googlebot') !== false ) {
                $platform = 'web_crawler';
                $bname    = "google_bot";
                $ub       = "Googlebot";
            } else if ( stripos($user_agent, 'bingbot') !== false ) {
                $platform = 'web_crawler';
                $bname    = "bing_bot";
                $ub       = "bingbot";
            } else if ( stripos($user_agent, 'Yahoo! Slurp') !== false ) {
                $platform = 'web_crawler';
                $bname    = "yahoo_slurp";
                $ub       = "Yahoo! Slurp";
            }
            
            // This is set to the name of the browser, as it should appear, in the User Agent string.
            // Right after that name, is the version number, and this is used to grab that version laterz.
            $ub = '';
            
            // Next get the name of the useragent yes seperately and for good reason
            if( preg_match('/MSIE/i',$user_agent) && !preg_match('/Opera/i',$user_agent) ) {
                $bname = 'ie';
                $ub = "MSIE";
                
                // iemobile also reports as MSIE, so it's caught here
                if ( stripos($user_agent, 'iemobile') !== false ) {
                    $bname = 'iemobile';
                    $ub = 'IEMobile';
                    $isMobile = true;
                    $isTouch  = true;
                }
            } elseif(preg_match('/Firefox/i',$user_agent)) {
                $bname = 'firefox';
                $ub = "Firefox";
            } elseif(preg_match('/Chrome/i',$user_agent)) {
                $bname = 'chrome';
                $ub = "Chrome";
            } elseif(preg_match('/Safari/i',$user_agent)) {
                $bname = 'safari';
                $ub = "Safari";
            } elseif(preg_match('/Opera/i',$user_agent)) {
                $bname = 'opera';
                $ub = "Opera";
                
                if ( stripos($user_agent, 'opera mini') !== false ) {
                    $bname = 'opera_mini';
                    $ub = 'Opera Mini';
                    $isMobile = true;
                    $isTouch  = true;
                }
            } elseif(preg_match('/Netscape/i',$user_agent)) {
                $bname = 'netscape';
                $ub = "Netscape";
            }
           
            // finally get the correct version number
            $known = array('Version', $ub, 'other');
            $pattern = '#(?<browser>' . join('|', $known) .
            ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
            if (!preg_match_all($pattern, $user_agent, $matches)) {
                // we have no matching number just continue
            }
           
            // see how many we have
            $i = count($matches['browser']);
            if ( $i > 0 ) {
                if ($i === 1) {
                    $version = $matches['version'][0];
                } else {
                    //we will have two since we are not using 'other' argument yet
                    //see if version is before or after the name
                    if ( strripos($user_agent, "Version") < strripos($user_agent, $ub) ) {
                        $version = $matches['version'][0];
                    } else {
                        $version = $matches['version'][1];
                    }
                }
            }
           
            // check if we have a number
            if ( $version !== null && $version !== '' ) {
                $version_parts = explode( '.', $version );
                
                $first = array_shift( $version_parts );
                $version = floatval( $first . '.' . implode( $version_parts, '' ) );
            } else {
                $version = null;
            }
        }
        
        return array(
                'user_agent'    => $user_agent,
                'name'          => $bname,
                'version'       => $version,
                'platform'      => $platform,
                'pattern'       => $pattern,
                'is_mobile'     => $isMobile,
                'is_touch'      => $isTouch
        );
    } 
    
    global $_flexi_browserInfo;
    $_flexi_browserInfo = getBrowser(
            isset( $_SERVER['HTTP_USER_AGENT'] ) ?
                    $_SERVER['HTTP_USER_AGENT'] :
                    ''
    );

    /**
     * @return A simplified, lower-case, name for the users browser.
     */
    function browser() {
        global $_flexi_browserInfo;
        return $_flexi_browserInfo['name'];
    }
    
    /**
     * @return A simplified, lower-case, name for the users platform.
     */
    function platform() {
        global $_flexi_browserInfo;
        return $_flexi_browserInfo['platform'];
    }
    
    /**
     * @return The browser version number, as a number, or null if this could not be worked out.
     */
    function version() {
        global $_flexi_browserInfo;
        return $_flexi_browserInfo['version'];
    }
    
    /**
     * Devices which are very similar to a mobile phone,
     * is pretty much just the iPod.
     * 
     * This will return false for Android tablet PC's, and the iPad.
     * 
     * @return True if the user is using a mobile phone, or very similar, device.
     */
    function isMobile() {
        global $_flexi_browserInfo;
        return $_flexi_browserInfo['is_mobile'];
    }

    /**
     * Returns true or false if it believes the device
     * is 'touch-oriented'. This doesn't mean touch is
     * supported or not, but that we believe touch is
     * probably a first class citizen.
     * 
     * For example iPhone, iPad and Android.
     * 
     * Note that this is a leap of faith, and is intended
     * to only work for popular devices and setups.
     * 
     * For example Windows with a touch screen is _not_
     * reporting as being touch-oriented. Neither is a
     * Mac with a swanky Apple touch pad. This is because
     * keyboard and mouse is still dominent on those
     * platforms.
     * 
     * Conversely an iPad with a keyboard plugged in *is*
     * still considered to be touch oriented, because we
     * can't detect the keyboard.
     * 
     * For corner case (unpopular) devices, this is
     * a wild shot in the dark based on probably support.
     * For example Blackberry *is* touch oriented because
     * modern ones are. This is even though older ones are
     * not.
     * 
     * Android is touch-oriented, but due to fragmentation,
     * who knows what will happen!
     * 
     * @return True if this believes that touch comes first on this device, and false if not.
     */
    function isTouch() {
        global $_flexi_browserInfo;
        return $_flexi_browserInfo['is_touch'];
    }
    
    function isBrowser( $name ) {
        return ( browser() == $name );
    }
    
    function isPlatform( $name ) {
        return ( platform() == $name );
    }
    
    /**
     * Note that this only checks for common web crawlers,
     * it does not take into account all of them.
     * That is namely Google, Bing and Yahoo.
     * 
     * @return True if the request is from a web crawler, otherwise false.
     */
    function isWebCrawler() {
        return isPlatform( 'web_crawler' );
    }
    
    function isGooglebot() {
        return isBrowser( 'google_bot' );
    }
    
    function isBingbot() {
        return isBrowser( 'bing_bot' );
    }
    
    function isYahooSlurp() {
        return isBrowser( 'yahoo_slurp' );
    }
    
    /**
     * Note that iPhone includes the iPod,
     * as the browsing-experience is the same (or very similar).
     * 
     * For a similar reason, this does _not_ include the iPad,
     * as screen size and performance make the experience very different.
     * 
     * @see isIPad()
     * @return True if the page is being viewed by an iPod or iPhone.
     */
    function isIPhone() {
        return isPlatform( 'iphone' );
    }
    
    /**
     * @return True if the page is being viewed on an iPad, false if not.
     */
    function isIPad() {
        return isPlatform( 'ipad' );
    }
    
    /**
     * @return True if the user is using the desktop version of IE.
     */
    function isIE() {
        return isBrowser( 'ie' );
    }
    
    function isIEMobile() {
        return isBrowser( 'iemobile' );
    }
    
    /**
     * @return True if the user is using the desktop version of FireFox.
     */
    function isFirefox() {
        return isBrowser( 'firefox' );
    }
    
    function isSafari() {
        return isBrowser( 'safari' );
    }
    
    function isChrome() {
        return isBrowser( 'chrome' );
    }
    
    /**
     * @return True if the user is using the desktop version of Opera.
     */
    function isOpera() {
        return isBrowser( 'opera' );
    }
    
    function isNetscape() {
        return isBrowser( 'netscape' );
    }
    
    function isOperaMini() {
        return isBrowser( 'opera_mini' );
    }
    
    function isBlackberry() {
        return isPlatform( 'blackberry' );
    }
    
    /**
     * Note, this returns true for Android Tablet devices.
     * To check for just android mobiles, use 'isAndroidMobile'.
     * 
     * @return True if the device is an Android powered device, false if not.
     */
    function isAndroid() {
        return isPlatform( 'android' );
    }
    
    /**
     * @return True if the device is an Android phone, false if not.
     */
    function isAndroidMobile() {
        return isAndroid() && isMobile();
    }
