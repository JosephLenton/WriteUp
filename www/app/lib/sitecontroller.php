<?
    class SiteController extends Controller
    {
        public function __construct() {
            parent::__construct( 'Articulate' );

            $this->load->model->articles();
            $this->load->model->users();

            $this->load->obj( 'sitesession', 'session', null, $this->users );
            $this->load->obj( 'SiteOpenGraphTags', 'opengraphtags' );
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
