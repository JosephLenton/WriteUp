<?
	// stop scripts from auto-exiting
	define( 'ACCESS_OK', true );
    
    require( 'flexi/flexi.php' );
    
    $flexi = new Flexi();
	$flexi->loadConfig( 'config.php' );
	$flexi->loadConfig( 'config_local.php', true );
	$flexi->loadConfig( 'frames.php' );
	
	// run the website!
    $flexi->run( $_SERVER['REQUEST_URI'] );
    $flexi->events()->runOnEnd();
