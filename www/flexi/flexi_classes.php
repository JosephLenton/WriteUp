<?
    /**
     * This contians all of the common classes used by the framework.
     * They are mostly internal, and are rarely extended or instantiated
     * outside of framework code.
     */

    namespace flexi;
    
    /**
     * A common object to offer better error messages then PHP.
     * 
     * It just overlaps the standard magic methods, and then outputs an exception
     * instead of raising a fatal error.
     */
    class Obj extends \stdClass
    {
        /**
         * This is for automating calling callbale properties.
         * 
         * For example: '$this->view( 'index/build' )'
         */
        public function __call( $fun, $args ) {
            if ( isset($this->{$fun}) ) {
                $obj = $this->{$fun};

                if ( method_exists($obj, '__invoke') ) {
                    return $obj->__invoke( $args );
                }
            }

            throw new \flexi\ErrorToExceptionException( E_ERROR, 'Call to undefined method ' . get_class($this) . "->$fun()", __FILE__, __LINE__ );
        }

        /**
         * @throws An ErrorToExceptionException, as setting randomly unknown properties without expecting it, is clearly an error.
         */
        public function __set( $prop, $value ) {
            throw new \flexi\ErrorToExceptionException( E_ERROR, "Setting undeclared property, $prop, on " . get_class($this) . " object", __FILE__, __LINE__ );
        }

        /**
         * If you override __get, but the property requested is invalid,
         * then you can call this to just throw an error.
         * 
         * @param prop The name of the property being requested.
         * @throws An ErrorToExceptionException, 
         */
        public function getErr( $prop ) {
            throw new \flexi\ErrorToExceptionException( E_ERROR, "Setting undeclared property, $prop, on " . get_class($this) . " object", __FILE__, __LINE__ );
        }

        /**
         * @throws an E_ERROR wrapped in an exception, so we have a stack trace.
         */
        public function invokeErr() {
            throw new \flexi\ErrorToExceptionException( E_ERROR, 'This object ' . get_class($this) . ', is not callable', __FILE__, __LINE__ );
        }
    }

    /**
     * This is a wrapper class, for wrapping errors inside
     * of Exceptions. This way errors can now be terminal,
     * and provide a stack trace.
     */
    class ErrorToExceptionException extends \Exception
    {
        public function __construct( $code, $message, $file, $line )
        {
            parent::__construct( $message, $code );

            $this->file = $file;
            $this->line = $line;
        }
    }

    /**
     * Stores and retrieves your frames.
     * 
     * This works with setting up, and creating, all of the indevidual
     * frame instances.
     */
    class FramesBuilder extends \flexi\Obj
    {
        private $defaultSection = 'content';
        private $frames = array();

        private function newFrameItem( $controller, $function, &$frame ) {
            return array(
                    'controller' => $controller,
                    'function'   => $function  ,
                    'frame'      => $frame
            );
        }

        /**
         * Sets a frame to be applied to the controller or function stated.
         */
        public function setFrame( $controller, $function, &$frame ) {
            $this->frames[]= $this->newFrameItem( $controller, $function, $frame );
        }

        /**
         * @return Null if no frame is found, otherwise a new Frame object for a Controller to use.
         */
        public function getFrame( $controller, $function ) {
            $controller = strtolower($controller);
            $function   = strtolower($function);

            for ( $i = count($this->frames)-1; $i >= 0; $i-- ) {
                $frameItem = $this->frames[$i];

                $frameConn = $frameItem['controller'];
                $frameFun  = $frameItem['function'];

                if (
                       ( $frameConn === null || strtolower($frameConn) === $controller ) &&
                       ( $frameFun  === null || strtolower($frameFun)  === $function   )
                ) {
                    $frameData = $frameItem['frame'];

                    if ( $frameData === null ) {
                        return null;
                    } else {
                       $frame = new \Frame();
                       $frame->_setConfig( $frameItem['frame'], $this->defaultSection );

                       return $frame;
                    }
                }
            }

            return null;
        }

        public function setDefaultFrameView( $section ) {
            $this->defaultSection = $section;
        }
    }

    /**
     * Stores, and allows you to run, various events.
     */
    class EventsHandler extends \flexi\Obj
    {
        private static function runEvents( &$events, $args=null )
        {
            if ( $events !== null ) {
                if ( is_array($events) ) {
                    $len = count($events);

                    if ( $args === null ) {
                        for ( $i = 0; $i < $len; $i++ ) {
                            $event = $events[$i];
                            $event();
                        }
                    } else {
                        for ( $i = 0; $i < $len; $i++ ) {
                            $event = $events[$i];
                            call_user_func_array( $event, $args );
                        }
                    }
                } else {
                    if ( $args === null ) {
                        $events();
                    } else {
                        call_user_func_array( $event, $args );
                    }
                }
            }
        }

        private static function runKeyEvents( &$events, $key, $args=null )
        {
            $key = strtolower( $key );

            if ( $events !== null ) {
                if ( isset($events[$key]) ) {
                    \flexi\EventsHandler::runKeyEventsInner( $events, $key, $args );
                }

                if ( isset($events['']) ) {
                    \flexi\EventsHandler::runKeyEventsInner( $events, '', $args );
                }
            }
        }

        private static function runKeyEventsInner( &$events, $key, $args ) {
            $event = $events[$key];

            if ( $args === null ) {
                if ( is_array($event) ) {
                    $len = count($event);

                    for ( $i = 0; $i < $len; $i++ ) {
                        $ev = $event[$i];
                        $ev();
                    }
                } else {
                    $event();
                }
            } else {
                if ( is_array($event) ) {
                    $len = count($event);

                    for ( $i = 0; $i < $len; $i++ ) {
                        call_user_func_array( $event[$i], $args );
                    }
                } else {
                    call_user_func_array( $event, $args );
                }
            }
        }

        private static function runLogicalKeyEvents( &$events, $folder, $key, $args=null )
        {
            $folder = strtolower( $folder );

            if ( $events !== null && isset($events[$folder]) ) {
                \flexi\EventsHandler::runKeyEvents( $events[$folder], $key, $args );
            }
        }

        private static function addEvent( &$arr, $val )
        {
            if ( $arr === null ) {
                $arr = array( $val );
            } else if ( is_array($arr) ) {
                $arr[] = $val;
            } else {
                $arr = $val;
            }
        }

        private static function addKeyEvent( &$arr, $key, $val )
        {
            if ( $key === null ) {
                $key = '';
            } else {
                $key = strtolower( $key );
            }

            // don't create an array unless we have to!
            if ( $arr === null ) {
                $arr = array( $key => $val );
            } else {
                if ( isset($arr[$key]) ) {
                    $current = $arr[$key];

                    if ( is_array($current) ) {
                        $arr[$key][]= $val;
                    } else {
                        $arr[$key] = array( $current, $val );
                    }
                } else {
                    $arr[$key] = $val;
                }
            }
        }

        private static function addLogicalKeyEvent( &$arr, $folder, $key, $val )
        {
            if ( $folder === null ) {
                $folder = '';
            } else {
                $folder = strtolower( $folder );
            }

            if ( $key === null ) {
                $key = '';
            } else {
                $key = strtolower( $key );
            }

            // don't create an array unless we have to!
            if ( $arr === null ) {
                $arr = array(
                        $folder => array(
                                $key => $val
                        )
                );
            } else {
                if ( isset($arr[$folder]) ) {
                    EventsHandler::addKeyEvent( $arr[$folder], $key, $val );
                } else {
                    $arr[$folder] = array( $key => $val );
                }
            }
        }

        private $preAction;
        private $postAction;

        private $onEnd;

        private $onNewObject;
        private $onNewKeyObject;
        private $onNewLogicalKeyObject;

        private $onLoadFile;
        private $onLoadKeyFile;
        private $onLoadLogicalKeyFile;

        private $flexi;

        public function __construct( $flexi )
        {
            $this->flexi = $flexi;

            $this->preAction        = null;
            $this->postAction       = null;

            $this->onEnd            = null;

            $this->onNewObject           = null;
            $this->onNewKeyObject        = null;
            $this->onNewLogicalKeyObject = null;

            $this->onLoadFile            = null;
            $this->onLoadKeyFile         = null;
            $this->onLoadLogicalKeyFile  = null;
        }

        public function preAction( $callback )
        {
            \flexi\EventsHandler::addEvent( $this->preAction, $callback );

            return $this;
        }

        public function runPreAction( $controller, $params, $cName, $action )
        {
            \flexi\EventsHandler::runEvents( $this->preAction, array($controller, $params, $cName, $action, $this->flexi) );
        }

        public function postAction( $callback )
        {
            \flexi\EventsHandler::addEvent( $this->postAction, $callback );

            return $this;
        }

        public function runPostAction( $controller, $result, $cName, $action )
        {
            \flexi\EventsHandler::runEvents( $this->postAction, array($controller, $result, $cName, $action, $this->flexi) );
        }

        public function onEnd( $callback )
        {
            \flexi\EventsHandler::addEvent( $this->onEnd, $callback );

            return $this;
        }

        public function runOnEnd()
        {
            \flexi\EventsHandler::runEvents( $this->onEnd, array($this->flexi) );
        }

        public function onNewObject( $logical, $name=null, $callback=null )
        {
            if ( func_num_args() === 1 ) {
                \flexi\EventsHandler::addEvent( $this->onNewObject, $name );
            } else if ( func_num_args() === 2 ) {
                \flexi\EventsHandler::addKeyEvent( $this->onNewKeyObject, $logical, $name );
            } else {
                if ( $logical === null ) {
                    \flexi\EventsHandler::addKeyEvent( $this->onNewKeyObject, $name, $callback );
                } else {
                    \flexi\EventsHandler::addLogicalKeyEvent( $this->onNewLogicalKeyObject, $logical, $name, $callback );
                }
            }

            return $this;
        }

        public function runOnNewObject( $logical, $name, $object )
        {
            \flexi\EventsHandler::runEvents          ( $this->onNewObject                           , array($object, $name, $this->flexi) );
            \flexi\EventsHandler::runKeyEvents       ( $this->onNewKeyObject                 , $name, array($object, $name, $this->flexi) );
            \flexi\EventsHandler::runLogicalKeyEvents( $this->onNewLogicalKeyObject, $logical, $name, array($object, $name, $this->flexi) );
        }

        public function onLoadFile( $logical, $name=null, $callback=null )
        {
            if ( func_num_args() === 1 ) {
                \flexi\EventsHandler::addEvent( $this->onLoadFile, $name );
            } else if ( func_num_args() === 2 ) {
                \flexi\EventsHandler::addKeyEvent( $this->onLoadKeyFile, $logical, $name );
            } else {
                if ( $logical === null ) {
                    \flexi\EventsHandler::addKeyEvent( $this->onLoadKeyFile, $name, $callback );
                } else {
                    \flexi\EventsHandler::addLogicalKeyEvent( $this->onLoadLogicalKeyFile, $logical, $name, $callback );
                }
            }

            return $this;
        }

        public function runOnLoadFile( $logical, $name )
        {
            \flexi\EventsHandler::runEvents          ( $this->onLoadFile                           , array($logical, $name, $this->flexi) );
            \flexi\EventsHandler::runKeyEvents       ( $this->onLoadKeyFile                 , $name, array($logical, $name, $this->flexi) );
            \flexi\EventsHandler::runLogicalKeyEvents( $this->onLoadLogicalKeyFile, $logical, $name, array($logical, $name, $this->flexi) );
        }
    }
          
    class FlexiLazyLoader extends \flexi\Obj
    {
        private $flexi;
        private $folder;
        private $name;
        private $cName;

        private $construct;
        private $args;
        private $then;

        public function __construct( $flexi, $folder, $name ) {
            $this->flexi = $flexi;

            $this->folder = $folder;
            $this->name   = $name;
            $this->cName  = null;

            $this->construct = null;
            $this->args = null;
            $this->then = null;
        }

        public function className( $name ) {
            $this->cName = $name;

            return $this;
        }

        public function args() {
            if ( $this->construct !== null ) {
                throw new Error("cannot use both 'args' and 'construct', one or the other");
            }

            $this->args = func_get_args();

            return $this;
        }

        public function create( $fun ) {
            if ( $this->args !== null ) {
                throw new Error("cannot use both 'args' and 'construct', one or the other");
            }

            $this->construct = $fun;

            return $this;
        }


        public function then( $fun ) {
            $this->then = $fun;

            return $this;
        }

        public function build() {
            $this->flexi->loadFileFrom( $this->folder, $this->name, true );

            if ( $this->cName !== null ) {
                $className = $this->cName;
            } else {
                $last = strrpos($this->name, '/');
                if ( $last !== false ) {
                    $className = substr( $this->name, $last+1 );
                } else {
                    $className = $this->name;
                }
            }

            $obj = null;

            if ( $this->construct !== null ) {
                $constructFun = function() use (&$obj, $className) {
                    $reflection = new \ReflectionClass( $className );
                    $obj = $reflection->newInstanceArgs( func_get_args() );

                    return $obj;
                };

                $cons = $this->construct;
                $newObj = $cons( $constructFun, $this->flexi );

                if ( isset($newObj) && $newObj ) {
                    $obj = $newObj;
                }
            } else if ( $this->args !== null ) {
                $reflection = new \ReflectionClass( $className );
                $obj = $reflection->newInstanceArgs( $this->args );
            } else {
                $obj = new $className;
            }

            if ( $this->then !== null ) {
                $then = $this->then;
                $then( $obj, $this->flexi );
            }

            $this->flexi->events()->runOnNewObject( $this->folder, $className, $obj );

            return $obj;
        }
    }
