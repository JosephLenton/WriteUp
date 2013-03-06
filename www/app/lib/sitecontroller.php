<?
    class SiteController extends Controller
    {
        public function __construct() {
            parent::__construct( 'Articulate' );

            $this->session = $this->load->obj( 'sitesession' );
            $this->opengraphtags = $this->load->obj( 'SiteOpenGraphTags', null, 'opengraphtags' );
        }

        public function index()
        {
            $this->load->view( 'home/index' );
        }

        /**
         * 
         */
        public function page_not_found()
        {
            $this->load->view( 'home/404' );
        }

        /**
         * 
         */
        public function page_on_error()
        {
            $this->load->view( 'home/500' );
        }
    }
