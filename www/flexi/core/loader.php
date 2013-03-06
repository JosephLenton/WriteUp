<?
    class Loader extends \flexi\Obj
    {
        public static $currentController = null;
        
        private $flexi;
        private $parentObj;

        public $obj;
        public $model;
        public $view;
        
        /** 
         * Creates a new Loader which is associated to work on the controller given.
         * This means that any objects it loads (through the obj method) will be
         * assigned to this given controller.
         * 
         * @param controller The controller to associate with this loader.
         * @param flexi Optional, the Flexi instance to use with this loader.
         */
        public function __construct( $parentObj, $flexi=null )
        {
            if ( $flexi === null ) {
                $flexi = Flexi::getFlexi();
            }

            $this->flexi = $flexi;
            $this->parentObj = $parentObj;

            $this->obj   = new FlexiObjectLoader( $this, 'obj'  , false );
            $this->model = new FlexiObjectLoader( $this, 'model', true  );
            $this->view  = new FlexiViewLoader( $parentObj );
        }
        
        /**
         * Iterates over the array given, performing a view for each item.
         * 
         * @param $file The file to load for each item in the array.
         * @param $array The array to iterate over.
         */
        public function viewEach( $file, $array )
        {
            $params = func_num_args() > 2 ?
                    array_slice( func_get_args(), 2 ) :
                    null ;
            
            return $this->view->__each( $file, $array, $params );
        }

        public function bindView( $file )
        {
            $params = func_num_args() > 1 ?
                    array_slice( func_get_args(), 1 ) :
                    null ;

            return $this->view->__bind( $file, $params );
        }
        
        /**
         * 
         */
        public function view( $file )
        {
            $params = func_num_args() > 1 ?
                    array_slice( func_get_args(), 1 ) :
                    null ;
            
            return $this->view->__view( $file, $params );
        }
        
        public function viewApply( $file, $params )
        {
            return $this->view->__view( $file, $params );
        }

        /**
         * // todo change this to use 'func_get_args' and the new 'params' function
         * 
         * Also add alternatives for 'getView' using locals or passing in a param array.
         */
        public function getView( $file )
        {
            $params = func_num_args() > 1 ?
                    array_slice( func_get_args(), 1 ) :
                    null ;
            
            return $this->view->__getView( $file, $params );
        }
        
        public function lib( $file, $loadOnce=true )
        {
            return $this->loadFrom( 'lib', $file, $loadOnce );
        }
        
        public function model( $file, $varName=null, $className=null )
        {
            $params = ( func_num_args() > 3 ) ?
                    array_slice(func_get_args(), 3) :
                    null ;
            
            $obj = $this->__loadObj( 'model', $file, $varName, $className, $params, true );

            return $obj;
        }
        
        /**
         * Loads the given file and then creates a new instance of the class name
         * given (the intention being that the class was inside the file).
         * 
         * An instance of the class will be set to the controller associated with
         * this loader.
         * 
         * If ommitted then the class and variable names will be presumed to be
         * the name of the file being opnened.
         * 
         * Any variables after the className are passed into the constructor of
         * the class when it is created.
         * 
         * @param file The file to open.
         * @param varName The name of the variable to set the file to, or null to omit.
         * @param className The name of the class to initialize, or null to omit.
         */
        public function obj( $file, $varName=null, $className=null )
        {
            $params = ( func_num_args() > 3 ) ?
                    array_slice(func_get_args(), 3) :
                    null ;

            $obj = $this->__loadObj( 'obj', $file, $varName, $className, $params, false );

            return $obj;
        }
        
        public function __loadObj( $logicalFolder, $file, $varName=null, $className=null, &$params, $isModel )
        {
            $last = strrpos( $file, '/'  );
            if ( $last === false ) {
                $fileStr = $file;
            } else {
                $fileStr = substr( $file, $last+1 );
            }
            
            if ( $className !== null ) {
                $className = trim( $className );
            }
            if ( $className === null || $className === '' ) {
                $className = $fileStr;
            }
            
            if ( $varName !== null ) {
                $varName = trim( $varName );
            }
            if ( $varName === null || $varName === '' ) {
                $varName = strtolower( $className );
            }
            
            $this->flexi->loadFileFrom( $logicalFolder, $file, true );
            
            // stored so models can get access to this obj
            Loader::$currentController = $this->parentObj;
            
            // if has parameters for the object being made
            if ( $params !== null && count($params) > 0 ) {
                $reflection = new ReflectionClass( $className );
                $obj = $reflection->newInstanceArgs( $params );
            } else {
                $obj = new $className;
            }
            
            if ( $isModel ) {
                $this->flexi->events()->runOnNewModel( $className, $obj );
            } else {
                $this->flexi->events()->runOnNewObject( $className, $obj );
            }

            Loader::$currentController  = null;
            $this->parentObj->$varName = $obj;
            return $obj;
        }
        
        /**
         * Loads the given file, end of.
         * 
         * @param file The file to load.
         * @param loadOnce True to not reload the file if it's already loaded, otherwise false to reload. Defaults true.
         */
        public function load( $file, $loadOnce=true )
        {
            $this->flexi->loadFile( $file, $loadOnce );
        }
        
        public function loadFrom( $logicalFolder, $file, $loadOnce=true )
        {
            $this->flexi->loadFileFrom( $logicalFolder, $file, $loadOnce );
        }
    }

    class FlexiViewLoader extends \flexi\Obj
    {
        private $parentController;
        private $viewParts;

        public function __construct( $parentController )
        {
            $this->parentController = $parentController;
            $this->viewParts = null;
        }

        private function buildViewPath( $post=null )
        {
            $parts = $this->viewParts;

            if ( $parts === null ) {
                if ( $post === null ) {
                    throw new Exception( "no view provided" );
                } else {
                    $path = $post;
                }
            } else {
                $this->viewParts = null;

                if ( $post === null ) {
                    $path = $parts;
                } else if ( is_array($parts) ) {
                    $parts[]= $post;
                    $path = $parts;
                } else {
                    $path = array( $parts, $post );
                }
            }

            return $path;
        }

        function view( $file )
        {
            $params = func_num_args() > 1 ?
                    array_slice( func_get_args(), 1 ) :
                    null ;

            return $this->__view( $file, $params );
        }

        function __view( $file, &$params )
        {
            $this->parentController->__view( $file, $params );

            return $this;
        }

        /**
         * Builds a function which when called, will run the currently setup view.
         * 
         * Parameters given will be applied to the view, when it is called.
         * This can be no parameters if you don't have any you wish to use.
         * 
         * Optionally you can pass in more parameters can also be passed into the
         * resulting function. This could be because you didn't bind any, or to add
         * onto those already used.
         * 
         * @return A function which will call the current built view.
         */
        function bind()
        {
            $params = func_num_args() > 0 ?
                    func_get_args() :
                    null ;

            return $this->__bind( $this->buildViewPath(), $params );
        }

        function __bind( $path, $params )
        {
            $self = $this;

            return function() use ($self, $path, $params) {
                $params2 = func_num_args() > 0 ?
                    func_get_args() :
                    null ;

                if ( $params === null ) {
                    $viewParams = $params2;
                } else if ( $params2 === null ) {
                    $viewParams = $params;
                } else {
                    $viewParams = array_merge( $params, $params2 );
                }

                $self->__view( $path, $viewParams );
            };
        }

        function each( $file, $array )
        {
            $params = func_num_args() > 2 ?
                    array_slice( func_get_args(), 2 ) :
                    null ;

            return $this->__each( $file, $array, $params );
        }

        function __each( $file, &$array, &$params )
        {
            if ( $array !== null && count($array) > 0 ) {
                /*
                 * Either make an array,
                 * or make enough room.
                 */
                if ( $params === null ) {
                    $params = array();
                } else {
                    array_unshift( $params, null );
                }

                foreach ( $array as $obj ) {
                    $params[0] = $obj;
                    $this->parentController->__view( $file, $params );
                }
            }

            return $this;
        }

        function map( $array )
        {
            $params = func_num_args() > 1 ?
                    array_slice( func_get_args(), 1 ) :
                    null ;

            return $this->__each( $this->buildViewPath(), $array, $params );
        }

        /**
         * Runs the current view, and returns it as HTML.
         */
        function get()
        {
            $params = func_num_args() > 0 ?
                    array_slice( func_get_args(), 0 ) :
                    null ;

            return $this->__getView( $this->buildViewPath(), $params );
        }

        function getView( $file )
        {
            $params = func_num_args() > 1 ?
                    array_slice( func_get_args(), 1 ) :
                    null ;

            return $this->__getView( $file, $params );
        }

        function __getView( $file, &$params )
        {
            ob_start();
            $this->parentController->__view( $file, $params );
            $html = ob_get_contents();
            ob_end_clean();
            
            return $html;
        }

        function __get( $prop )
        {
            $parts = $this->viewParts;

            if ( $parts === null ) {
                $this->viewParts = $prop;
            } else if ( is_array($parts) ) {
                $this->viewParts[]= $prop;
            } else {
                $this->viewParts = array( $parts, $prop );
            }

            return $this;
        }

        function __call( $view, $params )
        {
            return $this->__view( $this->buildViewPath($view), $params );
        }

        function __invoke( $params ) {
            return call_user_func_array( array($this, 'view'), $params );
        }
    }

    class FlexiObjectLoader extends \flexi\Obj
    {
        private $parentLoader;
        private $logicalFolder;
        private $isModel;

        public function __construct( $parentLoader, $logicalFolder, $isModel )
        {
            $this->parentLoader  = $parentLoader;
            $this->logicalFolder = $logicalFolder;
            $this->isModel       = $isModel;
        }

        public function __set( $prop, $value )
        {
            $this->{$prop} = $value;
        }

        public function __get( $name )
        {
            if ( isset($this->{$name}) ) {
                return $this->{$name};
            } else {
                $params = null;
                
                $obj = $this->parentLoader->__loadObj( $this->logicalFolder, $name, null, null, $params, $this->isModel );
                $this->{$name} = $obj;

                return $obj;
            }
        }

        /**
         * Loads the class, and creates a new instance, on the fly.
         * The parameters given are used for the objects constructor.
         * 
         * The object will be cached, if there are no parameters.
         */
        public function __call( $name, $params )
        {
            if ( count($params) === 0 && isset($this->{$name}) ) {
                return $this->{$name};
            } else {
                return $this->parentLoader->__loadObj( $this->logicalFolder, $name, null, null, $params, $this->isModel );

                if ( count($params) === 0 ) {
                    $this->{$name} = $obj;
                }

                return $obj;
            }
        }
    }
