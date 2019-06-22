<?php
namespace DM_MVC\Models;

class User {

    public $id;
    public $name;
    public $email;

    public function __construct( $user = '' ) {

        // This check is here because we can instantiate both with an object and an ID.
        if ( ! is_a( $user, 'WP_User' ) ) {
            $user = get_post ( $user );
        }

        $this -> user  = $user;
        $this -> id    = $this -> user -> data -> ID;
        $this -> name  = $this -> user -> data -> display_name;
        $this -> email = $this -> user -> data -> user_email;

         unset ( $this -> user );

    }

    /**
     * Creates a user and logs him in using the array passes from the ajax controller.
     * @param  [type] $args [description]
     * @return User model
     */
    public static function create($user = []) {

        if ( empty( $user ) ) return false;

        $userdata = array(
            'user_login'   => $user['email'],
            'user_email'   => $user['email'],
            'user_pass'    => $user['password'],
            'display_name' => $user['first_name'] . ' ' . $user['last_name'],
            'first_name'   => $user['first_name'],
            'last_name'    => $user['last_name'],
            'role'         => $user['role'], // NOTE: This must be provided by the Client / Writer ajax controller.
        );

        $user_id = wp_insert_user( $userdata );

        self::log_in( $user_id );

        return $user_id;

    }


    public static function log_in( $user_id = '' ) {

        if ( empty( $user_id ) ) return;

        $user = get_user_by( 'id', $user_id );

        if( $user ) {
            wp_set_current_user( $user_id, $user -> user_login );
            wp_set_auth_cookie( $user_id );
            do_action( 'wp_login', $user -> user_login );

        }

    }

    public static function role() {

        $user_id = get_current_user_id();
        $user = get_user_by( 'id', $user_id );

        if ( ! isset( $user -> roles[0] ) ) return '';

        return $user -> roles[0];

    }


    public static function all() {

        $args = array(
        	'role'         => 'client',
         );

         $return = [];

         $users = get_users( $args );

         // echo '<pre>';
         // print_r( $users );
         // echo '</pre>';

         foreach ($users as $key => $user) {

             $user = new static( $user );

             // echo '<pre>';
             // print_r( $post );
             // echo '</pre>';

             $return[$user -> id] = $user;
         }

         return $return;

    }
}
