<?php
    class Article extends SiteController
    {
        public function __construct() {
            parent::__construct();
        }
        
        public function index( $user=null, $url=null )
        {
            $this->load->view( 'article/index' );
            return;
            if ( $user !== null && $url === null ) {
                $this->getFlexi()->runController( 'user', $user );
            } else if ( $user === null ) {
                $this->getFlexi()->runController( 'home' );
            } else {
                $this->load->view( 'articles/index' );
            }
        }
    }