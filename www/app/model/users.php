<?
    class Users extends Model
    {
        const NO_USERNAME = 1;
        const INCORRECT_PASSWORD = 2;
        const USERNAME_TAKEN = 3;

        private static $RESERVED_NAMES = array( 'anon', 'admin' );

        public static function hashPassword( $plainText, $salt ) {
            // todo
        }

        public function newUser( $username, $password, $email ) {
            $username = strtolower( trim($username) );

            if ( $username === '' ) {
                return Users::NO_USERNAME;
            } else if ( $password < 6 ) {
                return Users::INCORRECT_PASSWORD;
            } else {
                foreach ( Users::$RESERVED_NAMES as $name ) {
                    if ( $username === $name ) {
                        return Users::USERNAME_TAKEN;
                    }
                }

                try {
                    $this->db->users = array(
                            'username' => $username,
                            'password' => Users::hashPassword( $password, $salt ),
                            'salt'     => $salt
                    );
                } catch ( Exception $err ) {
                    return Users::USERNAME_TAKEN;
                }
            }
        }

        public function getUser( $username ) {
            $username = strtolower( trim($username) );

            return $this->db->users->get( 'username', $username );
        }

        public function getUserID( $id ) {
            return $this->users->get( $id );
        }

        public function loginUser( $username, $password, &$errors=null ) {
            $this->load->obj( 'validator' );

            $username = $this->validator->username->
                    trim()->
                    exists( "no username provided" )->
                    len( 1, 12, "username can be no longer then 12 characters" )->
                    isAlphaNumeric( "username must be alpha numeric" )->
                    get();

            $password = $this->validator->username->
                    exists( "no password given" )->
                    minlen( 6, "password must be at least 6 characters long" )->
                    get();

            if ( $this->validator->hasErrors() ) {
                $errors = $this->validator;
            } else {
                $user = $this->getUser( $username );

                if ( $user->password === Users::hashPassword($password, $user->salt) ) {
                    return $user;
                } else {
                    $errors = array( 'password' => "incorrect password given" );
                }
            }
        }

        public function __construct() {
            parent::__construct();
            
            //$this->db->link( 'users', UserObj );
        }
    }
