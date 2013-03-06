<?
    /**
     * tags.php
     * 
     * Contains shorthand functions for generating tags.
     * For example to generate an img HTML tag, you can
     * just call:
     * 
     *  echo img( 'images/foo.png' );
     * 
     * What is special is that this will silently add on
     * cache busting, and the CDN prefix, if the CDN is
     * in use.
     */
    
    /**
     * Generates an image tag using the path given.
     * This uses the 'cdn_link' to generate the path,
     * so if a CDN is set, it will be used!
     * 
     * It will also use cache busting, if enabled!
     * 
     * WARNING! Double quotation marks are _not_
     * escaped, you must do them yourself or avoid
     * using them.
     * 
     * It can be used in one of two ways,
     * 
     * 1) List all the parameters. For example:
     * 
     *     img( '/images/foo.png', 'foo_class', 'foo_id', $width, $height, "an alternative string to display" )
     * 
     * 2) You can pass in an array for emulating the above:
     * 
     *     img( '/images/foo.png, array(
     *             'class' => 'foo_class',
     *             'id' => 'foo_id',
     *             'width' => $width,
     *             'height' => $height,
     *             'alt' => "alternative text to display",
     * 
     *             'data-something' => "some data embedded in the img tag"
     *     ) );
     * 
     * @return A string formatting a HTML img tag.
     */
    function img( $path, $classes = null, $id = null, $width = null, $height = null, $alt = null ) {
        $params = '';
        
        if ( $classes !== null && is_array($classes) ) {
            $params = array();
            
            // concats all values together into:
            // 'name="val" '
            // (note the trailing space, so further values are concatted ok!)
            foreach ( $classes as $name => $val ) {
                $params[]= $name;
                $params[]= '="';
                $params[]= $val;
                $params[]= '" ';
            }
            
            $params = join( '', $params );
        } else {
            if ( $classes ) {
                $params .= ' class="' . $classes . '"';
            }
            
            if ( $id ) {
                $params .= ' id="' . $id . '"';
            }
            
            if ( $width ) {
                $params .= ' width="' . $width . '"';
            }
            if ( $height ) {
                $params .= ' height="' . $height . '"';
            }
            
            if ( $alt ) {
                $params .= ' alt="' . $alt . '"';
            }
        }
        
        return '<img src="' . cdn_link($path) . '" ' . $params . '>';
    }
    
    function css( $path, $extras='' ) {
        return '<link rel="stylesheet" type="text/css" href="' . cdn_link( $path ) . '" ' . $extras . '>';
    }
    
    function js( $path, $type='', $extra='' ) {
        if ( $type ) {
            $type = ' type="' . $type . '"';
        }
        
        return '<script ' . $type . ' ' . $extra . ' src="' . cdn_link( $path ) . '"></script>';
    }
    
    function cdn_link( $path ) {
        return file_uri_link( Flexi::getFlexi()->getCDN(), $path );
    }
    
    function file_link( $path ) {
        return file_uri_link( Flexi::getFlexi()->getRootURI(), $path );
    }
    
    /**
     * Cache busting (the first one) inserts time stamps into
     * a file name. So 'style.css' becomes 'style.93483048.css'.
     * 
     * If it can't, it falls onto the second type of caching,
     * which is where the timestamp is appended as a URL
     * parameter. For example: 'style.css?v=93483048'
     * 
     * Cache busting is the better alternative, but requires
     * an HTACCESS file to undo the cache busting effect.
     * 
     * The second one doesn't sit well with many proxies,
     * including those used by some ISPs.
     */
    if ( Flexi::getFlexi()->get('is_cachebusting') ) {
        function file_uri_link( $uri, $path ) {
            // cut a hole for http linked images
            if ( strlen($path) >= 7 && substr_compare($path, 'http://', 0, 7, true) === 0 ) {
                return $path;
            } else {
                $localPath = Flexi::getFlexi()->fileExists( $path );
                
                if ( $localPath !== false ) {
                    $ext = strrchr($path, '.');
                    
                    // use 'cachebusting', where you call the file 'script.filetime.js'
                    if ( $ext !== false ) {
                        $location =
                                // -1 is to keep the '.'
                                substr( $path, 0, strlen($path)-(strlen($ext)-1) ) .
                                filemtime( $localPath ) .
                                $ext ;
                    // if no extension, use a query string for caching
                    } else {
                        $location = $path . '?v=' . filemtime( $localPath );
                    }
                } else {
                    $location = $path;
                }
                
                if (
                        strlen($uri) > strlen($location) ||
                        substr_compare($location, $uri, 0, strlen($uri)) !== 0
                ) {
                    return $uri . $location;
                } else {
                    return $location;
                }
            }
        }
    } else {
        function file_uri_link( $uri, $path ) {
            if ( strlen($path) >= 7 && substr_compare($path, 'http://', 0, 7, true) === 0 ) {
                return $path;
            } else {
                $localPath = Flexi::getFlexi()->fileExists( $path );

                if ( $localPath !== false ) {
                    $location = $path . '?v=' . filemtime( $localPath );
                } else {
                    $location = $path;
                }
                
                if (
                        strlen($uri) > strlen($location) ||
                        substr_compare($location, $uri, 0, strlen($uri)) !== 0
                ) {
                    return $uri . $location;
                } else {
                    return $location;
                }
            }
        }
    }
    
    function title( $title ) {
        return '<title>' . $title . '</title>';
    }
    
    function meta( $name, $content ) {
        return '<meta name="' . $name . '" content="' . $content . '">';
    }
