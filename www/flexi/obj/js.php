<?
    class JS extends \flexi\Obj
    {
        private $prefix;
        private $settings;
        private $laterScripts;

        /**
         *
         */
        public function __construct( $prefix=null )
        {
            $this->prefix = $prefix;
            $this->laterScripts = null;
            $this->settings = null;
        }

        /**
         * Adds a script tag, which will be run at a later point in time.
         */
        public function addScript( $code, $extra='' )
        {
            if ( $this->laterScripts === null ) {
                $this->laterScripts = array(
                        '<script ' . $extra . '>' . $code . '</script>'
                );
            } else {
                array_push( $this->laterScripts,
                        '<script ' . $extra . '>' . $code . '</script>'
                );
            }
        }

        /**
         * Sets a key to value binding to be set in JavaScript.
         */
        public function set( $key, $val )
        {
            if ( !$this->settings ) {
                $this->settings = array();
            }

            $this->settings[ $key ] = $val;
        }

        public function laterScripts()
        {
            if ( $this->laterScripts !== null ) {
                return implode( '', $this->laterScripts );
            } else {
                return '';
            }
        }

        /**
         * This generates a script tag,
         * as a string,
         * and returns it.
         *
         * @return A script tag containing all of the settings set so far.
         */
        public function scriptTag()
        {
            return '<script>' . $this->scriptBody() . '</script>' ;
        }

        public function scriptBody()
        {
            $output = array();

            if ( $this->prefix ) {
                $parts = explode( '.', $this->prefix );
                $partsLen = count($parts);

                array_push( $output,
                        'window.', $parts[0], '={'
                );

                for ( $i = 1; $i < count($parts); $i++ ) {
                    array_push( $output, $parts[$i], ':{' );
                }

                if ( $this->settings ) {
                    $size = count( $this->settings );
                    foreach ( $this->settings as $key => $value ) {
                        array_push( $output,
                                $key, ':', json_encode( $value )
                        );
                        if ( (--$size) !== 0 ) {
                            $output[]= ',';
                        }
                    }
                }

                for ( $i = 1; $i < $partsLen; $i++ ) {
                    $output[]= '}' ;
                }
                $output[]= '};';
            } else if ( $this->settings ) {
                foreach ( $this->settings as $key => $value ) {
                    array_push( $output,
                            'window.', $key, '=', json_encode( $value )
                    );
                }
            }

            return implode( '', $output ) ;
        }
    }