<?
    class SiteSession extends Session
    {
        /**
         * The Users model.
         */
        private $users;

        /**
         * This holds the current user object, cached from the DB. This is to
         * avoid repeated calls to the DB, to get the user out.
         * 
         * It is always in one of three states:
         * 
         *  - false, no user cached.
         *  - null, there is no user object in the DB.
         *  - User, it holds the user object.
         */
        private $user;

        /**
         * Creates a new session.
         * 
         * It takes a user model, so it can retrieve the user later, if it
         * needs to.
         */
        public function __construct( $userModel ) {
            parent::__construct();

            $this->users = $userModel;
            $this->user  = false;
        }

        /**
         * If logged in, then the user object for the logged in user is
         * returned. Otherwise, this returns null.
         * 
         * @return The currently logged in user, or null.
         */
        public function getUser() {
            if ( $this->user !== false ) {
                return $this->user;
            } else {
                $this->user = ( $this->isLoggedIn() ?
                            $this->users->getUser( $this->id ) :
                            null );

                return $this->user;
            }
        }

        public function setUser( $user ) {
            $this->id   = $user->id;
            $this->user = $user;
        }

        public function login( $user, $remember ) {
            $this->setUser( $user );

            if ( $remember ) {
                $this->rememberSession( $this->id,
                        $user->id,
                        $user->username,
                        $user->password
                );
            }
        }

        protected function onRememberMe( $id, $hashTest )
        {
            $user = $this->users->getUserIfAllowedToLogin( $id );
            
            if ( $user !== null ) {
                if ( $hashTest($user->id, $user->username, $user->password) ) {
                    $this->setUser( $user );
                    
                    return true;
                }
            }

            return false;
        }

        public function isLoggedIn()
        {
            //return $this->id != null || $this->isCookieLogin() ;
            return false;
        }
    }
