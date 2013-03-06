<?
    global $_flexi_view_params;
    $_flexi_view_params = null;

    /**
     * This is the stripped down, core, no-nonsense, controller.
     * No frills, no extras, just the absolute bare essentials.
     * 
     * You should rarely need to use this, instead the Controller
     * class is the one you should use. That is why this is called
     * 'CoreController' instead.
     */
    class CoreController extends \flexi\Obj
    {
        private $flexi;
        private $internalVars;
        private $isInsideView;
        
        /**
         * Standard constructor. Creates a new Controller and it builds it's own Loader object.
         */
        public function __construct()
        {
            $this->flexi = Flexi::getFlexi();
            $this->isInsideView = false;
        }

        public function __invoke() {
            $this->invokeErr();
        }

        public function __set( $prop, $value ) {
            $this->{$prop} = $value;
        }
        
        /**
         * Returns the Flexi framework instance used to create this controller.
         * This was the main instance at the time this controller was made.
         * 
         * In practice, there is only ever one Flexi instance, and this will
         * be that instance.
         */
        protected function getFlexi()
        {
            return $this->flexi;
        }
        
        /**
         * Sets the frame for this controller to use.
         * Setting it to null will mean 'no frame'.
         * 
         * @param frame Null to set no frame, otherwise the frame for this Controller to use.
         */
        public function __setFrame( $frame )
        {
            $this->frame = $frame;
            if ( $this->frame != null ) {
                $this->frame->_setController( $this );
            }
        }
        
        /**
         * Loads the stated view file as though it is being run from within this Controller.
         * 
         * The optional params array holds mappings from field to value. The fields are made
         * into variables that hold their matching values for use within the view when it is
         * run.
         * 
         * Params are values assigned using the 'params' function, whilst 'locals' are ones
         * passed in using an assoviative array, and set at the top of the view before it is
         * run.
         * 
         * @param file The view file to run.
         * @param params null for no parameters (default), otherwise an array of variableName => variableValue.
         * @param useFrame states if the frame for this controller should be run or not, before loading the view, and defaults to true.
         */
        public function __view( $file, &$params, $useFrame=true )
        {
            if ( !$this->isInsideView && $useFrame && $this->frame != null ) {
                $this->frame->_loseDefault();
                $this->frame->_runTo();
            }

            $this->__viewInner( $file, $params );
        }

        /**
         * This presumes params is a list of local parameters,
         * and these are set at the top of the frame,
         * before it is viewed.
         * 
         * @param file The view file to load, or a closure to be run.
         */
        public function __viewInner( $file, &$params )
        {
            /*
             * We backup the old parameters,
             * incase someone calls 'view',
             * before calling 'params'.
             */
            global $_flexi_view_params;
            $old_params = $_flexi_view_params;
            $_flexi_view_params = $params;
            
            $this->isInsideView = true;

            if ( $file instanceof Closure ) {
                if ( $params === null || count($params) > 0 ) {
                    $file();
                } else {
                    call_user_func_array( $file, $params );
                }
            } else {
                if ( is_array($file) ) {
                    $file = join( $file, '/' );
                }
                
                $filePath = $this->getFlexi()->findFrom( 'view', $file );
                
                if ( $filePath === false ) {
                    throw new Exception( 'View not found: ' . $file );
                } else {
                    require( $filePath );
                }
            }

            $this->isInsideView = false;
            $_flexi_view_params = $old_params;
        }
    }

    /**
     * Defines the parameters for this view.
     * 
     * Usage:
     *  params( $title, $content, $foo );
     * 
     * and in the view you can now use $title, $content and $foo.
     * 
     * The values passed in are then set to those parameters, in that order.
     */
    function params(  &$_0=null, &$_1=null, &$_2=null, &$_3=null, &$_4=null, &$_5=null, &$_6=null, &$_7=null, &$_8=null, &$_9=null, &$_10=null, &$_11=null, &$_12=null, &$_13=null, &$_14=null, &$_15=null, &$_16=null, &$_17=null, &$_18=null, &$_19=null ) {
        global $_flexi_view_params;

        $numParams = func_num_args();

        if (
                ($_flexi_view_params === null && $numParams > 0) ||
                count($_flexi_view_params) < $numParams
        ) {
            throw new Exception( "not enough parameters passed to this view, or params called outside of view" );
        } else if (
                (count($_flexi_view_params) > $numParams )
        ) {
            throw new Exception( "more parameters given then the view will accept" );
        }

        switch( $numParams ) {
            case 0:
                break;
            case 1:
                $_0 = $_flexi_view_params[0];
                break;
            case 2:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                break;
            case 3:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                break;
            case 4:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                break;
            case 5:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                break;
            case 6:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                $_5 = $_flexi_view_params[5];
                break;
            case 7:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                $_5 = $_flexi_view_params[5];
                $_6 = $_flexi_view_params[6];
                break;
            case 8:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                $_5 = $_flexi_view_params[5];
                $_6 = $_flexi_view_params[6];
                $_7 = $_flexi_view_params[7];
                break;
            case 9:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                $_5 = $_flexi_view_params[5];
                $_6 = $_flexi_view_params[6];
                $_7 = $_flexi_view_params[7];
                $_8 = $_flexi_view_params[8];
                break;
            case 10:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                $_5 = $_flexi_view_params[5];
                $_6 = $_flexi_view_params[6];
                $_7 = $_flexi_view_params[7];
                $_8 = $_flexi_view_params[8];
                $_9 = $_flexi_view_params[9];
                break;
            case 11:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                $_5 = $_flexi_view_params[5];
                $_6 = $_flexi_view_params[6];
                $_7 = $_flexi_view_params[7];
                $_8 = $_flexi_view_params[8];
                $_9 = $_flexi_view_params[9];
                $_10 = $_flexi_view_params[10];
                break;
            case 12:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                $_5 = $_flexi_view_params[5];
                $_6 = $_flexi_view_params[6];
                $_7 = $_flexi_view_params[7];
                $_8 = $_flexi_view_params[8];
                $_9 = $_flexi_view_params[9];
                $_10 = $_flexi_view_params[10];
                $_11 = $_flexi_view_params[11];
                break;
            case 13:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                $_5 = $_flexi_view_params[5];
                $_6 = $_flexi_view_params[6];
                $_7 = $_flexi_view_params[7];
                $_8 = $_flexi_view_params[8];
                $_9 = $_flexi_view_params[9];
                $_10 = $_flexi_view_params[10];
                $_11 = $_flexi_view_params[11];
                $_12 = $_flexi_view_params[12];
                break;
            case 14:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                $_5 = $_flexi_view_params[5];
                $_6 = $_flexi_view_params[6];
                $_7 = $_flexi_view_params[7];
                $_8 = $_flexi_view_params[8];
                $_9 = $_flexi_view_params[9];
                $_10 = $_flexi_view_params[10];
                $_11 = $_flexi_view_params[11];
                $_12 = $_flexi_view_params[12];
                $_13 = $_flexi_view_params[13];
                break;
            case 15:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                $_5 = $_flexi_view_params[5];
                $_6 = $_flexi_view_params[6];
                $_7 = $_flexi_view_params[7];
                $_8 = $_flexi_view_params[8];
                $_9 = $_flexi_view_params[9];
                $_10 = $_flexi_view_params[10];
                $_11 = $_flexi_view_params[11];
                $_12 = $_flexi_view_params[12];
                $_13 = $_flexi_view_params[13];
                $_14 = $_flexi_view_params[14];
                break;
            case 16:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                $_5 = $_flexi_view_params[5];
                $_6 = $_flexi_view_params[6];
                $_7 = $_flexi_view_params[7];
                $_8 = $_flexi_view_params[8];
                $_9 = $_flexi_view_params[9];
                $_10 = $_flexi_view_params[10];
                $_11 = $_flexi_view_params[11];
                $_12 = $_flexi_view_params[12];
                $_13 = $_flexi_view_params[13];
                $_14 = $_flexi_view_params[14];
                $_15 = $_flexi_view_params[15];
                break;
            case 17:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                $_5 = $_flexi_view_params[5];
                $_6 = $_flexi_view_params[6];
                $_7 = $_flexi_view_params[7];
                $_8 = $_flexi_view_params[8];
                $_9 = $_flexi_view_params[9];
                $_10 = $_flexi_view_params[10];
                $_11 = $_flexi_view_params[11];
                $_12 = $_flexi_view_params[12];
                $_13 = $_flexi_view_params[13];
                $_14 = $_flexi_view_params[14];
                $_15 = $_flexi_view_params[15];
                $_16 = $_flexi_view_params[16];
                break;
            case 18:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                $_5 = $_flexi_view_params[5];
                $_6 = $_flexi_view_params[6];
                $_7 = $_flexi_view_params[7];
                $_8 = $_flexi_view_params[8];
                $_9 = $_flexi_view_params[9];
                $_10 = $_flexi_view_params[10];
                $_11 = $_flexi_view_params[11];
                $_12 = $_flexi_view_params[12];
                $_13 = $_flexi_view_params[13];
                $_14 = $_flexi_view_params[14];
                $_15 = $_flexi_view_params[15];
                $_16 = $_flexi_view_params[16];
                $_17 = $_flexi_view_params[17];
                break;
            case 19:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                $_5 = $_flexi_view_params[5];
                $_6 = $_flexi_view_params[6];
                $_7 = $_flexi_view_params[7];
                $_8 = $_flexi_view_params[8];
                $_9 = $_flexi_view_params[9];
                $_10 = $_flexi_view_params[10];
                $_11 = $_flexi_view_params[11];
                $_12 = $_flexi_view_params[12];
                $_13 = $_flexi_view_params[13];
                $_14 = $_flexi_view_params[14];
                $_15 = $_flexi_view_params[15];
                $_16 = $_flexi_view_params[16];
                $_17 = $_flexi_view_params[17];
                $_18 = $_flexi_view_params[18];
                break;
            case 20:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                $_5 = $_flexi_view_params[5];
                $_6 = $_flexi_view_params[6];
                $_7 = $_flexi_view_params[7];
                $_8 = $_flexi_view_params[8];
                $_9 = $_flexi_view_params[9];
                $_10 = $_flexi_view_params[10];
                $_11 = $_flexi_view_params[11];
                $_12 = $_flexi_view_params[12];
                $_13 = $_flexi_view_params[13];
                $_14 = $_flexi_view_params[14];
                $_15 = $_flexi_view_params[15];
                $_16 = $_flexi_view_params[16];
                $_17 = $_flexi_view_params[17];
                $_18 = $_flexi_view_params[18];
                $_19 = $_flexi_view_params[19];
                break;
            default:
                throw new Exception( "too many parameters set for view");
        }

        $_flexi_view_params = null;
    }

    /**
     * Defines the parameters for this view.
     * 
     * Usage:
     *  optionalParams( $title, $content, $foo );
     * 
     * and in the view you can now use $title, $content and $foo.
     * 
     * The values passed in are then set to those parameters, in that order.
     * 
     * This differs from params in that parameters not provided will
     * be set to 'null', and be skipped.
     */
    function optionalParams(  &$_0=null, &$_1=null, &$_2=null, &$_3=null, &$_4=null, &$_5=null, &$_6=null, &$_7=null, &$_8=null, &$_9=null, &$_10=null, &$_11=null, &$_12=null, &$_13=null, &$_14=null, &$_15=null, &$_16=null, &$_17=null, &$_18=null, &$_19=null ) {
        global $_flexi_view_params;

        $numParams = func_num_args();

        if ($_flexi_view_params === null && $numParams > 0) {
            $_flexi_view_params = array();
        } else if ( count($_flexi_view_params) > $numParams ) {
            throw new Exception( "more parameters given then the view will accept" );
        }

        while ( count($_flexi_view_params) < $numParams ) {
            $_flexi_view_params[]= null;
        }

        switch( $numParams ) {
            case 0:
                break;
            case 1:
                $_0 = $_flexi_view_params[0];
                break;
            case 2:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                break;
            case 3:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                break;
            case 4:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                break;
            case 5:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                break;
            case 6:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                $_5 = $_flexi_view_params[5];
                break;
            case 7:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                $_5 = $_flexi_view_params[5];
                $_6 = $_flexi_view_params[6];
                break;
            case 8:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                $_5 = $_flexi_view_params[5];
                $_6 = $_flexi_view_params[6];
                $_7 = $_flexi_view_params[7];
                break;
            case 9:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                $_5 = $_flexi_view_params[5];
                $_6 = $_flexi_view_params[6];
                $_7 = $_flexi_view_params[7];
                $_8 = $_flexi_view_params[8];
                break;
            case 10:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                $_5 = $_flexi_view_params[5];
                $_6 = $_flexi_view_params[6];
                $_7 = $_flexi_view_params[7];
                $_8 = $_flexi_view_params[8];
                $_9 = $_flexi_view_params[9];
                break;
            case 11:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                $_5 = $_flexi_view_params[5];
                $_6 = $_flexi_view_params[6];
                $_7 = $_flexi_view_params[7];
                $_8 = $_flexi_view_params[8];
                $_9 = $_flexi_view_params[9];
                $_10 = $_flexi_view_params[10];
                break;
            case 12:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                $_5 = $_flexi_view_params[5];
                $_6 = $_flexi_view_params[6];
                $_7 = $_flexi_view_params[7];
                $_8 = $_flexi_view_params[8];
                $_9 = $_flexi_view_params[9];
                $_10 = $_flexi_view_params[10];
                $_11 = $_flexi_view_params[11];
                break;
            case 13:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                $_5 = $_flexi_view_params[5];
                $_6 = $_flexi_view_params[6];
                $_7 = $_flexi_view_params[7];
                $_8 = $_flexi_view_params[8];
                $_9 = $_flexi_view_params[9];
                $_10 = $_flexi_view_params[10];
                $_11 = $_flexi_view_params[11];
                $_12 = $_flexi_view_params[12];
                break;
            case 14:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                $_5 = $_flexi_view_params[5];
                $_6 = $_flexi_view_params[6];
                $_7 = $_flexi_view_params[7];
                $_8 = $_flexi_view_params[8];
                $_9 = $_flexi_view_params[9];
                $_10 = $_flexi_view_params[10];
                $_11 = $_flexi_view_params[11];
                $_12 = $_flexi_view_params[12];
                $_13 = $_flexi_view_params[13];
                break;
            case 15:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                $_5 = $_flexi_view_params[5];
                $_6 = $_flexi_view_params[6];
                $_7 = $_flexi_view_params[7];
                $_8 = $_flexi_view_params[8];
                $_9 = $_flexi_view_params[9];
                $_10 = $_flexi_view_params[10];
                $_11 = $_flexi_view_params[11];
                $_12 = $_flexi_view_params[12];
                $_13 = $_flexi_view_params[13];
                $_14 = $_flexi_view_params[14];
                break;
            case 16:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                $_5 = $_flexi_view_params[5];
                $_6 = $_flexi_view_params[6];
                $_7 = $_flexi_view_params[7];
                $_8 = $_flexi_view_params[8];
                $_9 = $_flexi_view_params[9];
                $_10 = $_flexi_view_params[10];
                $_11 = $_flexi_view_params[11];
                $_12 = $_flexi_view_params[12];
                $_13 = $_flexi_view_params[13];
                $_14 = $_flexi_view_params[14];
                $_15 = $_flexi_view_params[15];
                break;
            case 17:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                $_5 = $_flexi_view_params[5];
                $_6 = $_flexi_view_params[6];
                $_7 = $_flexi_view_params[7];
                $_8 = $_flexi_view_params[8];
                $_9 = $_flexi_view_params[9];
                $_10 = $_flexi_view_params[10];
                $_11 = $_flexi_view_params[11];
                $_12 = $_flexi_view_params[12];
                $_13 = $_flexi_view_params[13];
                $_14 = $_flexi_view_params[14];
                $_15 = $_flexi_view_params[15];
                $_16 = $_flexi_view_params[16];
                break;
            case 18:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                $_5 = $_flexi_view_params[5];
                $_6 = $_flexi_view_params[6];
                $_7 = $_flexi_view_params[7];
                $_8 = $_flexi_view_params[8];
                $_9 = $_flexi_view_params[9];
                $_10 = $_flexi_view_params[10];
                $_11 = $_flexi_view_params[11];
                $_12 = $_flexi_view_params[12];
                $_13 = $_flexi_view_params[13];
                $_14 = $_flexi_view_params[14];
                $_15 = $_flexi_view_params[15];
                $_16 = $_flexi_view_params[16];
                $_17 = $_flexi_view_params[17];
                break;
            case 19:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                $_5 = $_flexi_view_params[5];
                $_6 = $_flexi_view_params[6];
                $_7 = $_flexi_view_params[7];
                $_8 = $_flexi_view_params[8];
                $_9 = $_flexi_view_params[9];
                $_10 = $_flexi_view_params[10];
                $_11 = $_flexi_view_params[11];
                $_12 = $_flexi_view_params[12];
                $_13 = $_flexi_view_params[13];
                $_14 = $_flexi_view_params[14];
                $_15 = $_flexi_view_params[15];
                $_16 = $_flexi_view_params[16];
                $_17 = $_flexi_view_params[17];
                $_18 = $_flexi_view_params[18];
                break;
            case 20:
                $_0 = $_flexi_view_params[0];
                $_1 = $_flexi_view_params[1];
                $_2 = $_flexi_view_params[2];
                $_3 = $_flexi_view_params[3];
                $_4 = $_flexi_view_params[4];
                $_5 = $_flexi_view_params[5];
                $_6 = $_flexi_view_params[6];
                $_7 = $_flexi_view_params[7];
                $_8 = $_flexi_view_params[8];
                $_9 = $_flexi_view_params[9];
                $_10 = $_flexi_view_params[10];
                $_11 = $_flexi_view_params[11];
                $_12 = $_flexi_view_params[12];
                $_13 = $_flexi_view_params[13];
                $_14 = $_flexi_view_params[14];
                $_15 = $_flexi_view_params[15];
                $_16 = $_flexi_view_params[16];
                $_17 = $_flexi_view_params[17];
                $_18 = $_flexi_view_params[18];
                $_19 = $_flexi_view_params[19];
                break;
            default:
                throw new Exception( "too many parameters set for view");
        }

        $_flexi_view_params = null;
    }
