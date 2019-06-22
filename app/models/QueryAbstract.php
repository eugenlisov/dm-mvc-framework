<?php

namespace DM_MVC\Models;

abstract class QueryAbstract {

    public $post_type;
    public $post_status = 'publish';
    public $posts_per_page = -1;


    public function __construct( $post_type = '' ) {
        $this -> post_type = $post_type;
    }


    public static function new( $post_type = '' ) {
        $instance = new static( $post_type );
        return $instance;
    }

    public function build() {
        // Do nothing. Child will take care of it.
    }


    // Makes rue we only return the first n results.
    public function take( Int $number ) {
        $this -> posts_per_page = $number;

        return $this;
    }


    // TEMP: Only used on the writers results page for now.
    // ORDER BY WRITER_NUMBER_OF_TIMES_RETURNED_IN_RESULTS ASC
    // NOTE: This might not work properly with the regular query.
    // TODO: Maybe move this to the QueryClassic.
    // Details here: https://codex.wordpress.org/Class_Reference/WP_Meta_Query
    public function order_by( $property = '', $type = 'NUMERIC', $order = 'ASC') {

        $key = $this -> post_type . '_' . $property;

        $order_clause = [
            'key' => $key,
            'type' => $type // u
        ];

        $this -> order = $order;
        $this -> orderby = 'order_clause';
        $this -> meta_query['order_clause'] = $order_clause;

        return $this;

    }

}
