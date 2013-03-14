<?php if ( ! defined('ACCESS_OK')) exit('Can\'t access scripts directly!');

    /* --- --- --- --- --- --- --- --- --- --- --- --- --- --- --- --- ---
     *  Models - hook them into the Databases above.
     * 
     * The DB object is created, and then setup to be a apart of the model,
     * when models are created.
     * --- --- --- --- --- --- --- --- --- --- --- --- --- --- --- ---  */

    $flexi->lazyLoadObject( 'obj', 'db' )->
            create( function($constructor, $flexi) {
                return $constructor( $flexi->getDatabase() );
            } )->
            then( function($db, $flexi) {
                if ( $flexi->isDev() ) {
                    $db->validateTables();
                }

                /*
                 * Hookup to run the static initializer, if there is one.
                 */
                $flexi->events()->onLoadFile( 'model', null, function($folder, $klass, $flexi) use ( $db ) {
                    if ( method_exists($klass, 'initDB') ) {
                        call_user_func( $klass . '::initDB', $db );
                    }
                } );

                $flexi->events()->onNewObject( 'model', null, function($obj, $name, $flexi) use ( $db ) {
                    $obj->setDatabase( $db );
                } );
            } );

