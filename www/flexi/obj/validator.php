<?
    /**
     * This is for validating values stored in a given array, namely $_GET and
     * $_POST. If an array is not given when it is created then it will work on
     * $_POST values by default.
     *
     * Most methods return the Validator object you are calling on. This is to
     * allow method chaining.
     *
     * On each validation operation you can pass in an error message, which will
     * be returned by 'getError'.
     */
    class Validator extends \flexi\Obj
    {
        const EMAIL_REGEX = '/^\w[-.\w]*@(\w[-._\w]*\.[a-zA-Z]{2,}.*)$/';
        
        private static function checkEmail( $email ) {
            if ( preg_match(Validator::EMAIL_REGEX, $email, $matches) ) {
                if(function_exists('checkdnsrr')) {
                    if(checkdnsrr($matches[1] . '.', 'MX')) return true;
                    if(checkdnsrr($matches[1] . '.', 'A')) return true;
                // Windowz compatability
                } else {
                    if(!empty($hostName)) {
                        if( $recType == '' ) $recType = "MX";

                        exec("nslookup -type=$recType $hostName", $result);

                        foreach ($result as $line) {
                            if(eregi("^$hostName",$line)) {
                                return true;
                            }
                        }

                        return false;
                    }

                    return false;
                }
            }

            return false;
        }
        
        private $array;
        
        // if the validator currently has a value
        private $hasValue;
        // if the current field is valid, or not
        private $isValid;
        
        // the current field being worked on
        private $field;
        // the current error for the current field
        private $error;
        
        // all field errors, stored over time
        private $errors;
        
        private $alt;
        private $value;
        
        private $isOptional;

        /**
         *
         * @param <type> $array null to use $_REQUEST (the default), otherwise an array of values for this to validate.
         */
        public function __construct( $array=null )
        {
            if ( $array ) {
                if ( ! is_array($array) ) {
                    throw new Exception( "'array' must be an Array or null; object of type '" . gettype($array) . "' was given." );
                }

                $this->array = $array;
            } else {
                $this->array = $_REQUEST;
            }

            $this->clear();
        }

        /**
         * This does not take into account any checks.
         * It just checks if the property is set, or not,
         * regardless of what that value might be.
         * 
         * @param property The name of the property to check for.
         * @return True if the property is set, false if not.
         */
        public function has( $property )
        {
            return isset( $this->array[$property] );
        }

        /**
         * Cleares all of the internal state inside of this validator.
         * This essentially wipes it, making it ready for completely new re-use.
         * 
         * Note that no errors will be stored after you have called this!
         */
        public function clear()
        {
            $this->clearField();
            $this->errors = array();
            
            return $this;
        }
        
        /**
         * Cleares the last field information in this validator,
         * setting it up to be ready for a new field.
         * 
         * Typically you never need to call this,
         * as the validator will be cleared when you grab a new field.
         */
        private function clearField()
        {
            $this->hasValue = false;
            $this->isValid  = false;
            $this->isOptional = false;
            $this->error    = null;
            $this->alt      = null;
            $this->value    = null;

            return $this;
        }

        /*  Starter Functions
         *
         * $this->form->required( 'username' );
         * $this->form->optional( 'location' );
         */

        /**
         * This is cleared of all previous settings before the value is retrieved.
         * 
         * @param <type> $field
         * @param <type> $error
         */
        public function field( $field, $error=null )
        {
            $this->clearField();

            $this->setValue( $field );
            $this->isValid = ( $this->value !== null );
            $this->field = $field;
            
            if ( !$this->isValid ) {
                $this->setError( $error );
            }

            return $this;
        }

        /**
         * Allows people to state which field they are working on via property
         * grabbing. i.e. $this->username is the same as $this->field('username')
         * 
         * @param <type> $field
         * @return <type>
         */
        public function __get( $field )
        {
            return $this->field( $field );
        }

        /**
         * When called with no parameters,
         * this denotes that further checks will only report errors if there is a value.
         * If no value was found in the field, then this will report success.
         */
        public function optional()
        {
            $this->isOptional = true;
            
            if ( $this->value === '' ) {
                $this->value = null;
            }
            
            if ( $this->value === null && ! $this->isValid ) {
                $this->error = null;
                $this->isValid = true;
                
                if ( isset($this->errors[$this->field]) ) {
                    unset( $this->errors[$this->field] );
                }
            }

            return $this;
        }
        
        /**
         * @return True if a validation test should be performed, false if fail silent.
         */
        private function doTest()
        {
            return ! ( $this->isOptional && $this->value === null ) ;
        }

        private function setValue( $field )
        {
            if ( isset($this->array[ $field ]) ) {
                $arrVal = $this->array[ $field ];
            } else {
                $arrVal = null;
            }

            $this->value = $arrVal;
            $this->hasValue = true;
        }

        /**
         * If this is being called without a value being set, then this will
         * throw an exception. The exception will say that the method named
         * should not be called without a value being set first.
         *
         * @param <type> $method The name of the method being called.
         */
        private function ensureValue( $method )
        {
            if ( ! $this->hasValue ) {
                throw new Exception( "Calling method " . $method . " without setting a field (call 'required' or 'optional' first!)." );
            }
        }

        /*  Altering Functions
         *
         * These functions alter the state of the value stored in some way.
         *
         * $this->form->required( 'username' )->len( 1, 20 )->isAlphaNumeric();
         * $this->form->required( 'password' )->minLen( 8 );
         */

        /**
         * Sets an alternate value for the field, but only if the field was not
         * provided.
         *
         * @param $alt
         * @return This object to allow you to chain methods.
         */
        public function alt( $alt )
        {
            $this->ensureValue( 'alt' );

            if ( $this->value === null ) {
                $this->alt = $alt;
            }

            return $this;
        }

        /**
         * Trims the value stored, if there is one.
         * @return This validator to allow method chaining.
         */
        public function trim()
        {
            $this->ensureValue( 'trim' );

            if ( $this->value !== null ) {
                $this->value = trim( $this->value );
            }

            return $this;
        }
        
        public function replace( $search, $replacement='' )
        {
            if ( $this->value !== null ) {
                $this->value = str_replace( $search, $replacement, $this->value );
            }

            return $this;
        }
        
        public function replaceRegex( $regex, $replacement='' )
        {
            if ( $this->value !== null ) {
                $this->value = preg_replace(
                        $regex,
                        $replacement,
                        $this->value
                );
            }

            return $this;
        }
        
        /*  Validation Functions
         *
         * $this->form->required( 'username' )->len( 1, 20 )->isAlphaNumeric();
         * $this->form->required( 'password' )->minLen( 8 );
         */

        /**
         * Tests if this has a numeric value.
         * 
         * @param <type> $error
         */
        public function isNumeric( $error=null )
        {
            $this->ensureValue( 'isNumeric' );

            if ( $this->doTest() && ($this->value === null || !is_numeric($this->value)) ) {
                $this->setError( $error );
            }

            return $this;
        }

        public function isAlphaNumeric( $error=null )
        {
            $this->ensureValue( 'isAlphaNumeric' );

            if ( $this->doTest() && ($this->value === null || !ctype_alnum($this->value)) ) {
                $this->setError( $error );
            }

            return $this;
        }

        public function isAlpha( $error=null )
        {
            $this->ensureValue( 'isAlpha' );
            
            if ( $this->doTest() && ($this->value === null || !ctype_alpha($this->value)) ) {
                $this->setError( $error );
            }

            return $this;
        }

        /**
         * Checks if the given input is a valid e-mail address.
         * It does this by ensuring it looks right,
         * and by performing a DNS lookup on the email addresses domain.
         * 
         * But the DNS lookup can be disabled, meaning it will only perform a regex check.
         * 
         * @param error The error message for the email address.
         * @param checkDNS True (default) to perform a DNS lookup, false if not.
         */
        public function isEmail( $error=null, $checkDNS=true ) {
            $this->ensureValue( 'isEmail' );
            
            if ( $this->isOptional && $this->value === null ) {
                return $this;
            }
            
            $isValid =
                    ( $this->value !== null ) &&
                    (
                            (  $checkDNS && Validator::checkEmail( $this->value )            ) ||
                            ( !$checkDNS && preg_match(Validator::EMAIL_REGEX, $this->value) )
                    );
            
            if ( ! $isValid ) {
                $this->setError( $error );
            }
            
            return $this;
        }
        
        /**
         * Checks if the input being validator is a valid url or not.
         * 
         * @param error The error message to display if this fails.
         * @return This validator object.
         */
        public function isUrl( $error=null ) {
            $this->ensureValue( 'isUrl' );
            
            if ( $this->isOptional && $this->value === null ) {
                return $this;
            }
            
            if ( $this->value !== null ) {
                // ensure the url starts with http(s)://
                $url = $this->value;
                if (
                        stripos( $url, 'http://'  ) !== 0 &&
                        stripos( $url, 'https://' ) !== 0
                ) {
                    $url = 'http://' . $url;
                }
                
                $isValid = ( filter_var( $url, FILTER_VALIDATE_URL ) !== false );
            } else {
                $isValid = false;
            }
            
            if ( ! $isValid ) {
                $this->setError( $error );
            }
            
            return $this;
        }
        
        /**
         * Ensures that the value exists with a length of at least 1.
         * This does not take into account whitespace, you'll have to
         * call trim first to do this.
         */
        public function exists( $error=null )
        {
            return $this->minLen( 1, $error );
        }
        
        /**
         * Tests if the value we have matches the value given.
         */
        public function equal( $other, $error = null )
        {
            $this->ensureValue( 'equal' );
            
            if ( $this->doTest() && ($this->value === null || $this->value != $other) ) {
                $this->setError( $error );
            }
            
            return $this;
        }
        
        public function minLen( $len, $error=null )
        {
            $this->ensureValue( 'minLen' );
            
            if ( $this->doTest() && ($this->value === null || strlen($this->value) < $len) ) {
                $this->setError( $error );
            }

            return $this;
        }
        
        /**
         *
         * @param <type> $len The maximum length for the value, inclusive.
         * @param <type> $error
         */
        public function maxLen( $len, $error=null )
        {
            $this->ensureValue( 'maxLen' );
            
            if ( $this->doTest() && ($this->value === null || strlen($this->value) > $len) ) {
                $this->setError( $error );
            }

            return $this;
        }

        /**
         * Both min and max are inclusive.
         *
         * @param <type> $min The minimum length for the value, inclusive.
         * @param <type> $max The maximum length for the value, inclusive.
         * @param <type> $error Optional, the error to set if this condition is broken.
         */
        public function len( $min, $max, $error=null )
        {
            $this->ensureValue( 'len' );

            if ( $this->doTest() ) {
                if ( $this->value === null ) {
                    $this->setError( $error );
                } else {
                    $strlen = strlen($this->value);
                    if ( $strlen < $min || $strlen > $max ) {
                        $this->setError( $error );
                    }
                }
            }

            return $this;
        }

        /**
         * Tests if the regex given matches the value, somewhere.
         * Using the '^' and '$' you can match against the whole of value.
         * 
         * @param <type> $regex The pattern to test against the stored value.
         * @param <type> $error Optional, the error to set if the regex does not match.
         */
        public function regex( $regex, $error=null )
        {
            $this->ensureValue( 'regex' );

            if ( $this->doTest() && ($this->value === null || !preg_match($regex, $this->value)) ) {
                $this->setError( $error );
            }

            return $this;
        }

        /**
         * Tests if this value matches the string given, entirely.
         * 
         * @param <type> $other A string which the value must match.
         * @param <type> $error Optional, the error to set if the strings don't match.
         */
        public function equals( $other, $error=null )
        {
            $this->ensureValue( 'equals' );

            if ( $this->doTest() && $this->value !== $other ) {
                $this->setError( $error );
            }

            return $this;
        }

        /**
         * Runs the given function and passes in the value.
         * The function should then return true or false to state if it is error'd
         * or not.
         * 
         * @param $fun The function to apply to the given form.
         */
        public function check( $fun, $error=null )
        {
            $this->ensureValue( 'check' );

            if ( $this->doTest() && ! $fun($this->value) ) {
                $this->setError( $error );
            }

            return $this;
        }

        /*  Finishing Methods
         *
         * These are for retrieving the final values.
         */

        /**
         * If no value was stored in the field and an alt was set, then the alt
         * is returned.
         * 
         * If this object is valid, then it is returned.
         * Otherwise the 'alt' value is returned, but if it's missing then null
         * is returned instead.
         * 
         * @param $alt An alternate value to return if this is invalid.
         * @return The value being validated, alt or null depending on if it's valid or not.
         */
        public function get( $alt=null )
        {
            $this->ensureValue( 'get' );

            if ( $this->alt !== null ) {
                return $this->alt;
            } else if ( $this->isValid() ) {
                return $this->value;
            } else {
                return $alt;
            }
        }
        
        /**
         * Same as get, only this will run the value to be parsed as an int first.
         * If the return value is not a numeric value, then this will return null.
         */
        public function getInt( $alt=null )
        {
            $this->ensureValue( 'get' );

            if ( $this->alt !== null ) {
                return $this->alt;
            } else if ( $this->isValid() && is_numeric($this->value) ) {
                return intval( $this->value );
            } else {
                return $alt;
            }
        }
        
        private function toBool( $bool )
        {
            if ( is_bool($bool) ) {
                return $bool;
            } else {
                $text = strtolower( $bool );
                
                if ( $text === 'true' ) {
                    return true;
                } else if ( $text === 'false' ) {
                    return false;
                } else {
                    return null;
                }
            }
        }
        
        public function getBool( $alt=null )
        {
            $this->ensureValue( 'get' );
            
            if ( $this->alt !== null ) {
                return $this->alt;
            } else {
                $bool = $this->toBool( $this->value );
                
                if ( $bool !== null ) {
                    return $bool;
                } else {
                    return $alt;
                }
            }
        }

        /**
         * The error will only be stored on the first time this is called.
         * Regardless of if there is an error message or not, this will be set
         * as being invalid.
         * 
         * @param <type> $error Null to not set an error message, otherwise a message for this error.
         */
        private function setError( $error )
        {
            if ( $error && $this->error === null ) {
                $this->error = $error;
                $this->errors[ $this->field ] = $error;
            }
            
            $this->isValid = false;
        }

        /**
         * The error returned is the first error that was recorded.
         * All errors since then will be lost.
         * 
         * @return Null if there is no error, otherwise a stored message for the first error that occurred.
         */
        public function getError()
        {
            if ( $this->alt !== null ) {
                return null;
            } else {
                return $this->error;
            }
        }
        
        /**
         * @return An array of field name to error message
         */
        public function getErrors()
        {
            return $this->errors;
        }

        /**
         * If no value was stored in the field, but an alt was provided, then
         * this is valid.
         *
         * @return True if there were no errors, otherwise false.
         */
        public function isValid()
        {
            $this->ensureValue( 'isValid' );
            return $this->alt !== null || $this->isValid;
        }
        
        public function hasErrors()
        {
            return count( $this->errors ) > 0;
        }
    }
?>