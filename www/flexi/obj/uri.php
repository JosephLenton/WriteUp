<?
	class URI extends \flexi\Obj
	{
		public $controller;
		public $method;
		public $params;
		
		public function __construct( $flexi )
		{
			$uriParts = $flexi->getURISplit();
			
			$this->controller = null;
			$this->method     = null;
			$this->params     = array();
			
			foreach ( $uriParts as $part ) {
				if ( $part !== '' ) {
                    $part = urldecode( $part );
                    
					if ( $this->controller == null ) {
						$this->controller = $part;
					} else if ( $this->method == null ) {
						$this->method = $part;
					} else {
						$this->params[]= $part;
					}
				}
			}
		}
		
		/**
		 * This returns the path to the controller and the function within it
		 * that have been selected. This is without any parameters and includes
		 * the set root path at the beginning.
		 */
		public function getPath()
		{
			$path = Flexi::getRootURI();
			
			if ( $this->controller !== null ) {
				$path .= $this->controller;
				
				if ( $this->method !== null ) {
					$path .= '/' . $this->method;
				}
			}
			
			return $path;
		}
		
		/**
		 * Returns the full path including the controller, it's function and all
		 * parameters given in the request URL.
		 */
		public function getFullPath()
		{
			if ( count($this->params) > 0 ) {
				return $this->getPath() . '/' . implode('/', $this->params);
			} else {
				return $this->getPath();
			}
		}
	}
?>