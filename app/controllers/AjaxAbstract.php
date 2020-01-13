<?php
namespace DM_MVC\Controllers;

class AjaxAbstract {

    protected static $endpoints;

    public function __construct() {
        add_action( 'init', array( $this, 'register_endpoints' ) );
    }


    /**
     * Loops through each $endpoints and initializes it
     */
    public function register_endpoints() {

        foreach (static::$endpoints as $endpoint => $function) {

            $regular = 'wp_ajax_' . $endpoint;
            $nopriv  = 'wp_ajax_nopriv_' . $endpoint;

            add_action( $regular, array( $this, $function ) );
            add_action( $nopriv, array( $this, $function ) );

        }
    }


}
