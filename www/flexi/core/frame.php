<?php if ( ! defined('ACCESS_OK')) exit('Can\'t access scripts directly!');
	/**
	 * Frames need to be setup in the config.php before being used.
	 * 
	 * If setup then Controllers that have a Frame will have it available
	 * under their '$this->frame' property.
	 */
	class Frame
	{
		private $views;
		
        // current has been viewed
		private $currentSection;
        
        // next has not been viewed
		private $nextSection;
		
		private $defaultSection;
		
		private $controller;
		
		/**
		 * Don't call this!
		 * 
		 * Frames should only ever be made for you by the Flexi runtime.
		 * You dont' create them yourself.
		 */
		public function __construct()
		{
			$views = array();
			
			$currentSection = null;
			$nextSection    = null;
			$defaultSection = null;
		}
		
		/**
		 * Unsets a value for the default view in this frame.
		 */
		public function _loseDefault()
		{
			$this->views[ $this->defaultSection ] = null;
		}
		
		/**
		 * Runs all views left in the frame until end.
		 */
		public function _runToEnd()
		{
			$runView = ( $this->currentSection == null );
			$params = null;
			
			foreach ( $this->views as $section => $view ) {
				if ( $runView ) {
					$this->currentSection = $section;

					if ( $view !== null ) {
						$this->controller->__viewInner( $view, $params );
					}
				} else if ( $section === $this->currentSection ) {
					$runView = true;
				}
			}
		}
		
		/**
		 * Runs up to a section with the given name.
		 * The skips parameter allows you to skip n number of section past that point.
		 * 
		 * All sections preceding the findSection + skips will be viewed.
		 * 
		 * @param findSection A nane of a section to find.
		 * @param skips The number of sections to skip after finding the findSection.
		 */
		public function _runTo( $findSection=null, $skips=0 )
		{
			if ( $findSection == null ) {
				$findSection = $this->defaultSection;
			}
            
			$runViews = false;
			$useSkips = false;
			
			$lastSection = null;
			$lastView 	 = null;
			$params 	 = null;
            
			foreach ( $this->views as $section => $view ) {
				// skip the number of sections stated
				if ( $useSkips ) {
					if ( $skips === 0 ) {
						break;
					} else {
						$skips--;
					}
				// if found the point to run up to, switch to the 'skips' mode
				} else if ( $findSection === $section ) {
					// if the section is found _before_ where we are up to
					if ( !$runViews ) {
						return;
					// otherwise, move to skips mode
					} else {
						$useSkips = true;
					}
				// search for where we are up to
				} else if ( $this->currentSection === null || $this->currentSection === $section ) {
					$runViews = true;
				}
				
				if ( ($this->currentSection !== $section) && $runViews ) {
					$this->nextSection = $section;
					
					if ( $lastSection !== $this->currentSection ) {
						$this->currentSection = $lastSection;
						
						if ( $lastView !== null ) {
							$this->controller->__viewInner( $lastView, $params );
						}
					}
				}
				
				$lastSection = $section;
				$lastView = $view;
			}
			
			if ( $runViews === false ) {
				throw new Exception( "Section was not found: '" . $findSection . "'." );
			}
		}
		
		/**
		 * Sets the controller to use for displaying views.
		 * 
		 * @param controller The controller for this Frame to callback to when making a 'view'. Cannot be null.
		 */
		public function _setController( $controller )
		{
			$this->controller = $controller;
		}
		
		/**
		 * This is essentially the frames initialize method.
		 * It sets up the frame ready for use.
		 * 
		 * @param config The config array from the original config page.
		 * @param defaultSection The name of the section within the config page which is the default for when viewing.
		 */
		public function _setConfig( $config, $defaultSection )
		{
			$this->defaultSection = $defaultSection;
			$this->views          = $config;
			$this->currentSection = null;
			
			// set nextSection to the first item in the config
			foreach ( $config as $section => $view ) {
				$this->nextSection = $section;
				return;
			}
			
			// if config is empty, set nextSection to null
			$this->nextSection = null;
		}
		
		/**
		 * Views the stated file instead of the view stored at the given section.
		 * 
		 * @param section The name of the section within the frame to replace with the view.
		 * @param view The view file to run at the stated section.
		 */
		public function view( $section, $view )
		{
            $params = func_num_args() > 2 ?
                    array_slice( func_get_args(), 2 ) :
                    null ;

			$this->_runTo( $section );
			// don't view this in the future
			$this->views[$section] = null;
			$this->controller->__viewInner( $view, $params );
		}
		
		/**
		 * Displays the view before the section stated.
		 * The section is untouched.
		 * 
		 * @param section The name of the section where you want the view shown before.
		 * @param view The view file to display.
		 */
		public function pre( $section, $view )
		{
            $params = func_num_args() > 2 ?
                    array_slice( func_get_args(), 2 ) :
                    null ;

			$this->_runTo( $section );
			$this->controller->__viewInner( $view, $params );
		}
		
		/**
		 * Displays the view after the stated section.
		 * If the section has a view stored in the frame config, then it is displayed.
		 * 
		 * @param section The name of the section where you want the view shown after.
		 * @param view The view file to display.
		 */
		public function post( $section, $view )
		{
            $params = func_num_args() > 2 ?
                    array_slice( func_get_args(), 2 ) :
                    null ;

			$this->_runTo( $section, 1 );
			$this->controller->__viewInner( $view, $params );
		}
		
		/**
		 * Echo's the given text at the stated section.
		 * Anything originally contained in the section is now removed.
		 * 
		 * @param section The name of the section in the frame, where you want the text echo'd.
		 * @param text The text to echo.
		 */
		public function html( $section, $text )
		{
			$this->_runTo( $section );
			// don't view this in the future
			$this->views[$section] = null;
			echo $text;
		}
		
		/**
		 * Echo's the given text before the section stated.
		 * The section is untouched.
		 * 
		 * @param section The name of the section in the frame, where you want the text echo'd before.
		 * @param text The text to echo.
		 */
		public function preHtml( $section, $text )
		{
			$this->_runTo( $section );
			echo $text;
		}
		
		/**
		 * Echo's the given text after the section stated.
		 * The original section is also displayed if it is not null.
		 * 
		 * @param section The name of the section in the frame, where you want the text echo'd after.
		 * @param text The text to echo.
		 */
		public function postHtml( $section, $text )
		{
			$this->_runTo( $section, 1 );
			echo $text;
		}
        
        /**
         * Skips the view named when it comes to be run.
         * 
         * If the view is not found, then an exception is thrown.
         */
        public function skip()
        {
            foreach ( func_get_args() as $view ) {
                if ( isset($this->views[$view]) ) {
                    $this->views[$view] = null;
                } else {
                    throw new Exception( "View not found: " + $view );
                }
            }
        }
        
        /**
         * Turns off frames from this point onwards. A common use for this is if
         * you are outputting JSON objects.
         * 
         * In order to fully disable the whole frame, this should be called
         * before calling any views. 
         */
        public function disable()
        {
        	if ( func_num_args() > 0 ) {
        		foreach ( func_get_args() as $view ) {
        			if ( ! isset($this->views[$view]) ) {
        				throw new InvalidArgumentException("View not found '$view'");
        			}

        			$this->views[$view] = null;
        		}
        	} else {
	            // remove all frame data so this is empty
	            $this->views          = array();
	            $this->currentSection = null;
	            $this->nextSection    = null;
	        }
        }
	}
?>