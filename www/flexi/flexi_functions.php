<?
    /*
     * These are standard functions used across Flexi, and inside Flexi
     * applications.
     * 
     * This includes common items for getting request queiry parameters,
     * simple file helper functions, and functions for isolating requiring.
     */

    /**
     * var_dump's each argument given,
     * wrapped in a <pre> tag.
     * 
     * That way you can actually read it.
     * 
     * Any number of arguments can be given.
     */
    function dump() {
        foreach ( func_get_args() as $arg ) {
            echo '<pre>';
            var_dump( $arg );
            echo '</pre>';
        }
    }

    /**
     * The same as dump,
     * except this will end after the dump.
     */
    function dumpx() {
        call_user_func_array( 'dump', func_get_args() );
        exit;
    }

    /**
     * Checks if the given path ends with a slash. A path ending with a slash is returned,
     * this is either the original path unaltered (if it already ended with one) or just
     * the original path with a slash appended to it.
     *
     * @param The path to check.
     * @return The path given but ensured to end with a slash (/).
     */
    function ensureEndingSlash( $path ) {
        if (
                $path !== '' &&
                strrpos($path, '/') !== (strlen($path)-1)
        ) {
            return $path . '/';
        } else {
            return $path;
        }
    }

    /**
     * Used to isolate a require.
     *
     * If you loaded a file, which then altered the local
     * variables, you would get unknown effects.
     *
     * Worst of all, this would be difficult to debug.
     *
     * So this ensures only the '$filePath' and '$flexi'
     * are available, and it doesn't matter if the loading
     * script alters either of them.
     *
     * @param filePath The path of the file to require.
     * @param flexi Used so the file can use this Flexi object, if it wishes.
     */
    function requireFileOnce( $filePath, $flexi=null )
    {
        require_once( $filePath );
    }

    /**
     * The same as requireFileOnce, only this is for
     * normal requires.
     *
     * @param filePath The path of the file to require.
     * @param flexi Used so the file can use this Flexi object, if it wishes.
     */
    function requireFile( $filePath, $flexi=null )
    {
        require( $filePath );
    }

    /**
     * Travels the path along each directory in turn.
     * For each directory it checks if it exists, and creates it if it
     * does not.
     *
     * Once it has reached the final item in the path it then touches whatever
     * is there, creating a new empty file if there is not a file there.
     *
     * @param savePath A path to the file to ensure the existance of.
     */
    function ensureFileExists( $savePath )
    {
        // ensure all directories leading to the save location exist
        $paths = explode( '/', $savePath );

        if ( count($paths) === 0 ) {
            throw new Exception("empty path is given");
        } else {
            $dirPath = implode( '/', array_splice($paths, 0, count($paths)-1) );

            ensureDirExists( $dirPath );

            touch( $savePath );
        }
    }

    /**
     * Ensures that all directories leading to the given path exist.
     * They will each be created if they don't.
     */
    function ensureDirExists( $savePath )
    {
        if ( ! is_dir($savePath) ) {
            mkdir( $savePath, 0777, true );
        }
    }

    /**
     * Deletes a directory, including all of it's contents.
     *
     * Note that if this fails half way through, such as not having permissions,
     * then the contents of the folder are left in an unknown state.
     * In practice this means that what could be deleted, was,
     * and what couldn't be, is still there.
     *
     * @return True if all deletion was successful, otherwise false.
     */
    function rmdir_all( $dir ) {
        if ( rmdir_contents($dir) ) {
            return rmdir($dir);
        } else {
            return false;
        }
    }

    /**
     * If the directory exists, then all of the files contained within it
     * are removed. If the directory does not exist, then this silently
     * does nothing.
     * 
     * In either case, the directory is left unaffected.
     * 
     * @param dir The directory to remove the contents of.
     */
    function rmdir_contents( $dir )
    {
        if ( is_dir($dir) ) {
            $objects = scandir($dir);

            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir."/".$object) == "dir") {
                        rmdir_all($dir."/".$object);
                    } else {
                        unlink($dir."/".$object);
                    }
                }
            }

            reset($objects);

            return true;
        } else {
            return false;
        }
    }

    function getGet( $item, $alt=null ) {
        if ( isset($_GET) && isset($_GET[$item]) ) {
            return $_GET[$item];
        } else {
            return $alt;
        }
    }

    function getPost( $item, $alt=null ) {
        if ( isset($_POST) && isset($_POST[$item]) ) {
            return $_POST[$item];
        } else {
            return $alt;
        }
    }

    function getRequest( $item, $alt=null ) {
        if ( isset($_REQUEST) && isset($_REQUEST[$item]) ) {
            return $_REQUEST[$item];
        } else {
            return $alt;
        }
    }

    /**
     * @return true if the item has been passed to this query via get.
     */
    function hasGet( $item ) {
        return isset($_GET) && isset($_GET[$item]);
    }

    /**
     * @return true if the item has been passed to this query via post.
     */
    function hasPost( $item ) {
        return isset($_POST) && isset($_POST[$item]);
    }

    /**
     * @return true if the item has been passed to this request.
     */
    function hasRequest( $item ) {
        return isset($_REQUEST) && isset($_REQUEST[$item]);
    }

    /**
     * @return True if the current request is a POST request.
     */
    function isPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * @return True if the current request is a GET request.
     */
    function isGet() {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    /**
     * @return True if the current request is a PUT request.
     */
    function isPut() {
        return $_SERVER['REQUEST_METHOD'] === 'PUT';
    }

    /**
     * @return True if the current request is a HEAD request.
     */
    function isHead() {
        return $_SERVER['REQUEST_METHOD'] === 'HEAD';
    }

    function flexi_ob_start()
    {
        $isIE = false;
        $ua = $_SERVER['HTTP_USER_AGENT'];
        // quick escape for non-IEs
        if (
            strpos($ua, 'Mozilla/4.0 (compatible; MSIE ') !== 0 ||
            strpos($ua, 'Opera') !== false
        ) {
            $isIE = false;
        } else {
            // no regex = faaast
            $version = (float)substr($ua, 30);
            $isIE = (
                    ($version  < 6) ||
                    ($version == 6 && strpos($ua, 'SV1') === false )
            );
        }

        if ( ! (
                $isIE ||
                ob_start("ob_gzhandler")
        ) ) {
            ob_start();
        }
    }
