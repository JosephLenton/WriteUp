<?php
    /**
     * Helper functions for manually starting and then ending the
     * connection. By using this you can end the session later whilst
     * the script is still running and so allow server side processing
     * to continue.
     * 
     * On the downside this ignores user aborting of loading the page.
     * 
     * Note that startNewHTTPHeader must be called before headers are sent.
     */
    
    /**
     * This must be called before headers are sent!
     * 
     * This ends the default header and starts a new one.
     * This new one can be ended at any time using endHTTPHeader.
     */
    function startNewHTTPHeader()
    {
		if ( ob_get_contents() ) {
	        ob_end_clean();
		}
        header("Connection: close\r\n");
        header("Content-Encoding: none\r\n");
        ignore_user_abort( true ); // optional
        flexi_ob_start();
    }
    
    /**
     * Note that this must be called after startNewHTTPHeader!
     * 
     * Ends the header (and so the connection) setup with the user.
	 * All HTTP and echo'd text will not be sent after calling this.
	 * This allows you to continue performing processing on server side.
     */
    function endHTTPHeader()
    {
        // now end connection
        header( "Content-Length: " . ob_get_length() );
        ob_end_flush();
        flush();
        ob_end_clean();
    }
?>