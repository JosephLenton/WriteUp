<?
    class Users extends Model
    {
        public static function hashPassword( $plainText, $salt ) {
            // todo
        }

        public function __construct() {
            parent::__construct();
        }

        public function newUser( $username, $password ) {

        }

        public function getUser( $username ) {

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
                $errors = $this->validator
            } else {
                $user = $this->getUser( $username );

                if ( $user->password === Users::hashPassword($password, $user->salt) ) {
                    return $user;
                } else {
                    $errors = array( 'password' => "incorrect password given" );
                }
            }
        }
    }