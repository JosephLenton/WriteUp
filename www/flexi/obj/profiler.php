<?
    /**
     * Profiler
     * 
     * A simple profiler class, for monitoring how long things
     * take to run, and then outputting results for this.
     */
    class Profiler extends \flexi\Obj
    {
        private $startTime;
        private $endTime;
        private $outputDisplay;
        private $times;
        
        public function __construct()
        {
            $this->startTime = microtime( true );
            $this->endTime = null;
            
            $this->times = array();
            
            $this->outputDisplay = true;
        }
        
        public function addTime( $type, $time, $comment='' )
        {
            $storedTime = array(
                    'time' => $time,
                    'comment' => $comment
            );
            
            if ( isset($this->times[$type]) ) {
                $this->times[$type][]= $storedTime;
            } else {
                $this->times[$type] = array( $storedTime );
            }
            
            return $this;
        }
        
        /**
         * Ends the timer.
         * 
         * @return This object.
         */
        public function end()
        {
            if ( $this->endTime === null ) {
                $this->endTime = microtime( true );
            }
            
            return $this;
        }
        
        /**
         * Once called, calls to 'display' will now do nothing.
         * 
         * This is useful to be able to turn off the profiler
         * during ajaxy calls, without having to alter your
         * profile logic.
         * 
         * You just call 'skipDisplay' once you know it
         * shouldn't be outputted, and the rest of your
         * profiling code can remain the same.
         * 
         * @return this object.
         */
        public function skipDisplay()
        {
            $this->outputDisplay = false;
            
            return $this;
        }
        
        /**
         * This is called to output the information.
         * 
         * If 'skipDisplay' has been called, then this will
         * silently do nothing.
         * 
         * If the profiler has not been ended yet, then it
         * will end now, and then display.
         * 
         * When displaying this will call the 'render' method,
         * and you can override that in order to alter how the
         * timing information is displayed.
         * 
         * @return this object.
         */
        public function display()
        {
            $this->end();
            
            if ( $this->outputDisplay ) {
                $this->render( $this->endTime - $this->startTime, $this->times );
            }
            
            return $this;
        }
        
        /**
         * Override this to add your own display code.
         * 
         * This the render logic, that actually displays the
         * output.
         */
        protected function render( $totalTime, $times )
        {
            $totalTimes = array();
            $phpTime = $totalTime;

            $indent = "&nbsp;&nbsp;";
            
            $strTimes = array();
            $minNameLen = 0;
            $minTimeLen = 0;
            
            foreach ( $times as $name => $record ) {
                $totalRecordTime = 0;
                
                foreach ( $record as $r ) {
                    $t = $r[ 'time' ];
                    
                    $totalRecordTime += $t;
                    $phpTime -= $t;
                }
                
                $totalRecordTime = (int) ($totalRecordTime*1000);
                $totalRecordTime = (string) $totalRecordTime;

                $minTimeLen = max( $minTimeLen, strlen($totalRecordTime) );
                $minNameLen = max( $minNameLen, strlen($name) );

                $strTimes[ $name ] = $totalRecordTime;
            }

            $phpTime   = (int) ($phpTime*1000);
            $totalTime = (int) ($totalTime*1000);

            $phpTime = (string) $phpTime;

            $minTimeLen = max( $minTimeLen, strlen($phpTime) );
            $minNameLen = max( $minNameLen, strlen('other') );

            $strTimes[ 'other' ] = $phpTime;
            
            ?>
                <div style="box-shadow: 3px 3px 4px rgba(0, 0, 0, 0.35); position: fixed; font: 15px monaco, consolas, monospace; top: 64px; left: 0; background: #222; opacity: 0.8; padding: 3px 9px; color: #eee; border-top-right-radius: 4px; border-bottom-right-radius: 4px;">
                    total: <?= $totalTime ?>ms,<br>
                    <?
                        foreach ( $strTimes as $name => $time ) {
                            $pad = $minNameLen - strlen($name);
                            while ( $pad > 0 ) {
                                $name = "$name&nbsp;";
                                $pad--;
                            }

                            $pad = $minTimeLen - strlen( $time );
                            while ( $pad > 0 ) {
                                $name = "$name&nbsp;";
                                $pad--;
                            }

                            echo $indent, $name, ' ', $time, 'ms<br>';
                        }
                    ?>
                </div>
            <?
        }
    }
