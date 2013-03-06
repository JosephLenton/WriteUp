<?php
    /**
     * Decodes a base64 file into it's extension and pure data.
     * 
     * The supported extensions should _not_ include the dot. For example
     * if you want to support Jpeg and PNG then you would pass in:
     * 
     *  array( 'jpg', 'jpeg', 'png' )
     * 
     * @param data The base64 encoded data.
     * @param supportedExts Optional, a filter containing supported extensions.
     */
    /* 
     * For info on the 'str_replace' bit,
     * see: http://www.php.net/manual/en/function.base64-decode.php#102113
     */
    function decodeBase64File( $data, $supportedExts=null )
    {
        $type = substrchar( $data, '/', ';' );
        
        if ( $supportedExts && !in_array($type, $supportedExts) ) {
            return null;
        } else {
            /* 
             * important we don't use intermediate variables for the data
             * incase the image is megabytes in size.
             */
            return array(
                    'ext'  => $type,
                    'data' => base64_decode(
                                    str_replace( ' ', '+',
                                            substr(
                                                    $data,
                                                    strpos( $data, "," ) + 1
                                            )
                                    )
                            )
            );
        }
    }
    
    /**
     * Given a filename, this returns the file extension at the end.
     * The extension does not include the preceeding '.'.
     * 
     * @param filename The name of the file to get the extension of.
     * @return The extension of the file.
     */
    function getFileExtension( $filename )
    {
        // this grabs the extension, but includes the '.', so we need to remove it
        $dotExt = strrchr( strtolower($filename), '.' );
        if ( $dotExt === false || $dotExt === '.' ) {
            return null;
        } else {
            return substr( $dotExt, 1 );
        }
    }
    
    /**
     * Works out the type of the given file and then loads it.
     * The loaded image is then returned.
     * 
     * @param file The path of the image to load.
     * @return The file loaded as an image.
     */
    function loadImage( $file )
    {
        $image_type = exif_imagetype( $file );
        
        if( $image_type == IMAGETYPE_JPEG ) {
            $image = imagecreatefromjpeg( $file );
        } else if( $image_type == IMAGETYPE_PNG ) {
            $image = imagecreatefrompng(  $file  );
        } else if( $image_type == IMAGETYPE_GIF ) {
            $image = imagecreatefromgif( $file );
        } else {
            throw new Exception( "Unknown image type" );
        }
        
        return $image;
    }
    
    /**
     * Saves the given image to the stated path.
     * 
     * The image is saved using the extension in the path.
     * So the path _must_ end with an image extension!
     * 
     * @param image The image to save.
     * @param path Where to save the image.
     * @return True on success, false on failure.
     */
    function saveImage( $image, $path )
    {
        ensureFileExists( $path );
        
        $ext = strtolower( getFileExtension( $path ) );
        
        if ( $ext == 'jpg' || $ext == 'jpeg' ) {
            return imagejpeg( $image, $path, 91 );
        } else if ( $ext == 'png' ) {
            return imagepng( $image, $path, 9 );
        } else if ( $ext == 'gif' ) {
            return imagegif( $image, $path );
        } else {
            throw new Exception( "Unknown extension " . $ext );
        }
    }
    
    function resizeCropImage( $image, $width, $height, $supersample=true )
    {
        $imgWidth  = imagesx( $image );
        $imgHeight = imagesy( $image );
        
        $scale = max( $width / $imgWidth, $height / $imgHeight );
        $resizedImage = resizeImage( $image,
                round($imgWidth*$scale), round($imgHeight*$scale), $supersample );
        
        return cropImage( $resizedImage, $width, $height );
    }
    
    function cropImage( $image, $width, $height )
    {
        $imgWidth  = imagesx( $image );
        $imgHeight = imagesy( $image );
        
        $drawX = ( $imgWidth  - $width  ) / 2;
        $drawY = ( $imgHeight - $height ) / 2;
        
        $new_image = imagecreatetruecolor( $width, $height );
        
        imagecopy(
                $new_image, $image,
                0, 0,
                $drawX, $drawY,
                $width, $height
        );
        
        return $new_image;
    }
    
    /**
     * Takes an image and returns a new one at a new size.
     * 
     * @param image The image to resize
     * @param width The width of the new image.
     * @param height The Height of the new image.
     * @param supersample True if supersampling should be used to smooth the image, otherwise false. Default is false.
     * @return A copy of the given image, but resized.
     */
    function resizeImage( $image, $width, $height, $supersample=true )
    {
        $new_image = imagecreatetruecolor( $width, $height );

        if ( $supersample ) {
            imagecopyresampled(
                    $new_image, $image,
                    0, 0, 0, 0,
                    $width, $height,
                    imagesx( $image ), imagesy( $image )
            );
        } else {
            imagecopyresized(
                    $new_image, $image,
                    0, 0, 0, 0,
                    $width, $height,
                    imagesx( $image ), imagesy( $image )
            );
        }
        
        return $new_image;
    }
    
    function resizeImageFile( $in, $out, $width, $height, $supersample=true )
    {
        $image = loadImage( $in );
        
        $newImage = resizeImage(
                $image,
                $width, $height,
                $supersample
        );
        
        return saveImage( $newImage, $out );
    }
    
    function resizeCropImageFile( $in, $out, $width, $height, $supersample=true )
    {
        $image = loadImage( $in );
        
        $newImage = resizeCropImage(
                $image,
                $width, $height,
                $supersample
        );
        
        return saveImage( $newImage, $out );
    }
    
    function cropImageFile( $in, $out, $width, $height )
    {
        $image = loadImage( $in );
        
        $newImage = cropImage(
                $image,
                $width, $height
        );
        
        return saveImage( $newImage, $out );
    }
