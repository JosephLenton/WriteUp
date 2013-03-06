<?
    function json( $result=null ) {
        return array( 'success' => true, 'data' => $result );
    }

    function jsonErr( $message=null ) {
        if ( $message === null ) {
            return array( 'success' => false );
        } else {
            return array( 'success' => false, 'error' => $message );
        }
    }