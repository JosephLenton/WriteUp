<?
    class Home extends SiteController
    {
        const NUM_ON_PAGE = 20;

        public function __construct() {
            parent::__construct();
        }

        public function index( $page=0 ) {
            $offset = $page*Home::NUM_ON_PAGE;
            $this->view->home->index( $this->model->articles->getLatest($offset, Home::NUM_ON_PAGE) );
        }
    }
