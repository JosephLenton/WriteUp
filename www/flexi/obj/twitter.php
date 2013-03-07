<?
    $flexi->loadFile( 'obj', 'ItemCache' );
	
    /**
     * Class for interacting with Twitter.
     * The class itself will silently use a file behind the scenes
     * if this is called too often. This is to avoid the restriction
     * the number of times you can request tweets from Twitter.com.
     * 
     * Note that the fileName given is just a prefix. The actual file
     * name used will be 'file' + '.' + 'user' + '.csv'.
     * 
     * 'getTweets' returns an array of all Tweets available.
     * Each element in this array is an instance of 'Tweet'.
     * 
     * If something went wrong, or if there just aren't any, then an
     * empty array is returned.
     * 
     * The date is formatted into the style 'Day Month Date, Hour:Minute'.
     * However if the tweet is less then a day old then it will be formatted
     * into number of hours, and number of minutes if it's less then 1 hour old.
     */
    /* 
     * The cached tweets file is in CSV format of: 'date, message'
     */
    class Twitter extends ItemCache
    {
        const POLL_TWEETS = 3;
        const DEFAULT_FILE = "twitter";
        
        const CACHE_TIMEOUT = 2400; // 40 minutes, in seconds
        
        const CSV_SEPERATOR = ',';
        
        const TWEET_DATE_CLASS   = 'tweet_date' ;
        const TWEET_LINK_CLASS   = 'tweet_link' ;
        const TWITTER_USER_CLASS = 'tweet_user' ;
        
        const TWEET_URL_PRE = 'http://twitter.com/';
        const TWEET_URL_POST = '/status/';
        
        const TWITTER_USER_URL = 'http://www.twitter.com/';
        
        const FILE_SEPERATOR = '.';
        const FILE_EXTENSION = ".cache";
        
        const DATE_FORMAT = 'D M j, G:i';
        
        /* 
		 * These are all for unix time comparisons.
		 * They are all in seconds.
		 * 
		 * They don't need to be exact, for example
		 * a month is presumed to always be 30 days.
		 * This is because they are only for display
		 * purposes.
		 */
		
        const ONE_YEAR   = 31536000;
        const ONE_MONTH  = 2628000; // approximate_month = ( seconds_in_a_day * 365 ) / 12
        const ONE_WEEK   = 604800;
        const ONE_DAY    = 86400;
        const ONE_HOUR   = 3600;
        const ONE_MINUTE = 60;
		const JUST_NOW   = 30;
        const ONE_SECOND = 1;
		
		private static function calcDisplayAge( $age, $unit, $divider )
		{
			$val = round( $age / $divider );
			
			if ( $val === 1 ) {
				return '1 ' . $unit . ' ago';
			} else {
				return $val . ' ' . $unit . 's ago';
			}
		}
        
        private static function formatDateCreated( $created )
        {
            $time = strtotime( $created );
            $age = time() - $time;
            
			if ( $age <= Twitter::JUST_NOW ) {
				return "just now";
            } else if ( $age < Twitter::ONE_HOUR ) {
				return Twitter::calcDisplayAge( $age, 'minute', Twitter::ONE_MINUTE );
            } else if ( $age < Twitter::ONE_DAY ) {
				return Twitter::calcDisplayAge( $age, 'hour'  , Twitter::ONE_HOUR   );
			} else if ( $age < Twitter::ONE_DAY*2 ) {
				return 'yesterday';
			} else if ( $age < Twitter::ONE_WEEK ) {
				return Twitter::calcDisplayAge( $age, 'day'   , Twitter::ONE_DAY    );
			} else if ( $age < Twitter::ONE_MONTH ) {
				return Twitter::calcDisplayAge( $age, 'week'  , Twitter::ONE_WEEK   );
			} else if ( $age < Twitter::ONE_YEAR ) {
				return Twitter::calcDisplayAge( $age, 'month' , Twitter::ONE_MONTH  );
            } else {
                return date( Twitter::DATE_FORMAT, $time );
            }
        }
        
        private $user;
        private $pass;
        private $file;

        private $tweetDateClass;
        private $tweetLinkClass;
        private $tweetUserClass;
        
        public function __construct($user, $file=Twitter::DEFAULT_FILE)
        {
			parent::__construct(
					$file . Twitter::FILE_SEPERATOR . $user . Twitter::FILE_EXTENSION,
					Twitter::CACHE_TIMEOUT
			);
			
            if ( ! $user ) {
                throw new Exception( "falsy 'user' value given for user" );
            }

            $this->user = $user;
			
			$this->tweetDateClass = Twitter::TWEET_DATE_CLASS;
			$this->tweetLinkClass = Twitter::TWEET_LINK_CLASS;
			$this->tweetUserClass = Twitter::TWITTER_USER_CLASS;
        }
        
        public function getTweets( $numTweets=Twitter::POLL_TWEETS )
        {
			$response = $this->poll( $numTweets );
            
            if ( $response ) {
                return $this->xmlToTweets( new SimpleXMLElement($response), $numTweets );
            } else {
                return array();
            }
        }
        
        /**
         * Polls Twitter.com for an XML file representing tweets.
         * This is then parsed into an array and returned.
         * 
         * On failure this will return null.
         * 
         * @param numTweets The number of the last tweets to retrieve from twitter.
         */
        protected function pollSource( $numTweets=Twitter::POLL_TWEETS )
        {
            // polls extra ones, to get around the flakey Twitter API
            $numTweets = min( $numTweets*3, 200 );
            
            $c = curl_init();
            curl_setopt($c, CURLOPT_URL, "http://api.twitter.com/1/statuses/user_timeline/" . $this->user . ".xml?count=" . $numTweets . "&include_rts=true" );
            curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($c, CURLOPT_TIMEOUT, 5);
            
            $response = curl_exec($c);
            $responseInfo = curl_getinfo($c);
            curl_close($c);
            
            if ( ((int) ($responseInfo['http_code'])) === 200 ) {
                return $response;
            } else {
                return null;
            }
        }
        
        private function xmlToTweets( $twitter_xml, $max )
        {
            $tweets = array();
            
            foreach ( $twitter_xml->status as $key => $status ) {
                $tweets[]= new Tweet(
                        $this->formatDate($status->created_at, $status->id),
                        $this->formatMessage( $status->text )
                );
                
                $max--;
                if ( $max == 0 ) {
                    break;
                }
            }
            
            return $tweets;
        }
        
        private function formatMessage( $text )
        {
            return $this->tweetIdToLink( $this->htmlEscapeAndLinkUrls($text) );
        }
        
        private function tweetIdToLink( $text )
        {
			$userKlass = $this->tweetUserClass;
			
            return preg_replace_callback(
                    '/@([A-Za-z0-9_]+)/',
					function( $match ) use ( $userKlass ) {
						$name = substr( $match[0], 1 );
						return '<a class="' . $userKlass . '" href="' . Twitter::TWITTER_USER_URL . $name . '" target="_blank">' . $match[0] . '</a>';
					},
                    $text
            );
        }
        
        /**
         *  Transforms plain text into valid HTML, escaping special characters and
         *  turning URLs into links.
         * 
         * Code from: http://www.kwi.dk/projects/php/UrlLinker/source/UrlLinker.php?view
         */
        private function htmlEscapeAndLinkUrls($text)
        {
            $rexProtocol = '(https?://)?';
            $rexDomain   = '((?:[-a-zA-Z0-9]{1,63}\.)+[-a-zA-Z0-9]{2,63}|(?:[0-9]{1,3}\.){3}[0-9]{1,3})';
            $rexPort     = '(:[0-9]{1,5})?';
            $rexPath     = '(/[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]*?)?';
            $rexQuery    = '(\?[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]+?)?';
            $rexFragment = '(#[!$-/0-9:;=@_\':;!a-zA-Z\x7f-\xff]+?)?';
            $rexUrlLinker = "{\\b$rexProtocol$rexDomain$rexPort$rexPath$rexQuery$rexFragment(?=[?.!,;:\"]?(\s|$))}";

            /*
             *  $validTlds is an associative array mapping valid TLDs to the value true.
             *  Since the set of valid TLDs is not static, this array should be updated
             *  from time to time.
             *
             *  List source:  http://data.iana.org/TLD/tlds-alpha-by-domain.txt
             *  Last updated: 2010-09-04
             */
            $validTlds = array_fill_keys(explode(" ", ".ac .ad .ae .aero .af .ag .ai .al .am .an .ao .aq .ar .arpa .as .asia .at .au .aw .ax .az .ba .bb .bd .be .bf .bg .bh .bi .biz .bj .bm .bn .bo .br .bs .bt .bv .bw .by .bz .ca .cat .cc .cd .cf .cg .ch .ci .ck .cl .cm .cn .co .com .coop .cr .cu .cv .cx .cy .cz .de .dj .dk .dm .do .dz .ec .edu .ee .eg .er .es .et .eu .fi .fj .fk .fm .fo .fr .ga .gb .gd .ge .gf .gg .gh .gi .gl .gm .gn .gov .gp .gq .gr .gs .gt .gu .gw .gy .hk .hm .hn .hr .ht .hu .id .ie .il .im .in .info .int .io .iq .ir .is .it .je .jm .jo .jobs .jp .ke .kg .kh .ki .km .kn .kp .kr .kw .ky .kz .la .lb .lc .li .lk .lr .ls .lt .lu .lv .ly .ma .mc .md .me .mg .mh .mil .mk .ml .mm .mn .mo .mobi .mp .mq .mr .ms .mt .mu .museum .mv .mw .mx .my .mz .na .name .nc .ne .net .nf .ng .ni .nl .no .np .nr .nu .nz .om .org .pa .pe .pf .pg .ph .pk .pl .pm .pn .pr .pro .ps .pt .pw .py .qa .re .ro .rs .ru .rw .sa .sb .sc .sd .se .sg .sh .si .sj .sk .sl .sm .sn .so .sr .st .su .sv .sy .sz .tc .td .tel .tf .tg .th .tj .tk .tl .tm .tn .to .tp .tr .travel .tt .tv .tw .tz .ua .ug .uk .us .uy .uz .va .vc .ve .vg .vi .vn .vu .wf .ws .xn--0zwm56d .xn--11b5bs3a9aj6g .xn--80akhbyknj4f .xn--9t4b11yi5a .xn--deba0ad .xn--fiqs8s .xn--fiqz9s .xn--fzc2c9e2c .xn--g6w251d .xn--hgbk6aj7f53bba .xn--hlcj6aya9esc7a .xn--j6w193g .xn--jxalpdlp .xn--kgbechtv .xn--kprw13d .xn--kpry57d .xn--mgbaam7a8h .xn--mgbayh7gpa .xn--mgberp4a5d4ar .xn--o3cw4h .xn--p1ai .xn--pgbs0dh .xn--wgbh1c .xn--xkc2al3hye2a .xn--ygbi2ammx .xn--zckzah .ye .yt .za .zm .zw"), true);
            
            // use an array and implode later, more efficient then string-concat
            $result = array();
			
			$linkClass = $this->tweetLinkClass;
            
            $position = 0;
            while ( preg_match($rexUrlLinker, $text, $match, PREG_OFFSET_CAPTURE, $position) ) {
                list($url, $urlPosition) = $match[0];
                
                // Add the text leading up to the URL.
                $result[]= htmlspecialchars(substr($text, $position, $urlPosition - $position));
               
                $domain = $match[2][0];
                $port   = $match[3][0];
                $path   = $match[4][0];
                
                // Check that the TLD is valid or that $domain is an IP address.
                $tld = strtolower(strrchr($domain, '.'));
                if ( preg_match('{^\.[0-9]{1,3}$}', $tld) || isset($validTlds[$tld]) ) {
                    // Prepend http:// if no protocol specified
                    $completeUrl = $match[1][0] ? $url : "http://$url";
                    
                    // Add the hyperlink.
                    $result[]= '<a class="';
					$result[]= $linkClass;
					$result[]= '" href="';
                    $result[]= htmlspecialchars($completeUrl);
                    $result[]= '" target="_blank">';
                    $result[]= htmlspecialchars("$domain$port$path");
                    $result[]= '</a>';
                } else {
                    // Not a valid URL.
                    $result[]= htmlspecialchars($url);
                }
                
                // Continue text parsing from after the URL.
                $position = $urlPosition + strlen($url);
            }

            // Add the remainder of the text.
            $result []= htmlspecialchars( substr($text, $position) );
            return implode( '', $result );
        }
        
		/**
		 * Sets the class, or classes, to use for when generating
		 * links within tweets.
		 * 
		 * @return this, to allow method chaining.
		 */
		public function tweetLinkClass()
		{
			$this->tweetLinkClass = join( ' ', func_get_args() );
			
			return $this;
		}
        
		/**
		 * Sets the class, or classes, to use for when generating
		 * twitter direct link which shows the date.
		 * 
		 * @return this, to allow method chaining.
		 */
		public function tweetDateClass()
		{
			$this->tweetDateClass = join( ' ', func_get_args() );
			
			return $this;
		}
        
		/**
		 * Sets the class, or classes, to use for when generating
		 * user links in Tweets.
		 * 
		 * @return this, to allow method chaining.
		 */
		public function tweetUserClass()
		{
			$this->tweetUserClass = join( ' ', func_get_args() );
			
			return $this;
		}
		
        private function formatDate( $created, $id )
        {
            return '<a class="' . $this->tweetDateClass . '" href="' . $this->formatTweetLink($id) . '">' . Twitter::formatDateCreated($created) . '</a>';
        }
        
        private function formatTweetLink( $id )
        {
            return Twitter::TWEET_URL_PRE . $this->user . Twitter::TWEET_URL_POST . $id;
        }
    }
    
    class Tweet
    {
        const URL_MATCH = "/<a\s[^>]*href=(\"??)(http[^\" >]*?)\\1[^>]*>(.*)<\/a>/siU";
        
        private $date;
        private $message;
        
        public function __construct( $date, $message )
        {
            $this->date = $date;
            $this->message = $message;
        }
        
        public function getDate()
        {
            return $this->date;
        }
        
        public function getMessage()
        {
            return $this->message;
        }
    }
?>
