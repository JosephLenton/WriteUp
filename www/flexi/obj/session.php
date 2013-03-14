<?
    /**
     * A wrapper for simplifying using Sessions, and to make them more object
     * oriented.
     *
     * Creating an instance of the Session starts the current session. You can
     * destroy it using the 'destroy' method.
     *
     * To set and get session fields you just access them as properties on this
     * Session object.
     * 
     * There are two types of cookies in play by this:
     * 
     *  - the session cookie, used for the current browser being open.
     *  - the optional 'remember me' cookie, for remembering when browser closes.
     * 
     * The first will always be set, when this is used. The latter is only used
     * when you explicitely set a 'remember cookie' name.
     */
    class Session extends \flexi\Obj
    {
        /**
         * The name of the cookie used, for 'remember' functionality.
         */
        private $_rememberCookie;
        private $_rememberExpire;
        private $_rememberSalt;

        private $_sessionLifetime;
        private $_sessionHttponly;
        private $_sessionDomain;
        private $_sessionPath;
        private $_sessionSecure;

        private $_started;
        
        /**
         * cookieSettings is expected to be an array, with element keys
         * expected to match those used by the underlying PHP session.
         * These are: lifetime, path, domain, secure and httponly.
         * 
         * @param cookieSettings Optional parameter to set the cookie params.
         * @param startSession Optional, pass in true to start a session now, otherwise false (the default) if you want to call 'start' manually later.
         */
        public function __construct()
        {
            $this->_started = false;

            $this->_sessionLifetime = 0;
            $this->_sessionHttponly = true;
            $this->_sessionDomain   = '';
            $this->_sessionPath     = '/';
            $this->_sessionSecure   = false;

            $this->_rememberCookie = false;
            $this->_rememberExpire = 31536000; // 1 year in seconds
            $this->_rememberSalt   = false;
        }

        /**
         * Setting a cookie name for this, will cause the 'remember me' cookie
         * to be turned on.
         * 
         * @param $name The name for the cookie.
         * @param $salt A static salt for hashing.
         * @param $expire optional, how long until this expires. Defaults to one year.
         * @return This object.
         */
        public function setupRememberCookie( $name, $salt, $expire=31536000 )
        {
            $this->_rememberCookie = $name;
            $this->_rememberSalt   = $salt;
            $this->_rememberExpire = $expire;

            return $this;
        }
        
        /**
         * This has to be public, to get around PHP's scoping rules. : (
         */
        public function hashValues( $args )
        {
            $preHash = '';

            if ( count($args) === 0 ) {
                throw new Exception( "no arguments provided" );
            } else if ( count($args) === 1 ) {
                $preHash = ((string)$args[0]) + $this->_rememberSalt;
            } else {
                for ( $i = 0; $i < count($args); $i++ ) {
                    $args[$i] = (string) $args[$i];
                }

                $preHash = implode( $this->_rememberSalt, $args );
            }

            return md5( $preHash );
        }

        /**
         * This sets the 'remember me' cookie, with the details given, so this
         * will be remembered for the future.
         * 
         * The $id value is *public*, and pre-pended to the cookie.
         * 
         * The details are hashed, and should contain a 'shared secret', i.e.
         * the users password and e-mail address. The resulting hash will be
         * stored in the users cookie.
         * 
         * Given multiple parameters, this will hash them all together, along
         * with the static salt.
         * 
         * @param id The publically known id associated with the current user.
         * @param hashVals One or more values to hash, for validating the cookie later.
         */
        public function rememberSession( $id )
        {
            if ( $this->_rememberSalt === false ) {
                throw new Exception( "no salt set for session 'remember me' cookie" );
            }

            $hashVals = array();
            for ( $i = 1; $i < func_num_args(); $i++ ) {
                $hashVals[]= func_get_arg( $i );
            }

            $this->setRememberCookie(
                    $id . '_' . $this->hashValues( $hashVals ),
                    time() + $this->_rememberExpire
            );
        }

        private function setRememberCookie( $cookie, $expires )
        {
            setcookie(
                    $this->_rememberCookie,
                    $cookie,
                    $expires,
                    '/'  , null,
                    false, true
            );
        }
        
        /**
         * Forces the session to start.
         * 
         * This is called automatically when you interact with the session class,
         * but sometimes you just want to ensure it is definitely started before
         * the headers are sent.
         * 
         * You can do that by calling this method at the start of your script.
         */
        /*
         * This is to avoid starting the session on every request,
         * such as JSON calls,
         * and any other request where the session is not needed.
         * 
         * This should be called by every other method in this class,
         * if it is going to be interacting with the underlying PHP session.
         * 
         * Don't worry about calling it multiple times!
         */
        public function start()
        {
            if ( ! $this->_started ) {
                $this->_started = true;

                if ( ! $this->startSession() ) {
                    $this->startRememberMe();
                }
            }

            return $this;
        }

        /**
         * @return True if this has a session already, before it was started.
         */
        private function startSession()
        {
            $hasSession = ( session_id() !== '' );

            session_start();
        
            session_set_cookie_params(
                    $this->_sessionLifetime,
                    $this->_sessionPath,
                    $this->_sessionDomain,
                    $this->_sessionSecure,
                    $this->_sessionHttponly
            );

            return $hasSession;
        }

        /**
         * Override this!
         * 
         * This implements the final check, for the 'remember me' functionality.
         * 
         * It is given the $id used when setting up the cookie, and a $hashFun
         * used for validating the hash.
         * 
         * You call the hashFun, with the shared secret arguments given when
         * setting up the remember me cookie, and it returns true or false if
         * those values still match.
         * 
         * This in turn needs to return true, to say everything is ok. If it
         * fails to, then the session for this user, and the remember cookie,
         * will both be removed.
         * 
         * @param id The id used when the cookie was set.
         * @param testFun A test used to validate if the cookie's settings were correct or not.
         * @return True if the 'remember me' is accepted, false if rejected.
         */
        protected function onRememberMe( $id, $testFun )
        {
            throw new Exception( "onRememberMe has not been overridden" );
        }

        /**
         * Attempts to login via the 'remember me' cookie.
         */
        private function startRememberMe()
        {
            if ( $this->_rememberCookie !== false ) {
                if ( isset($_COOKIE[$this->_rememberCookie]) ) {
                    $idHash = $_COOKIE[ $this->_rememberCookie ];
                    $parts = explode( '_', $idHash, 2 );
                    
                    if ( count($parts) === 2 ) {
                        $id   = $parts[0];
                        $hash = $parts[1];

                        $self = $this;

                        $hashTest = function() use ( $self, $hash ) {
                            return $self->hashValues( func_get_args() ) === $hash;
                        };

                        if ( $this->onRememberMe($id, $hashTest) ) {
                            return true;
                        }
                    }

                    $this->destroy();
                }
                
                return false;
            }
        }

        /**
         * Note that the underlying sessions will be shared amongst
         * all Session objects.
         * 
         * So if Session A starts, Session B is also started.
         * 
         * @return True if a session has already been started, false if not.
         */
        public function isStarted()
        {
            return $this->_started;
        }

        public function __get( $field )
        {
            $this->start();
            
            return isset( $_SESSION[$field] ) ?
                    $_SESSION[$field] :
                    null ;
        }

        public function __set( $field, $val )
        {
            $this->start();
            $_SESSION[$field] = $val;
        }

        public function __isset( $field )
        {
            $this->start();
            return isset( $_SESSION[$field] );
        }

        public function __unset( $field )
        {
            $this->start();
            unset( $_SESSION[$field] );
        }

        public function setSessionLifetime( $lifetime )
        {
            $this->_sessionLifetime = $lifetime;

            return $this;
        }

        public function setSessionHttponly( $httponly )
        {
            $this->_sessionHttponly = $httponly;

            return $this;
        }

        public function setSessionDomain( $domain )
        {
            $this->_sessionDomain = $domain;

            return $this;
        }

        public function setSessionPath( $path )
        {
            $this->_sessionPath = $path;

            return $this;
        }

        public function setSessionSecure( $secure )
        {
            $this->_sessionSecure = $secure;

            return $this;
        }
       
        public function setID($id)
        {
            $this->start();
            
            session_id( $id );

            return $this;
        }
 
        public function getID()
        {
            $this->start();
            
            return session_id();
        }

        /**
         * Destroys the current session, and the remember cookie.
         * 
         * This is the same as calling both 'destroySession' and
         * 'destroyRememberMe', in turn.
         */
        public function destroy()
        {
            $this->destroySession();
            $this->destroyRememberMe();

            return $this;
        }

        public function destroySession()
        {
            if ( ! $this->_started ) {
                $this->start();
            }

            if ( session_id() !== '' ) {
                foreach ( $_SESSION as $key => $value ) {
                    unset( $_SESSION[$key] );
                }

                if (ini_get("session.use_cookies")) {
                    $params = session_get_cookie_params();
                    
                    // expire the session cookie
                    // value string must not be empty!
                    setcookie(
                            session_name(), 'a',
                            1,
                            $params["path"]  , $params["domain"],
                            $params["secure"], $params["httponly"]
                    );
                }
                
                session_destroy();
            }

            return $this;
        }

        public function destroyRememberMe()
        {
            if (
                    $this->_rememberCookie !== false &&
                    isset($_COOKIE[$this->_rememberCookie])
            ) {
                $this->setRememberCookie( 'a', 1 );
            }

            return $this;
        }
    }
?>
