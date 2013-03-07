<?php
    class Articles extends Model
    {
        public static $ALL_STYLES = array( 'academic', 'informal' );

        public function getLatest( $offset, $limit ) {
return array(); // todo, remove this!

            return $this->db->articles->limit( $offset, $limit )->order( 'created_at' )->gets();
        }
    }
