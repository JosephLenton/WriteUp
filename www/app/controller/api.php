<?
    /**
     * This is a controller for handling ajax requests.
     */
    class api extends SiteController
    {
        public function __construct() {
            parent::__construct();

            $this->getFlexi()->events()->postAction( function($controller, $result, $controllerName, $action) {
                header('Content-type: application/json');
                echo json_encode( $result );
            } );
        }

        public function login() {
            $user = $this->users->loginUser( post('username'), post('password'), $errors );

            if ( $user !== null ) {
                return true;
            } else {
                return array( 'error' => $errors );
            }
        }
    }