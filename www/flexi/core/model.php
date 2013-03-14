<?php if ( ! defined('ACCESS_OK')) exit('Can\'t access scripts directly!');
	/**
	 * The Model, this represents data.
	 * 
	 * It's intended for this to be extended and for the developer to add
	 * their functions to make this into a specific model, say for
	 * dealing with users data on a forum or video data on a video site.
	 * 
	 * On creation this will have a database stored under it's 'db' field.
	 * You can access it as '$this->db'.
	 */
	class Model extends \flexi\Obj
	{
        private $flexi;
        
        public $load;
        public $obj;
        public $model;

        protected $db;
        
		/**
		 * Creates a new Model.
		 * 
		 * If 'dbName' is null then it will use the default database (the first one in the config file).
		 * 
		 * If 'dbName' is null and there is no default database, then none is picked and the 'db' field
		 * will not be present in this object.
         * 
         * @param dbName The name of the DB configuration to use, if null, then the default name is used.
         * @param loadDB When this is false, the DB will not be loaded, regardless of if name was given or not. Default to true.
		 */
	    public function __construct( $dbName=null, $loadDB=true )
		{
            $flexi = Flexi::getFlexi();
            $this->flexi = $flexi;
			
			$this->load  = new Loader( $this, $flexi );
            $this->obj   = $this->load->obj;
            $this->model = $this->load->model;

            $this->db = null;
		}

        public function setDatabase( $db ) {
            $this->db = $db;
        }

        public function __invoke() {
            $this->invokeErr();
        }

        public function __set( $prop, $value ) {
            $this->{$prop} = $value;
        }

        public function getFlexi() {
            return $this->flexi;
        }
	}
