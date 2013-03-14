<?
    class Home extends SiteController
    {
        const NUM_ON_PAGE = 20;

        public function __construct() {
            parent::__construct();
        }

        public function index( $page=0 ) {
            $offset   = $page * Home::NUM_ON_PAGE;
            $articles = $this->articles->getLatest( $offset, Home::NUM_ON_PAGE );

            $this->view->home->index(
                    $articles,
                    Articles::$ALL_STYLES
            );
        }
    }

