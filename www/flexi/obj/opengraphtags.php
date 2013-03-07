<?
    /**
     * Generate the open graph tags class.
     */
    class OpenGraphTags extends \flexi\Obj
    {
        private static function ensureOgTag( $key ) {
            /*
             * Ensure it's a string, just incase it's an object with a toString,
             * so it always gets coerced here!
             */
            $key = (string) $key;

            if ( strpos($key, ':') === false ) {
                return 'og:' . $key;
            } else {
                return $key;
            }
        }

        private $rootUrl;
        private $tags;
        
        /**
         * Example Usage:
         *
            parent::__construct( array(
                'description' => 'welcome to my site',
                'title' => 'My Site'
            ));

            parent::__construct( 'http://example.com', array(
                'description' => 'welcome to my site',
                'title' => 'My Site',
                'url' => '/home'
            ));

            parent::__construct( 'http://example.com' );
         */
        public function __construct( $url=null, $defaultTags=null )
        {
            if ( $defaultTags === null && is_array($url) ) {
                $defaultTags = $url;
                $url = '';
            }

            if ( $url === null ) {
                $url = '';
            }

            $this->rootUrl = $url;
            $this->tags = null;

            if ( $defaultTags ) {
                $this->setTags( $defaultTags );
            }

            $this->setTag( 'og:type', 'website' );
        }
        
        function generateHTML() {
            $openGraphTags = '';

            if ( $this->tags !== null ) {
                foreach( $this->tags as $key => $value ) {
                    $openGraphTags .= '<meta property="' .$key . '" content="' . $value . '" />';
                }
            }

            return $openGraphTags;
        }

        function setUrl( $url ) {
            $this->rootUrl = $url;

            return $this;
        }

        /**
         * The same as 'set'.
         * 
         * It was originally called 'setTag', but 'set' is a better name,
         * and this still exists to avoid breaking code.
         */
        function setTag( $key, $val ) {
            return $this->set( $key, $val );
        }

        function set( $key, $val ) {
            if ( $key === 'og:url' ) {
                $val = $this->rootUrl . $val;
            }

            $key = OpenGraphTags::ensureOgTag( $key );

            return $this->setDirect( $key, $val );
        }

        function get( $key, $alternative='' ) {
            $key = OpenGraphTags::ensureOgTag( $key );

            if ( isset($this->tags[$key]) ) {
                return $this->tags[$key];
            } else {
                return $alternative;
            }
        }

        /**
         * This does no changes to the $key value,
         * no appending of 'og:' before it.
         */
        function setDirect( $key, $val ) {
            $this->tags[ (string) $key ] = $val;

            return $this;
        }
        
        function setTags( $tags ) {
            foreach ( $tags as $k => $v ) {
                $this->set(
                        OpenGraphTags::ensureOgTag( $k ),
                        $v
                );
            }

            return $this;
        }

        /**
         * If the tag has been set,
         * then it will be removed.
         * 
         * Otherwise this silently does nothing.
         */
        function remove( $key ) {
            $key = OpenGraphTags::ensureOgTag( $key );

            return $this->removeDirect( $key );
        }

        function removeDirect( $key ) {
            unset( $this->tags[(string) $key] );

            return $this;
        }
    }
