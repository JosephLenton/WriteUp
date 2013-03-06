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

            if ( $events !== null && isset($events[$key]) ) {
                $event = $events[$key];

                if ( $args === null ) {
                    if ( is_array($event) ) {
                        $len = count($event);

                        for ( $i = 0; $i < $len; $i++ ) {
                            $event[$i]();
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
            $key = strtolower( $key );

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

        private $preAction;
        private $postAction;

        private $onEnd;

        private $onNewObject;
        private $onNewKeyObject;

        private $onNewModel;
        private $onNewKeyModel;

        public function __construct()
        {
            $this->preAction        = null;
            $this->postAction       = null;

            $this->onEnd            = null;

            $this->onNewObject      = null;
            $this->onNewKeyObject   = null;

            $this->onNewModel       = null;
            $this->onNewKeyModel    = null;
        }

        public function preAction( $callback )
        {
            \flexi\EventsHandler::addEvent( $this->preAction, $callback );

            return $this;
        }

        public function runPreAction( $controller, $params, $cName, $action )
        {
            \flexi\EventsHandler::runEvents( $this->preAction, array($controller, $params, $cName, $action) );
        }

        public function postAction( $callback )
        {
            \flexi\EventsHandler::addEvent( $this->postAction, $callback );

            return $this;
        }

        public function runPostAction( $controller, $result, $cName, $action )
        {
            \flexi\EventsHandler::runEvents( $this->postAction, array($controller, $result, $cName, $action) );
        }

        public function onEnd( $callback )
        {
            \flexi\EventsHandler::addEvent( $this->onEnd, $callback );

            return $this;
        }

        public function runOnEnd()
        {
            \flexi\EventsHandler::runEvents( $this->onEnd );
        }

        public function onNewObject( $name, $callback=null )
        {
            if ( func_num_args() === 1 ) {
                \flexi\EventsHandler::addEvent( $this->onNewObject, $callback );
            } else {
                \flexi\EventsHandler::addKeyEvent( $this->onNewKeyObject, $name, $callback );
            }

            return $this;
        }

        public function runOnNewObject( $name, $object )
        {
            \flexi\EventsHandler::runEvents( $this->onNewObject, $object, $name );
            \flexi\EventsHandler::runKeyEvents( $this->onNewKeyObject, $name, array($object, $name) );
        }

        public function onNewModel( $name, $callback=null )
        {
            if ( func_num_args() === 1 ) {
                \flexi\EventsHandler::addEvent( $this->onNewModel, $callback );
            } else {
                \flexi\EventsHandler::addKeyEvent( $this->onNewKeyModel, $name, $callback );
            }

            return $this;
        }

        public function runOnNewModel( $name, $object )
        {
            \flexi\EventsHandler::runEvents( $this->onNewModel, $object, $name );
            \flexi\EventsHandler::runKeyEvents( $this->onNewKeyModel, $name, array($object, $name) );
        }
    }
