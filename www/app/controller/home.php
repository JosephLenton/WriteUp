<?
    class Home extends SiteController
    {
        const NUM_ON_PAGE = 20;

        public function __construct() {
            parent::__construct();
        }

        public function index( $page=0 ) {
            $offset = $page*Home::NUM_ON_PAGE;
            $articles = $this->model->articles->getLatest( $offset, Home::NUM_ON_PAGE );

            $this->frame->skip( 'top_bar' );

            if ( $this->session->isLoggedIn() ) {
                $this->view->home->indexLoggedIn( $articles );
            } else {
                $this->view->home->indexLoggedOut( $articles );
            }
        }
    }
