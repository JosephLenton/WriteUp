<?
	/**
	 * This is a simple caching class, which is expected to be built on top of.
	 * The idea is that it allows you to build simple file based caching
	 * systems.
	 * 
	 * For example you could build on top of this a Twitter cache.
	 * When you poll for data, it will poll the source, which in this
	 * example will send a request to the Twitter servers. This class
	 * then automatically saves the result.
	 * 
	 * If this is polled again before the age runs out, then the contents
	 * of the cache is returned.
	 * 
	 * Another example is to use this to store the latest blog posts
	 * from a wordpress install, to avoid loading all of Wordpress core,
	 * and performing several DB requests.
	 * 
	 * What this class provides is the common code for deciding if you
	 * should poll the filesystem, or the source, and to save any polled
	 * data to disk.
	 * 
	 * That way all you have to care about is connecting up the source,
	 * providing the cache age and file name, and deciding how the output
	 * should look.
	 */
	abstract class ItemCache extends \flexi\Obj
	{
		private $file;
		private $cacheTimeout;
		
		/**
		 * @param filename
		 * @param age How long this will cache for, in seconds.
		 */
		public function __construct( $filename, $age )
		{
			$this->file = $filename;
			$this->cacheTimeout = $age;
		}
		
		/**
		 * @return The file name of the cache.
		 */
		public function getFileName()
		{
			return $this->file;
		}
		
		/**
		 * If the cache is valid, it is polled, and it's contents returned.
		 * 
		 * If the cache is not valid, then the source is polled. The source
		 * value is then stored, and returned.
		 * 
		 * However the source is skipped if it returns null. In that case
		 * the cache is returned anyway, since the source is presumed to have
		 * failed.
		 * 
		 * If everything fails, then null is returned.
		 */
		public function poll()
		{
            if ( $this->isPollCache() ) {
                $cache = $this->pollCache();
                
				return $this->processData( $cache );
            } else {
                $args = func_get_args();

                if ( func_num_args() === 0 ) {
                    $content = $this->pollSource();
                } else {
                    $content = call_user_func_array( array($this, 'pollSource'), func_get_args() );
                }
                
                if ( $content != null ) {
                    $this->saveCache( $content );
                    
					return $this->processData( $content );
                } else {
                    $content = $this->pollCache();
                    
                    if ( $content != null ) {
                        return $this->processData( $content );
                    } else {
                        return null;
                    }
                }
            }
        }

		/**
		 * If the cache exists, then it's contents is returned.
		 * This will always happen even if the cache is invalid.
		 * 
		 * If anything goes wrong, then null will be returned.
		 * 
		 * In practice you shouldn't need to call this manually,
		 * instead you call 'poll' as that will only call this
		 * if the cache is still valid.
		 * 
		 * This is provided incase you want to manage polling
		 * manually, or if you ever want to grab the cache
		 * regardless of if it's valid or not.
		 * 
		 * @return The contents of the cache, or null if something goes wrong.
		 */
        protected function pollCache()
        {
            if ( file_exists($this->file) ) {
                $contents = file_get_contents( $this->file );
                
                if ( $contents != false ) {
                    return json_decode( $contents );
                } else {
                    return null;
                }
            } else {
                return null;
            }
        }
		
		/**
		 * Returns the items that this cache is storing, from the
		 * expensive source where it all comes from. This could be
		 * polling a webserver, or grabbing items out of a DB,
		 * or something else.
		 * 
		 * Return null with this to state if it fails to return a
		 * value.
		 * 
		 * The poll method will call this for you, and also handle
		 * storing the return value for you too.
		 * 
		 * @return The contents from the source.
		 */
		abstract protected function pollSource();
		
		/**
		 * By default, this just returns the polled data from either the
		 * source or the cache.
		 * 
		 * Override it to be able to alter the data before it is returned.
		 * This is called for data returned from both the cache and the
		 * source.
		 */
		protected function processData( $data )
		{
			return $data;
		}
		
        /**
         * Saves the content given to the cache.
         * 
         * @param content The content to place in the cache.
         */
        protected function saveCache($content)
        {
			file_put_contents( $this->file, json_encode($content) );
        }
		
		/**
		 * Deletes the cache.
		 */
		protected function clearCache()
		{
			@unlink( $this->file );
		}
		
        /**
         * True if the cache should be polled, false if not and twitter should be used instead.
         * This will also return false if the cache does not exist or if it is out of date.
         * 
         * If this returns true then you can presume the cache is all safe to use and
         * you will get cache back. If the cache exists but is empty, then this still returns
         * true (as the cache is working fine).
         * 
         * @return If you should poll the cahce or not.
         */
        protected function isPollCache()
        {
            if ( file_exists($this->file) ) {
                $lastModified = filemtime( $this->file );
                
                return ( time() - $lastModified ) < $this->cacheTimeout ;
            } else {
                return false;
            }
        }
	}
