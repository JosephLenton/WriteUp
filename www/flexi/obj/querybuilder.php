<?
	/**
	 * On some pages you want to have it store data that can persist to the next page you are going
	 * too. However you do not want this information to be site wide, you just want it from the
	 * current page to the next.
	 * 
	 * So if the user opens up pages in other tabs and then comes back to this page, the persistance
	 * will still work.
	 * 
	 * One of the easiest ways to do this is to store the information in the url to the next page
	 * as a part of the query's get parameters. This is a class for automatically building this.
	 * 
	 * The intention is that parts can be set in one section of your site (such as a controller)
	 * and then outputted somewhere else as a part of an url (such as in a view). This allows the
	 * section outputting the generated queries within their urls to only need access to this object
	 * and not care about what values are set or not set.
	 */
	class QueryBuilder extends \flexi\Obj
	{
		private $query;
		private $queryMap;
		
		/**
		 * Standard constructor, just makes a new blank query object.
		 */
		public function __construct()
		{
			$this->clearHref();
		}
		
		/**
		 * A helper function to retrieve the value currently stored under $_GET.
		 * The value is run through htmlspecialchars before it is returned.
		 * 
		 * If the value was not found then the alt parameter is returned.
		 * 
		 * @param key The name of the value to look for and return.
		 * @param alt An alternate value to return if the key'd value is not found.
		 * @return The value stored underneath the key, or the alternate value if this is not found.
		 */
		public function get( $key, $alt=false )
		{
			if ( isset($_GET[$key]) ) {
				return htmlspecialchars( $_GET[$key] );
			} else {
				return $alt;
			}
		}
		
		/**
		 * Moves the key currently stored from the request for this page (in $_GET)
		 * into the request for the next. If there isn't one found then it is replaced
		 * with the alt value given.
		 * 
		 * The value persisted is also returned so you can both persist and get the value in one.
		 * 
		 * By default the alt is false and so if the key is not stored then false
		 * will also be returned.
		 * 
		 * @param key The key for the value being stored.
		 * @param alt False by default, an alternate value to store against the key.
		 * @return The value for the key given which is being persisted.
		 */
		public function persist( $key, $alt=false )
		{
			$val = $this->get( $key, $alt );
			$this->setHref( $key, $val );
			
			return $val;
		}
		
		/**
		 * Returns the key value currently stored for any queries generated by this object.
		 * The alt is an alternate value to return if a value is not found against the key
		 * given.
		 * 
		 * @param key The key to lookup a value for.
		 * @param alt An alternate value to return if the key is not found.
		 * @return The value stored against key, or alt if this is not found.
		 */
		public function getHref( $key, $alt=false )
		{
			if ( isset($this->queryMap[ $key ]) ) {
				return $this->queryMap[ $key ];
			} else {
				return $alt;
			}
		}
		
		/**
		 * Removes all values currently stored for the next query being generated.
		 */
		public function clearHref()
		{
			$this->query = null;
			$this->queryMap = array();
		}
		
		/**
		 * Sets the key value set to be appended for any generated urls.
		 * This will be appended as 'key=val'.
		 * 
		 * @param key The key for the query item.
		 * @param val The value for the query item.
		 */
		public function setHref( $key, $val )
		{
			$this->queryMap[ $key ] = $val;
			$this->query = null;
		}
		
		/**
		 * This is for removing any key/value pairs from this Query object so they
		 * won't appear in any generated urls.
		 * 
		 * @param Key The key for the value to remove from any generated queries.
		 */
		public function removeHref( $key )
		{
			unset( $queryMap[$key] );
			$query = null;
		}
		
		/**
		 * This echo's a whole anchor tag generated from the info given.
		 * It's url has the query items stored in this object appended to it
		 * and then placed as the href for the anchor.
		 * 
		 * The inside parameter is appended to inside the anchor as it's content.
		 * 
		 * The attributes are appended in the inside of the opening part of the
		 * anchor and are meant to be things like class, id, etc. (i.e.
		 * 'class="special_link" id="title_link"').
		 * 
		 * @param url Where the anchro tag is pointing too.
		 * @param inside The content to appear inside of the anchor, by default this is empty.
		 * @param attributes to appear in the anchor tag itself, by default this is empty.
		 * @return The generated anchor tag from the information given.
		 */
		public function a( $url, $inside='', $attributes='' )
		{
			echo '<a ' . $attributes . ' href="' . $this->href($url) . '" >' .
					$inside .
			'</a>';
		}
		
		/**
		 * Essentially returns the given url + any stored query items.
		 * The given url can optionally return it's own query items
		 * (i.e. '?blah=foo&foo=bar').
		 * 
		 * @param url The url to concat the query onto.
		 * @return An url containing the url given plus the query items to include.
		 */
		public function href( $url )
		{
			$strQuery = $this->getQueryStr();
			
			if ( $strQuery === '' || $strQuery == null ) {
				return $url;
			} else if ( stristr($url, '?') === false ) {
				return $url . '?' . $strQuery;
			} else {
				return $url . '&' . $strQuery;
			}
		}
		
		/**
		 * Concatonates all of the values stored for the next query to take place
		 * into a string and returns the result.
		 */
		private function getQueryStr()
		{
			if ( $this->query == null ) {
				$this->query = http_build_query( $this->queryMap );
			}
			
			return $this->query;
		}
	}
?>