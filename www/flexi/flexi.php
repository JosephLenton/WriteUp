<?
    require( __DIR__ . '/flexi_functions.php' );
    require( __DIR__ . '/flexi_classes.php' );
    
	/**
	 * Flexi
	 *
	 * The framework runs on top of a fully static class used for holding sitewide details.
	 * This is mainly used in two phases. First for setting up the framework from the users
	 * config file and as a repository for getting information from.
	 *
	 * For example the user might add their database info in their config file which will be
	 * retrieved and used later during a webpage.
	 */
	class Flexi extends \flexi\Obj
	{
        private static $currentFlexis = array();
        private static $lastFlexi     = null;

        public static function getFlexi()
        {
            $len = count( Flexi::$currentFlexis );

            if ( $len > 0 ) {
                return Flexi::$currentFlexis[ $len - 1 ];
            } else {
                return Flexi::$lastFlexi;
            }
        }

        private static function pushFlexi( $flexi )
        {
            array_push( Flexi::$currentFlexis, $flexi );
        }

        private static function popFlexi()
        {
            array_pop( Flexi::$currentFlexis );
        }

        private $loads;

		private $frames;

		private $variables;

        private $excludes;

        private $searchFolders;

        private $codePages;

        private $seenFiles;

        /**
         * These two are used when no controller or action is specified
         * in the request. For example: www.example.com
         * 
         * Basically it's for setting the initial landing page.
         */
		private $defaultController = 'home';
        private $defaultControllerAction = 'index';

        /**
         * When a controller is specified, but no action, then this action is used.
         * 
         * For example when visiting 'www.example.com/user', this action is added
         * onto the end of that.
         */
        private $defaultAction   = 'index';

        private $currentController = '';
        private $currentMethod     = '';

		private $rootURI = '/';
        private $cdn = '/';

        private $uri = null;
		private $searchPaths = array();

        private $currentFrame = null;

        /**
         * The better errors handler.
         * 
         * This only exists when in dev mode, and better errors is available.
         * 
         * When ...
         *      null   - has not attempted to load better errors
         *      false  - tried, but failed to load
         *      object - attempted and succeeded, this is the better errors handler
         */
        private $betterErrors = null;
        private $betterErrorsOptions;
        private $isDev = false;

        /**
         * Holds all of the events that Flexi has, which is
         * used for adding more.
         *
         * This way Flexi doesn't get filled with event
         * handlers.
         */
        private $events;

        private $defaultDatabase;
        private $namedDatabases;

        private $loaders;

        private $isConfig;
        private $isConfigDone;

        /*
		 * 
		 */
		public function __construct($config = null)
		{
            $this->isConfigDone = false;
            $this->isConfig = false;

            $this->loads = null;
            $this->workingDir = getcwd();

            Flexi::$lastFlexi = $this;

            $this->defaultDatabase = null;
            $this->namedDatabases  = null;

            $this->frames    = null;
            $this->excludes  = array();
            $this->variables = array();

            $this->seenFiles = array();

            $this->searchFolders = array();

            $this->loaders = array();

            $this->events = new \flexi\EventsHandler( $this );

            $this->codePages = array(
                    404 => array( 'Not Found'               , null, null ),
                    500 => array( 'Internal Server Error'   , null, null )
            );

            $this->betterErrorsOptions = array(
                    'ignore_folders'        => 'flexi',
                    'application_folders'   => 'app'
            );

            if ( $config !== null ) {
                $this->loadConfig( $config );
            }
		}

        public function __invoke()
        {
            $this->invokeErr();
        }

        /**
         * @return the event handler used by flexi, for setting events.
         */
        public function events()
        {
            return $this->events;
        }

        /**
         * Creates and returns, a lazy loader, which you can use to load and
         * create objects, after the configuration stage has eneded.
         */
        public function lazyLoadObject( $logicalFolder, $obj ) {
            if ( $this->isConfigDone ) {
                throw new Exception( "loaders can only be used during the config stage" );
            }

            $loader = new \flexi\FlexiLazyLoader( $this, $logicalFolder, $obj );
            $this->loaders[]= $loader;
            return $loader;
        }

        /**
         * Better error support is provided using a callback model.
         * You call this, and that then allows you to safely use better errors.
         * 
         * If better errors shouldn't be used, then your callback will fail.
         * This will happen if better errors cannot be loaded, or if we are
         * not in development mode.
         * 
         * This is to provide a single point for any bugs to be found when
         * loading better errors, so if this code is right, the system is
         * right.
         * 
         * If we are in dev mode, and better errors is not found, then a
         * warning will be raised. Either keep better errors, or turn off
         * dev mode.
         * 
         * @return False if better errors is unavailable, true if it is.
         */
        private function withBetterErrors( $callback ) {
            if ( $this->isDev ) {
                if ( $this->betterErrors === null ) {
                    $isLoaded = false;

                    if ( class_exists('\php_error\ErrorHandler') ) {
                        $isLoaded = true;
                    } else {
                        $path = __DIR__ . '/dev/php_error.php';

                        if ( file_exists($path) ) {
                            require( $path );
                            $isLoaded = true;
                        }
                    }

                    if ( $isLoaded ) {
                        $this->betterErrors = new \php_error\ErrorHandler( $this->betterErrorsOptions );
                    } else {
                        trigger_error( "php_error was not found (disable dev mode to hide this warning)" );
                        $this->betterErrors = false;
                    }
                }

                if ( $this->betterErrors ) {
                    $callback( $this->betterErrors, $this );

                    return true;
                }
            }

            return false;
        }

        /**
         * By default, items are found in specific folders.
         * These defaults are:
         *  = views are in 'view' folder,
         *  = classes that can be instanciated are in 'obj'
         *  = other libraries (like functions) are in 'lib'
         *  = models are in 'model'
         *  = controllers are stored in 'controller'
         *
         * These are my conventions, but what if you don't
         * like them, or want to add more?
         *
         * That is what this method is for!
         *
         * You can pass in a mapping, of logical name to
         * folder name. For example:
         *
         *  $flexi->addFolders( 'view', 'my_view' )
         *
         * You can also pass in an array of mappings:
         *
         *  $flexi->addFolders( array(
         *      'view' => 'my_view',
         *      'obj'  => 'my_objects'
         *  ) )
         */
        public function addFolders( $val )
        {
            if ( func_num_args() === 2 ) {
                $arg = func_get_arg(1);

                if ( is_array($arg) ) {
                    for ( $i = 0; $i < count($arg); $i++ ) {
                        $arg[$i] = ensureEndingSlash( $arg[$i] );
                    }
                } else {
                    $arg = ensureEndingSlash( $arg );
                }

                $this->searchFolders[ $val ] = $arg;
            } else if ( is_array($val) ) {
                foreach ( $val as $k => $v ) {
                    if ( is_array($v) ) {
                        for ( $i = 0; $i < count($v); $i++ ) {
                            $v[$i] = ensureEndingSlash( $v[$i] );
                        }
                    } else {
                        $v = ensureEndingSlash( $v );
                    }

                    $this->searchFolders[ $k ] = $v;
                }
            } else {
                throw new Exception("Unknown search folder setup: " . $val);
            }
        }

		public function preRun()
		{
            $this->isConfigDone = true;

            $len = count( $this->loaders );
            for ( $i = 0; $i < $len; $i++ ) {
                $this->loaders[$i]->build();
            }

			$len = count( $this->loads );

			if ( $len > 0 ) {
                Flexi::pushFlexi( $this );

				for ( $i = 0; $i < $len; $i++ ) {
					$this->load( $this->loads[$i] );
				}

                Flexi::popFlexi();
				$this->loads = null;
			}
		}

        /**
         * Adds folder names to the 404 list.
         * 
         * Anything hitting underneath these areas, will be given a general '404'
         * response. Note that this isn't PHP driven, it's a basic '404' response
         * for the browser.
         * 
         * Why? It's for /js, /css and similar folder. If you request a css
         * file which isn't found, there is no point in spinning up flexi running
         * a controller. This is to handle that use case.
         * 
         * Any request which starts with the values given, will be excluded.
         *  Example usage:
         *      $flexi->add404Prefixes(
         *              '/images',
         *              '/css',
         *              '/js',
         *              '/fonts',
         *              '/dynamic',
         *              
         *              '/apple-touch-icon'
         *      );
         */
        public function add404Prefixes()
        {
            $this->excludes += func_get_args();
        }

        private function is404Uri( $uri )
        {
            $uriLen = strlen( $uri );

            for (
                    $i = 0, $len = count($this->excludes);
                    $i < $len;
                    $i++
            ) {
                $start = $this->excludes[ $i ];
                $startLen = strlen( $start );

                // uri is longer then the exclude,
                // and the exclude is at the beginning of uri
                if (
                        $uriLen >= $startLen &&
                        strncmp($start, $uri, $startLen) === 0
                ) {
                    return true;
                }
            }

            return false;
        }

		/**
		 * There should only be one frame handler, but it's also made on the fly
		 * to avoid creating it when it's not in use.
		 *
		 * This function either returns the frame handler if it exists, or makes
		 * it if it doesn't (and returns the one it makes).
		 *
		 * @return The FrameHandler for storing all frame configurations.
		 */
		private function getFrameHandler()
		{
			if ( $this->frames == null ) {
				$this->frames = new \flexi\FramesBuilder();
			}

			return $this->frames;
		}

		/**
		 * Sets a frame to use against a controller/function combination.
		 *
		 * A frame is just an array with mappings from 'section' to 'views'.
		 * But a view can be null to say that a section is left empty.
		 *
		 * If the controller is null then this will means 'all controllers',
		 * and leaving function as null means 'all functions'.
		 *
		 * When a Controller is made at the start of a page request, it's
		 * name and the function selected will be used to find a Frame to
		 * apply to it.
		 *
		 * @param controller The name of a controller to apply the frame to, or null for all controllers.
		 * @param action The name of a function to apply the frame to, or null for all functions.
		 * @param config A frame setup to be applied to the controller/function combination.
		 */
		public function setFrame( $controller, $action=null, $config=null )
		{
            $frames = $this->getFrameHandler();

            switch ( func_num_args() ) {
                case 1:
                    $frames->setFrame( null, null, $controller );
                    break;
                case 2:
                    $frames->setFrame( $controller, null, $action );
                    break;
                default:
                    $frames->setFrame( $controller, $action, $config );
                    break;
            }

            return $this;
		}

		/**
		 * If a frame is applied to a controller, and if that controller performs a normal view
		 * (that does not involve it's frame), then the 'default section' is the place in that
		 * frame where their view will appear.
		 *
		 * You can use this function to state which section is the default section. This is
		 * applied to all frames.
		 *
		 * @param section The name of the default section in all frames.
		 */
		public function setDefaultFrameView( $section )
		{
			$frames = $this->getFrameHandler();
			$frames->setDefaultFrameView( $section );
		}

        /**
         * Turns off error reporting, runs the given callback,
         * and then turns it back on.
         * 
         * This is so you can turn it off for one specific section,
         * such as when working with Wordpress.
         * 
         * @param callback The function to call with errors turned off.
         */
        public function withoutErrors( $callback )
        {
            if ( ! is_callable($callback) ) {
                throw new Exception( "non callable callback given" );
            }

            if ( $this->betterErrors ) {
                return $this->betterErrors->withoutErrors( $callback );
            } else {
                return $callback;
            }
        }

        /**
         * Sets this into development mode.
         * This does two things.
         * 
         * First it gives you a global and unified flag you can check,
         * to see if this is in development mode or not. Inside your
         * controllers you can then add special logic, such as:
         * 
         *  if ( $this->getFlexi()->isDev() ) {
         *      // do development stuff here
         *  }
         * 
         * Second, flexi will output errors when in development mode.
         * When it is not in development mode, it will not display
         * errors.
         * 
         * By default, flexi is not in development mode (it's in production mode).
         * 
         * The second argument allows you to specify if dev or production
         * error reporting should be used. It's useful to change this when
         * you're integrating with an external library, such as Wordpress,
         * which will not run under very strict error reporting.
         * 
         * These can also be changed on it's own using 'setErrorReporting'
         * 
         * @param isDev Optional, defaults to true, use this to say if flexi is or isn't in development mode.
         * @param isDevErrorReporting By default this matches 'isDev'.
         * @return This flexi instance.
         */
        public function setDev( $isDev=true, $isDevErrorReporting=true )
        {
            $this->isDev = $isDev ? true : false ;

            if ( func_num_args() < 2 ) {
                $isDevErrorReporting = $this->isDev;
            }

            $this->setErrorReporting( $isDevErrorReporting );

            return $this;
        }

        public function setErrorReporting( $isDev )
        {
            if ( $isDev ) {
                $this->withBetterErrors( function($betterErrors) {
                    $betterErrors->turnOn();
                });
            } else {
                if ( $this->betterErrors ) {
                    $this->betterErrors->turnOff();
                }

                // acceptable production level error reporting
                error_reporting( E_ALL & ~E_DEPRECATED );

                set_error_handler( function() { return false; } );

                $self = $this;
                set_exception_handler( function($ex) use ($self) {
                    $self->run500();

                    return false;
                } );
            }

            return $this;
        }

        /**
         * @return True if this is in development mode, false if not.
         */
        public function isDev()
        {
            return $this->isDev;
        }

		/**
		 * Environment variables can be stored in Flexi for use globally.
		 * These are typically set in the config file and then retrieved from
		 * wherever that uses them.
		 *
		 * @param key The key to store the value under.
		 * @param value The value to store under the key.
		 */
		public function set( $key, $value )
		{
			$this->variables[ $key ] = $value;
		}

		/**
		 * @param key The stored value to get.
		 * @return Null if the key is not set, otherwise the value stored under the key.
		 */
		public function get( $key )
		{
			return isset( $this->variables[$key] ) ?
                    $this->variables[ $key ] :
                    null ;
		}

		/**
         * This returns the database, for the name provided. If no name is
         * given, then this returns the default database. Otherwise a database
         * object is looked up, for the model name given, and then returned, if
         * found.
         * 
         * If it is not found, then the default is returned.
         * 
         * If there is no database found, at all, not even a default, then this
         * will throw an exception, as it is presumed this is a configuration
         * error.
         * 
         * @param name Optional, pass in null or nothing at all, for the default database.
         * @return The Database instance, for the model stated.
		 */
		public function getDatabase( $name=null )
		{
            if ( $name !== null && isset($this->namedDatabases[$name]) ) {
                return $this->namedDatabases[$name];
            } else if ( $this->defaultDatabase !== null ) {
                return $this->defaultDatabase;
            } else {
                throw new Exception("no default database set");
            }
		}

		/**
		 * Adds a database config to be stored for use later.
		 * The config should be an associative array containing mappings for:
		 * 'username', 'password', 'database' and 'hostname'.
         * 
         * Databases are stored against a name. Technically, this is *just* a
         * name; an identifier used for them, and nothing more. However by
         * default, the Model presumes that the name is a 'models' name.
         * 
         * That behaviour is specific to the Model, *not*, Flexi. As far as
         * Flexi and the DB are concerned, there are no models, they do no
         * exist.
		 *
		 * @param name Optional, the name of the DB is for.
		 * @param config An associative array containing the settings needed to connect to the DB.
		 */
		public function addDatabase( $config )
		{
            if ( func_num_args() === 2 ) {
                $model = $config;
                $config = func_get_arg(1);

                if ( is_array($model) ) {
                    for ( $i = 0; $i < count($model); $i++ ) {
                        $this->setNamedDatabase( $model[$i], $config );
                    }
                } else {
                    $this->setNamedDatabase( $model, $config );
                }
            } else {
                $this->defaultDatabase = $config;
            }
		}

        private function setNamedDatabase( $model, $config ) {
            if ( $this->namedDatabases === null ) {
                $this->namedDatabases = array( $model => $config );
            } else {
                $this->namedDatabases[ $model ] = $config;
            }
        }

		/**
		 * All of the paths passed into this are added as folders to search
		 * for files within.
		 *
		 * It has variable length arguments.
		 */
		public function addRoots()
		{
			$numArgs = func_num_args();
			for ( $i = 0; $i < $numArgs; $i++ ) {
				$this->searchPaths[] = ensureEndingSlash( func_get_arg($i) );
			}
		}

		/**
		 * Load all of the files stated, using the paths given from 'addRoots'
		 * as a basis for where to look.
		 */
		public function load()
		{
			$numArgs = func_num_args();

            if ( $this->isConfig ) {
                if ( $this->loads === null ) {
                    $this->loads = func_get_args();
                } else {
                    $this->loads = array_merge( $this->loads, func_get_args() );
                }
            } else {
				for ( $i = 0; $i < $numArgs; $i++ ) {
					$file = func_get_arg( $i );
					$this->loadFile( $file );
				}
			}
		}

        public function getControllerName() {
            return $this->currentController;
        }
        public function getAction() {
            return $this->currentMethod;
        }

        private function setPageID( $controller, $method ) {
            $this->currentController = $controller;
            $this->currentMethod     = $method;
        }

		/**
		 * When a controller is accessed, but not action has been given,
         * a default action must be used.
         * 
         * For example with the page:
         *  example.com/user
         * 
         * It will run the 'user' controller, but which action?
         * 
         * This method allows you to set which action it will run. By
         * default this is 'index'. So:
         * 
         *  example.com/user
         * 
         * ... is the same as ...
         * 
         *  example.com/user/index
         * 
		 * @param method The method to try run on that controller.
         * @return This Flexi object.
		 */
		public function setDefaultAction( $action )
		{
            if ( ! $action ) {
                throw new Exception("false-like action given");
            }

			$this->defaultAction = $action;

            return $this;
		}

        /**
         * If you go to:
         * 
         *  example.com
         * 
         * What controller and action will get run? This method allows
         * you to set that.
         *
         * By default flexi will try 'home/index', and you can use this
         * to change it.
         * 
         * The action given is optional, if it's not given then this
         * defaults to the value set by 'setDefaultAction'.
         * 
         * @param controller The default controller to run.
         * @param action Optional, the default action to run when defaulting to this controller.
         * @return This Flexi object.
         */
        public function setDefaultController( $controller, $action=null )
        {
            if ( ! $controller ) {
                throw new Exception("false-like controller given");
            }

            $this->defaultController       = $controller;
            $this->defaultControllerAction = ( $action ) ? $action : null ;

            return $this;
        }

        /**
         * If a controller or action was given, but was not found,
         * then flexi will use this controller/action setup instead.
         * 
         * This is for PHP driven 404 pages. For a general 404, such as
         * for CSS or JS not found, use 'add404Requests'
         * 
         * @return This Flexi object.
         */
        public function set404( $controller, $action )
        {
            return $this->setCodePage( 404, $controller, $action );
        }

        public function set500( $controller, $action )
        {
            return $this->setCodePage( 500, $controller, $action );
        }

		/**
		 * Sets the root location of this site.
		 * By default this is '/', but it can be changed if the site is living within a sub-folder
		 * of the site.
		 *
		 * For example if you wanted the site running at 'example.com/blah/' then you would set this
		 * to '/blah'.
		 *
		 * @param root The root location of Flexi within your site.
         * @return This Flexi object.
		 */
		public function setRootURI( $root )
		{
			$this->rootURI = $root;

            return $this;
		}

		/**
		 * This returns whatever has been set using 'setRootURI'.
		 *
		 * @return The root URI set for this site.
		 */
		public function getRootURI()
		{
			return $this->rootURI;
		}

		public function setCDN( $root )
		{
			$this->cdn = $root;

            return $this;
		}

        public function getCDN()
        {
            return $this->cdn;
        }

		/**
		 * Loads and runs the config file stated.
		 * This is relative to the index.php file.
		 *
		 * By default 'config.php' is always run, but you can use this to run more
		 * if you have split your configurations across multiple files.
         * 
         * ConfigFile may also be an array of config files.
         * 
		 * @param configFile A path to the config file to run.
         * @param failSilent When true, this supresses the exception thrown if the config is not found. Default is false.
		 */
		public function loadConfig( $configFile, $failSilent=false )
		{
            if ( $this->isConfigDone ) {
                throw new Exception("loading config file, after the config stage has ended");
            }

            if ( is_array($configFile) ) {
                foreach ( $configFile as $file ) {
                    $this->loadConfig( $file, $failSilent );
                }
            } else if ( !( $failSilent && !file_exists($configFile) ) ) {
                Flexi::pushFlexi( $this );

                $this->isConfig = true;
                requireFile( $configFile, $this );
                $this->isConfig = false;

                Flexi::popFlexi();
            }

            return $this;
		}

		/**
		 * Breaks the request URI used into it's parts and then returns them
		 * split up in an array.
		 *
		 * They are split by the '/' delimiter.
		 *
		 * @return An array containing each of the parts that make up the request URI.
		 */
		public function getURISplit()
		{
			// first explode removed the query data
			$uri = explode( '?', $this->uri );
			$uri = $uri[0];

			// skip the root URI
			$rootURI = $this->getRootURI();
            $left = substr( $uri, 0, strlen($rootURI) );

            if ( $rootURI === $left ) {
                $uri = substr( $uri, strlen($rootURI) );
            }

			return explode( '/', $uri );
		}

        private function getDefaultController( $controller )
        {
            if ( $controller === null ) {
                return $this->defaultController;
            } else {
                return $controller;
            }
        }

        private function getDefaultAction( $controller, $action )
        {
            if ( $controller === null ) {
                if ( $this->defaultControllerAction !== null ) {
                    return $this->defaultControllerAction;
                }
            }

            if ( $action === null || ( strpos($action, '_') === 0 ) ) {
                return $this->defaultAction;
            } else {
                $action = str_replace( '-', '_', $action );

                if ( $action === '' ) {
                    return $this->defaultAction;
                } else {
                    return $action;
                }
            }
        }

        /**
         * Searches in the controller paths for a controller of the given name.
         * If the controller is found then an instance is made and it is returned.
         *
         * If the controller is not found then an exception is thrown.
         *
         * @param name The name of a controller to load.
         * @return A new instance of the named controller.
         */
        public function newController( $name=null, $function=null )
        {
            $function = $this->getDefaultAction( $name, $function );
            $name = $this->getDefaultController( $name );

            $controllerPath = $this->findController( $name );

			if ( $controllerPath === false ) {
                throw new Exception( "Controller not found: '" . $name . "'" );
            } else {
                Flexi::pushFlexi( $this );
                requireFileOnce( $controllerPath, $this );

                $controller = new $name;
                if ( ! method_exists($controller, $function) ) {
                    $function = $this->defaultAction;
                }

                $frame = $this->frames->getFrame( $name, $function );
                $controller->__setFrame( $frame );

                Flexi::popFlexi( $this );

                return $controller;
			}
        }

        /**
         * Redirects permanently to the location given.
         *
         * It just concats all the parameters up into an url,
         * adds a leading slash, and then alters the header to a 301 code.
         *
         * The browser then does the actual redirect, so you should stop
         * processing after calling this.
         *
         * Internal redirections are only supported, i.e. redirecting to
         * other parts in the site.
         */
        public function redirectPermanent()
        {
            $this->redirectHelper( func_get_args(), 301 );
        }

        public function redirectTemporary()
        {
            $this->redirectHelper( func_get_args(), 307 );
        }

        private function redirectHelper( $args, $httpCode )
        {
            $location = implode( '/', $args );
            if ( strpos($location, 'http://') !== 0 && strpos($location, 'https://') !== 0 && strpos($location, '/') !== 0 ) {
                $location = "/$location";
            }

            header("Location: " . $location, true, $httpCode);
            exit( 0 );
        }

        /**
         * Alters the header stating a redirection,
         * and runs the controller stated.
         *
         * By 'redirect', it really means 'run a different controller'.
         * This is useful as a clean way of switching to a different
         * controller/method.
         *
         * The controller and action given is used to represent the
         * url it is moving to. For example '/home/front' would be
         * called as: 'redirect( 'home', 'front' )'.
         *
         * Further parts of the URL can be added as extra parameters,
         * for example '/home/users/joe/games/2' would be called as:
         * 'redirect( 'home', 'users', 'joe', 'games', 2 )'
         *
         * The redirect is a '303', and so is temporary.
         */
        public function redirect( $controller, $action=null )
        {
            if ( strpos($controller, 'http://') === 0 ) {
                $isUrl = true;

                if ( $action ) {
                    $location = "$controller/$action";
                } else {
                    $location =  $controller;
                }
            } else {
                $isUrl = false;

                if ( $controller && $action ) {
                    $location = "/$controller/$action";
                } else if ( $action ) {
                    throw new Exception( "action given, with no controller" );
                } else if ( $controller ) {
                    $location = "/$controller";
                } else {
                    throw new Exception( "action given, with no controller" );
                }
            }

            $params = array_slice( func_get_args(), 2 );
            if ( count($params) > 0 ) {
                $location .= '/' . implode( '/', $params );
            }

            header("Location: " . $location, true, 303);

            if ( $isUrl ) {
                exit(0);
            } else {
                $this->runInner( $controller, $action, $params );
            }
        }

		/**
		 * Parses the URI for this page and finds the appropriate controller to run.
		 * This is the last thing that should happen in the index and should never be
		 * called again.
		 *
		 * This is called automatically by the index.php script.
		 */
		public function run( $uri )
		{
            if ( $this->is404Uri($uri) ) {
                header("HTTP/1.0 404 Not Found");
            } else {
                $this->preRun();

                $this->uri = $uri;
                $uriParts = $this->getURISplit();

                $name     = null;
                $function = null;
                $params   = array();

                foreach ( $uriParts as $part ) {
                    if ( $part !== '' ) {
                        $part = urldecode( $part );

                        if ( $name == null ) {
                            $name = $part;
                        } else if ( $function == null ) {
                            $function = $part;
                        } else {
                            $params[]= $part;
                        }
                    }
                }

                // check name is not for this script
                if ( ($this->rootURI.$name) == $_SERVER['PHP_SELF'] ) {
                    $name = null;
                }

                $this->runInner( $name, $function, $params );
            }
        }

        /**
         * Looks up the information for the code given, and returns the
         * header message, controller and actions to use for it.
         * 
         * If they cannot be found, then null is returned for each.
         * 
         * The return value is: array( $header, $controller, $action )
         * 
         * example usage:
         *  list( $header, $controller, $action ) = $this->getCodePage( 404 );
         */
        private function getCodePage( $httpCode )
        {
            if ( isset($this->codePages[$httpCode]) ) {
                return $this->codePages[$httpCode];
            } else {
                return array( null, null, null );
            }
        }

        private function setCodePage( $httpCode, $controller, $action )
        {
            $page = &$this->codePages[ $httpCode ];

            if ( ! isset($page) ) {
                throw new Exception( "$httpCode is not supported" );
            } else if ( ! $controller ) {
                throw new Exception("false-like controller given");
            } else if ( ! $action ) {
                throw new Exception("false-like action given");
            }

            $page[1] = $controller;
            $page[2] = $action;

            return $this;
        }

        private function runCodePage( $httpCode )
        {
            try {
                ob_clean();
            } catch ( Exception $ex ) {
                /* do nothing */
            }

            list( $header, $controller, $action ) = $this->getCodePage( $httpCode );

            if ( $header === null ) {
                return $this->throwHeaderCodeNotFound( $httpCode );
            } else {
                header( "HTTP/1.0 $httpCode $header" );

                $controllerPath = $this->findController( $controller );

                if ( $controllerPath === false ) {
                    return $this->throwHeaderCodeNotFound( $httpCode );
                } else {
                    return $this->loadAndRunController( $controllerPath, $controller, $action, array(), false );
                }
            }
        }

        public function run500()
        {
            return $this->runCodePage( 500 );
        }

        /**
         * Runs the 404 page.
         */
        public function run404()
        {
            return $this->runCodePage( 404 );
        }

        private function throwHeaderCodeNotFound( $httpCode, $controller=null, $action=null )
        {
            $message = ( $controller !== null ) ?
                    "$httpCode controller / action not found, $controller" . '->' . $action :
                    "$httpCode controller / action not found" ;

            throw new Exception( $message );
        }

        private function runInner( $name, $action, $params )
        {
            $isDefault = ( $name === null );
            $action    = $this->getDefaultAction( $name, $action );
            $name      = $this->getDefaultController( $name );

			$controllerPath = $this->findController( $name );
			if ( $controllerPath === false ) {
                if ( $isDefault ) {
                    throw new Exception( 'default controller not found: ' . $this->defaultController );
                } else {
                    return $this->run404();
                }
			} else {
                return $this->loadAndRunController( $controllerPath, $name, $action, $params, true );
            }
        }

        private function loadAndRunController( $controllerPath, $name, $action, $params, $redirect404 )
        {
            requireFileOnce( $controllerPath, $this );

			if ( class_exists($name) ) {
                Flexi::pushFlexi( $this );

                /*
                 * If the constructor throws an exception, within it's self,
                 * then we can get the 'exception thrown with no stack trace'.
                 * That is a nightmare to debug.
                 * 
                 * So we catch, ensure it's reported, and show an error page instead.
                 */
                try {
				    $controller = new $name;
                } catch ( Exception $ex ) {
                    $reported = $this->withBetterErrors( function($err) use ($ex) {
                        $err->reportException( $ex );
                    });

                    if ( ! $reported ) {
                        $trace = $ex->getTraceAsString();
                        $parts = explode( "\n", $trace );
                        $trace = "        " . join( "\n        ", $parts );

                        error_log( "$message \n           $file, $line \n$trace" );
                        
                        if ( $redirect404 ) {
                            $this->run500();
                        }
                    }

                    return;
                }

                if ( method_exists($controller, $action) ) {
                    $methodObj = new ReflectionMethod( $controller, $action ) ;

                    if ( ! $methodObj->isPublic() ) {
                        if ( $redirect404 ) {
                            return $this->run404();
                        } else {
                            return $this->throwHeaderCodeNotFound( 404 );
                        }
                    }
                } else {
                    if ( $redirect404 ) {
                        return $this->run404();
                    } else {
                        return $this->throwHeaderCodeNotFound( 404 );
                    }
                }

                // set the frame
				$frame = null;
				if ( $this->frames !== null ) {
					$frame = $this->frames->getFrame( $name, $action );
					$controller->__setFrame( $frame );

                    if ( $this->currentFrame !== null ) {
                        $this->currentFrame->disable();
                    }

                    $this->currentFrame = $frame;
				}

                if (
                        ! $this->tryControllerMethod(
                                $controller,
                                $name,
                                $action,
                                $params,
                                $methodObj
                        )
                ) {
                    Flexi::popFlexi();
                    throw new Exception(
                            'Method \'' . $action .
                            '\' not found in controller: ' . $name . '\'.'
                    );
                } else {
                    if ( $controller->frame !== null ) {
                        $controller->frame->_runToEnd();
                    }

                    Flexi::popFlexi();
                }
			} else {
                Flexi::popFlexi();
				throw new Exception( 'Controller loaded but class not found: ' . $name );
			}
		}

		/**
		 * Attempts to invokve the method stated on the controller given. If the method is
		 * not found then false is returned. If it is found then the method is called and
		 * then true is returned after the call has ended.
		 *
		 * If the method requires more parameters then those in params then it will be
		 * padded with the default parameter.
         *
         * Often you want to check for method existance before calling this,
         * so this allows you to pass in a 'methodObj' which is an already existing
         * ReflectionMethod object for the method to be called.
         * This is solely to avoid creating a ReflectionMethod twice.
		 *
		 * @param controller The controller to run the method on.
		 * @param class The class of the controller.
		 * @param method The method to call on the controller.
		 * @param params Null for no parameters, or an array containing each of the parameters to pass in to the method.
         * @param methodObj Optional, the method we are calling already wrapped in a ReflectionMethod.
		 */
		private function tryControllerMethod( $controller, $class, $methodName, $params, $methodObj=null )
		{
			if ( $methodObj !== null || method_exists($controller, $methodName) ) {
				$method = ( $methodObj === null ) ?
                        new ReflectionMethod( $class, $methodName ) :
                        $methodObj ;
                
                if ( $method->isPublic() ) {
                    $num = $method->getNumberOfRequiredParameters();

                    while ( count($params) < $num ) {
                        $params[]= null;
                    }

                    $this->setPageID( strtolower($class), strtolower($methodName) );

                    $this->events->runPreAction( $controller, $params, $class, $methodName );
                    $result = $method->invokeArgs( $controller, $params );
                    $this->events->runPostAction( $controller, $result, $class, $methodName );

                    return true;
                }
			} else {
				return false;
			}
		}

		/**
		 * Given the name of a Controller class, this will search with it
		 * using all of the paths it has stored.
		 *
		 * @param name The name of the controller to load.
		 */
		private function findController( $name )
		{
            return $this->findFrom( 'controller', $name );
        }

        public function findFrom( $logicalFolder, $name, $error = false )
        {
            $search = null;

            if ( ! isset($this->searchFolders[$logicalFolder]) ) {
                throw new Exception("Logical folder not found: " . $logicalFolder);
            } else {
                $search = $this->searchFolders[$logicalFolder];
            }

            if ( is_array($search) ) {
                foreach ( $search as $subFolder ) {
                    $path = $this->findFile( $subFolder . $name, $error );

                    if ( $path !== false ) {
                        return $path;
                    }
                }

                return false;
            } else {
                return $this->findFile( $search . $name, $error );
            }
		}

        public function loadFileFrom( $logicalFolder, $file, $loadOnce=true )
        {
            return $this->loadFileHelper(
                    $logicalFolder,
                    $file,
                    $loadOnce
            );
        }

		/**
		 * Looks for the stated file in the frameworks set paths.
		 * If found then it is loaded.
		 *
		 * The reload is to state if it should be required, or required_once.
		 * When reload is false then required_once is used.
         *
         * Usage:
         * 
         *  loadFile( file )
         *      searches for the given file in the root, and
         *      then loads it.
         *      If the file is already loaded, then it is
         *      skipped.
         *
         *  loadFile( file, loadOnce )
         *      searches for the file in the root, and then
         *      loads it. loadOnce states if it should be
         *      loaded again if it's already loaded, or not.
         *
         *  loadFile( location, file )
         *      searches for the file in the logical folder
         *      given, and then loads it.
         *      If file is already loaded, then it is skipped.
         * 
         *  loadFile( location, file, loadOnce )
         *      searches for the file in the logical folder,
         *      and is loaded again or not depending on if
         *      loadOnce is true or not.
         *
		 */
		public function loadFile( $a, $b=true, $c=true )
        {
            /*
             * 3 parameter version: loadFile( $logicalFolder, $file, $loadOnce )
             */
            if ( $b !== true && $b !== false ) {
                $loadOnce = !! $c;
                $file = $b;
                $logicalFolder = $a;

            /*
             * 2 parameter version: loadFile( $file, $loadOnce )
             */
            } else {
                $loadOnce = !! $b;
                $file = $a;
                $logicalFolder = null;
            }

            return $this->loadFileHelper(
                    $logicalFolder,
                    $file,
                    $loadOnce
            );
        }

        private function loadFileHelper( $logicalFolder, $file, $loadOnce )
		{
            $filePath = ( $logicalFolder !== null ) ?
                    $this->findFrom( $logicalFolder, $file ) :
                    $this->findFile( $file ) ;

			if ( $filePath === false ) {
				throw new Exception( 'File not found: ' . $file );
			} else {
				if ( $loadOnce ) {
                    if ( ! isset($this->seenFiles[$filePath]) ) {
                        requireFileOnce( $filePath, $this );

                        $this->seenFiles[ $filePath ] = true;
                        $this->events()->runOnLoadFile( $logicalFolder, $file );
                    }
				} else {
                    requireFile( $filePath, $this );
                    $this->events()->runOnLoadFile( $logicalFolder, $file );
				}
			}
		}

        private $workingDir = '.';

        public function setWorkingDir( $dir )
        {
            $this->workingDir = $dir;

            return $this;
        }

        public function getWorkingDir()
        {
            return $this->workingDir;
        }

		/**
		 * Using the name given this will search for the file.
		 * A path to that file is returned if it is found,
		 * otherwise false is return.
		 *
		 * @param file The file to search for.
         * @param error If true, then this will raise an error if file is not found. Otherwise it will not raise an error.
		 * @return false if the file is not found, otherwise a path to the stated file.
		 */
		public function findFile( $file, $error = false )
		{
			$countPaths = count( $this->searchPaths );
            $filePHP = $file . '.php';
			for ( $i = 0; $i < $countPaths; $i++ ) {
                $path = $this->fileExists( $this->searchPaths[$i] . $filePHP );
				if ( $path ) {
					return $path;
				}
			}

			for ( $i = 0; $i < $countPaths; $i++ ) {
                $path = $this->fileExists( $this->searchPaths[$i] . $file );

				if ( $path ) {
					return $path;
				}
			}

            $path = $this->fileExists( $filePHP );
            if ( $path ) {
                return $path;
            } else {
                $path = $this->fileExists( $file );

                if ( $path || !$error ) {
                    return $path;
                } else {
                    throw new Exception( "Flexi cannot find file: " . $file );
                }
            }

		}

        public function fileExists( $path ) {
            $search = $this->getWorkingDir() . '/' . $path;

            return file_exists($search) ? $search : false;
        }
	}
