<?php if ( ! defined('ACCESS_OK')) exit('Can\'t access scripts directly!');
    /* --- --- ---
     *  Frames - auto-views
     * --- --- --- */
    
    $flexi->setFrame( array(
            'start_page'       => 'frame/start_page',
            'css'              => 'frame/css',
            'head'             =>  null,
            
            'start_body'       => 'frame/start_body',

            'top_bar'          => 'frame/top_bar',
            'header'           => 'frame/header',
            'content'          =>  null,
            
            'footer_start'     => 'frame/footer_start',
            'footer'           =>  null,
            'footer_end'       => 'frame/footer_end',

            'end_body'         => 'frame/end_body',
            
            'end_js'           => 'frame/end_js'
    ) );

    $flexi->setFrame( 'ajax', null );
    
    // Viewing automatically replaces this section,
    // unless otherwise stated in your code.
    $flexi->setDefaultFrameView( 'content' );
