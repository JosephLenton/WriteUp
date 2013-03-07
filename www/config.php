<?php if ( ! defined('ACCESS_OK')) exit('Can\'t access scripts directly!');
    $host = $_SERVER['HTTP_HOST'];
    $isDev = strstr( $host, 'localhost' ) !== false;
    
    /**
     * Config
     * 
     * Site wide settings are set in this script. It is devided into different sections
     * for each area.
     */
    
    /* --- --- ---
     *  Environment Settings
     * --- --- --- */
    
    $flexi->set( 'is_cachebusting', true );
    
    /* --- --- ---
     *  Setup
     * --- --- --- */
    
    // The default controller and the default method to use when none is selected or found.
    $flexi->setDev( $isDev )->
            setDefaultController( 'home', 'index' )->
            setDefaultAction( 'index' )->
            set404( 'home', 'page_not_found' )->
            set500( 'home', 'page_on_error'  );
    
    // A second url to use for downloading content.
    // Main PMC uses 'www.' at start of urls,
    // so ensure there is no 'www.' in the CDN to prevent cookies.
    if ( $isDev ) {
        $flexi->setCDN( '/' );
    } else {
//        $flexi->setCDN( 'http://playmycode.net' );
        $flexi->setCDN( '/' );
    }
    
    /* --- --- ---
     *  Paths - for finding stuff
     * --- --- --- */
    
    /*
     * First are the paths to the main 'application folders'.
     * 
     * This is the folder where your controllers, views,
     * objects, libraries, models, and other bits all live.
     * 
     * By default, this is 'app'. You also need 'flexi', to
     * get the core bits.
     * 
     * = Loading Order =
     * 
     * Items are checked under each of these folders, in the
     * order given. An item in the top one, can then replace
     * an item found in the bottom one.
     * 
     * For example you could replace the 'database' class with
     * your own, by placing it at 'app/obj/database'.
     * 
     * = Why would you want to change this? =
     * 
     * First example is if you don't like my conventions. You
     * can change 'app' to your own preference, and your done.
     * 
     * Second example, so you can include a whole utility
     * application. This is useful if you have a large code
     * base that you re-use for several applications.
     * 
     * For example:
     * 
     *      $flexi->addRoots(
     *              'app',
     *              'generic_app',
     *              'flexi'
     *      );
     * 
     * Where your utility bits are in 'generic_app'.
     */
    $flexi->addRoots(
            'app',
            'flexi',
            '' // root folder
    );
    
    /**
     * Next, the names for folders to check. These are
     * mappings of 'logical' folder, to the 'real' folder.
     * 
     * Again this is so you can change my conventions, or add
     * more locations to check.
     * 
     * For example if you have a 'util' folder, you could set:
     * 
     *  $flexi->addFolders( 'lib', ['lib', 'util'] )
     * 
     * Now anything loaded with 'lib' is loaded there. For
     * example:
     * 
     *      // both of these examples will check 'lib' and
     *      // then 'util' for 'util_functions'.
     *      $this->load->lib( 'util_functions' );
     *      $this->load->loadFrom( 'lib', 'util_functions' );
     * 
     * = Can I have my own logical folders? =
     * 
     * Yes! Loading is very similar with the Loader. You just
     * use:
     * 
     *      $this->load->loadFrom( 'my_logical', 'foobar' );
     * 
     */
    $flexi->addFolders( array(
            'controller' => 'controller',
            'obj'        => 'obj',
            'lib'        => 'lib',
            'view'       => 'view',
            'model'      => 'model'
    ) );
    
    /*
     * Any requests starting with the following, at this point,
     * will be sent a 404, if we try to run them as a PHP
     * request.
     * 
     * This is to prevent serving PHP on broken links to CSS,
     * JavaScript, Image and other static files.
     */
    $flexi->add404Prefixes(
            '/images',
            '/css',
            '/js',
            '/fonts',
            '/dynamic',
            
            '/apple-touch-icon'
    );
    
    /* --- --- ---
     * 
     *  Development Only Settings
     * 
     * Needs to be at the bottom, so the paths are setup first.
     * 
     * Setup the profiling, where to put mail, database SQL
     * output, and more!
     *
     * --- --- --- */
    if ( $isDev ) {
         /*
         * Profiling & Save Database SQL
         * 
         * Create a profiler and hook into Flexi for outputting
         * the results.
         * 
         * We also hook into the DB, record the SQL statements
         * generated, and record the time they took.
         */
        
        $flexi->loadFile( 'obj', 'profiler' );
        $profiler = new Profiler();

        $startTime = microtime( true );
        $flexi->events()->preAction( function() use ( $profiler, $startTime ) {
            if ( $startTime !== false ) {
                $profiler->addTime( 'startup', microtime(true)-$startTime );
                $startTime = false;
            }
        } );
        
        /*
         * Mails sent by the mailer are dumped to the
         * following file.
         */
        $flexi->events()->onNewObject( 'mailer', function($mailer) use ( $profiler ) {
            $mailer->
                    setQuiet()->
                    onMail( function($title, $content, $to, $from, $headers) use ( $profiler ) {
                        $start = microtime( true );

                        // create the message
                        // and then dump it to a file
                        $mail = join("\n", array(
                                '### START ###',
                                '',
                                $headers,
                                '',
                                $title,
                                '',
                                $content,
                                '### END ###',
                                ''
                        ) );
                        
                        @file_put_contents(
                                './../mail.txt',
                                $mail,
                                FILE_APPEND
                        );

                        $profiler->addTime( 'logs', microtime(true)-$start );
                    } );
        } );
        
        /**
         * Hook into the DB.
         */
        $outputSQLFile = './../queries.sql';
        $isFirst = true;
        $flexi->events()->onNewObject( 'db', function($db) use ($profiler, &$isFirst) {
            $dbTime = 0;

            $db->
                    beforeQuery( function() use ( &$dbTime ) {
                        if ( $isFirst ) {
                            file_put_contents( $outputSQLFile, PHP_EOL . '-- ' . $_SERVER['REQUEST_URI'] . PHP_EOL, FILE_APPEND );
                            $isFirst = false;
                        }

                        $dbTime = microtime( true );
                    })->
                    afterQuery( function($sql) use ( &$dbTime, $profiler ) {
                        $now = microtime( true );
                         
                        file_put_contents( './../queries.sql', $sql . "\n", FILE_APPEND );

                        $profiler->addTime( 'logs', microtime(true)-$now);
                        $profiler->addTime( 'sql' , $now - $dbTime      );
                    });
        });
        
        // remove the profiler on json_ functions
        $flexi->events()->preAction( function($controller, $params, $controllerName, $action) use ( $profiler ) {
            if ( strpos($action, 'json_') === 0 ) {
                $profiler->skipDisplay();
            }
        } );
        
        $flexi->events()->onEnd( function() use ( $profiler ) {
            $profiler->display();
        } );
    }
    
    /* --- --- ---
     *  Action Events
     * 
     * Here we setup methods beginning with 'json_' to work
     * differently, so they auto-output json to the client.
     * --- --- --- */
    
    $flexi->events()->preAction( function($controller, $params, $controllerName, $action) {
        if ( strpos($action, 'json_') === 0 ) {
            $controller->frame->disable();
            header('Content-type: text/plain');
        }
    } );
    $flexi->events()->postAction( function($controller, $result, $controllerName, $action) {
        if ( strpos($action, 'json_') === 0 && isset($result) && $result ) {
            echo json_encode( $result );
        }
    } );
    
    /* --- --- ---
     *  Databases - for the models
     * --- --- --- */
    
    /*
     * Here you set your DBs.
     * 
     * You can set multiple ones against a name, or set them
     * based on some other condition, such as if it's dev or
     * live.
     * 
     * The first DB set is used as the default DB for models,
     * unless they are told to use a specific one.
     */
    if ( $isDev ) {
        $flexi->addDatabase( 'main', array(
                'username' => '',
                'password' => '',
                'database' => '',
                'hostname' => 'localhost'
        ) );
    } else {
        $flexi->addDatabase( 'main', array(
                'username' => '',
                'password' => '',
                'database' => '',
                'hostname' => 'localhost'
        ) );
    }
    
    /* --- --- ---
     *  Pre-Load files
     * 
     * WARNING! They are not loaded right now, they are loaded
     * _after_ the config stage. This is so you can set paths,
     * and load files, in any order.
     * 
     * If you want to load right now, then use either
     * 'loadFile' or 'loadFileIn' from the Flexi class.
     * 
     * --- --- --- */
    
    // core Flexi files (don't remove these!)
    $flexi->load(
            'core/loader',
            'core/corecontroller',
            'core/controller',
            'core/model',
            'core/frame'
    );
    
    // extra Flexi files (optional)
    $flexi->load(
            'lib/browser', // note this is used by start_head
            'lib/tags',
            'lib/string',
            
            'obj/session'
    );
    
    // files stored in app
    $flexi->load(
            'lib/sitecontroller'
    );
