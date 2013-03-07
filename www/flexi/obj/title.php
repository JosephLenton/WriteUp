<?
    class Title extends \flexi\Obj
    {
        private $titles;
        private $sep;
        
        public function __construct( $title=null, $seperator = ' | ' ) {
            if ( is_string($title) ) {
                $this->set( $title );
            } else {
                $this->titles = array();
            }
            
            $this->sep = $seperator;
        }
        
        public function seperator( $new=null ) {
            if ( is_string($new) ) {
                $this->sep = $new;
            } else {
                return $this->sep;
            }
        }
        
        public function set( $default ) {
            $this->titles = array( $default );
        }

        public function prepend( $title, $seperator=null ) {
            $str = trim( (string)$title );
            
            if ( $str !== '' ) {
                if ( $seperator && count($this->titles) > 0 ) {
                    array_unshift(
                            $this->titles,
                            $str . $seperator . array_shift($this->titles)
                    );
                } else {
                    array_unshift( $this->titles, $str );
                }
            }

            return $this;
        }
        
        public function append( $title, $seperator=null ) {
            $str = trim( (string)$title );
            
            if ( $str !== '' ) {
                if ( $seperator && count($this->titles) > 0 ) {
                    $this->titles[] = array_pop($this->titles) . $seperator . $str;
                } else {
                    $this->titles[] = $str;
                }
            }

            return $this;
        }
        
        public function get() {
            return implode( $this->sep, $this->titles );
        }
        
        public function __toString() {
            return $this->get();
        }
    }
