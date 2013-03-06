<?php
    $this->session->start();
    
    /* Play My Code */
?><!DOCTYPE html>
<html <?php
            /* 
                Sets an id, so you can perform page specific CSS,
                and sets the browser name + ieVersion if it's ie.
             */
            
            $flexi = Flexi::getFlexi();
            $controller = $flexi->getControllerName();
            $action = $flexi->getAction();
            
            /* 
             * If flexi doesn't know the controller name,
             * then we guess.
             */
            if ( $controller == '' ) {
                $klass = get_class( $this );
                
                if ( $klass ) {
                    $controller = strtolower( $klass );
                }
            }
            
            /* HTML id and class,
             * 
             * this includes 'controller_action' for the id,
             * 'controller_controller' and 'action_action' for the class,
             * browser name and IE version for the class as well.
             */
            echo
                'id="' , $controller , '_' , $action , '"',
            
                ' class="' ,
                    browser() , ' ',
                    platform(), ' ',
                    isIE() ? ( ' ie' . version() ) : '',
                    
                    ( ($controller !== '') ? ' controller_'.$controller : '' ),
                    (     ($action !== '') ? '     action_'.$action     : '' ),
                '"'
            ;
?>><?php

    /*
     * Get the utf-8 out as early as possible,
     * and always force latest IE rendering engine (even in intranet) & Chrome Frame
     */
    ?><meta charset="utf-8"><?php
    ?><meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"><?php

    // firebug lite for IE and no robots on dev
    if ( $this->getFlexi()->get( 'is_dev' ) ) {
        ?><meta name="robots" content="noindex noodp nofollow" ><?php
    }

    /* # SEO #
     * 
     * Keywords is still supported by Yahoo,
     * if these keywords are found in the page
     */
    
    echo
            title( $this->title ),
            
            meta( "description", "" ),
            meta( 'keywords', '' ),
            
            meta( "author"  , "" ),
            meta( "language", "en" )
    ;
    
    echo $this->opengraphtags->generateHTML();
?>
<link href='http://fonts.googleapis.com/css?family=Neuton:200,300,400,700' rel='stylesheet' type='text/css'>
<link rel="shortcut icon" href="<?= file_link('/favicon.ico') ?>">
<link rel="apple-touch-icon" href="<?= file_link('/apple-touch-icon.png') ?>">