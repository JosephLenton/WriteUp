<?
	/**
	 * This is the common controller, that you should use in most applications.
	 * It is a standard controller, with added loading, and other common extras.
	 */
	class Controller extends CoreController
	{
        public $load;
        public $title;

        public $obj;
        public $model;
        public $view;
        
		/**
		 * Standard constructor. Creates a new Controller and it builds it's own Loader object.
		 */
		public function __construct( $title = 'example website' )
		{
            parent::__construct();
            
			$this->load = new Loader( $this, $this->getFlexi() );
            $this->load->obj( 'title', null, null, $title );

            $this->obj   = $this->load->obj;
            $this->view  = $this->load->view;
            $this->model = $this->load->model;
		}

        /**
         * The default function which always exists, by default.
         */
        public function index()
        {
            ?>
                <!DOCTYPE html>
                <title>Flexi | Welcome</title>
                <h1>Flexi is running!</h1>
                <p>Welcome, define your own controller and override the index method to get started.</p>
            <?php
        }
	}