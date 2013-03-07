<?php
    $flexi->loadFile( 'obj', 'opengraphtags' );

    /**
     * A PMC Specific version of the OpenGraphTags generator.
     */
    class SiteOpenGraphTags extends OpenGraphTags
    {
        public function __construct()
        {
            $defaultUrl = "http://" . $_SERVER['HTTP_HOST'];
            parent::__construct( $defaultUrl,
                    array(
                            "og:title"          =>  "",
                            "og:site_name"      =>  "",
                            "og:description"    =>  "",
                            "og:url"            =>  '/',
                            "og:image"          =>  "",
                            "og:type"           =>  "website",
                            "fb:admins"         =>  "551373383"    //FB profile no
                    )
            );
        }

        function setArticle( $article ) {
            if ( $article !== null ) {
                $this->setTags( array(
                        'og:title'          => '',
                        'og:description'    => ''
                ) );
            }
        }
    }