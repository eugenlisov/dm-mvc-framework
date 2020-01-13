<?php
namespace DM_MVC\Controllers;

class ShortcodeAbstract {

    protected static $shortcodes;

    public function __construct() {
        add_action( 'init', array( $this, 'register_shortcodes' ) );
    }


    /**
     * Loops through each $shortcodes and initializes it
     */
    public function register_shortcodes() {
        if ( is_admin() ) return;

        foreach (static::$shortcodes as $shortcode => $function) {
            add_shortcode( $shortcode, array( $this, $function ) );
        }
    }


}
