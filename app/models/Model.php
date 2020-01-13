<?php
/**
 * Model v.1.1
 * @var [type]
 */
namespace DM_MVC\Models;

abstract class Model {

    public $id;
    public $name; // NB: This is the post_title. On most models, the name is not 100% accurate.

    public static $serialized_props = [];
    public static $post_type = '';
    public static $query; // This will store the query instance, instantiated by the where() method.
    public $fillable = [];
    public $manual = [];
    public $array_props = [];
    public $boolean_fields  = [];
    public $multiline_fields  = [];

    public static $meta_query = ['relation' => 'AND'];


    public function __construct( $post = '', $autoload = false) {

        if ( ! isset( $post ) || empty( $post ) ) return;

        if ( is_a( $post, 'WP_Post' ) ) {
            $this -> id = $post -> ID;
            $this -> user_id = $post -> post_author;
        } else {
            $this -> id = $post;
            $post = get_post( $post );
        }

        if ( empty( $post ) ) return; // NOTE: Not sure in which situations, the POST is empty.

        if ( $post ) {
            $this -> name = $post -> post_title;
        }

        $this -> post_date_gmt = $post -> post_date_gmt;

        $this -> extract_meta_properties();
        $this -> filter_boolean_fields();

        $this -> initialize_array_properties();
        $this -> filter_multiline_fields();
    }


    /**
     * Grabs all models of the current type
     * @return Array [description]
     */
    public static function all($autoload = false) {

        $post_type = static::$post_type;

        $args = array(
          'numberposts' => -1,
          'post_type'   => $post_type,
          'post_status' => 'publish'
        );

        $posts = get_posts( $args );

        $return = [];

        foreach ($posts as $key => $post) {

            $post = new static( $post, $autoload );

            $return[] = $post;
        }

        return $return;

    }


    public static function create( $data = [] ) {

        $post_id = self::new_post( $data );

        $post = static::find($post_id);
        $post -> populate_fillables( $data );
        $post -> save();

        return $post;

    }

    public static function new_post( $data = [] ) {

        $post = array(
            'post_title'    => static::title_format( $data ),
            'post_content'  => '',
            'post_status'   => 'publish',
            'post_author'   => get_current_user_id(),
            'post_type'     => static::$post_type,
        );

        $post_id = wp_insert_post( $post );

        return $post_id;

    }


    /**
     * Used only be the 'create()' method.
     * Given an array, it will asign each fillabe value as a property
     */
    public function populate_fillables( Array $data = [] ) {

        foreach ( $this -> fillable as $key => $property) {
            if ( isset( $data[$property] ) ) {
                $this -> $property = $data[$property];
            }
        }



    }


    public function update( Array $data = [] ) {

        foreach ( $this -> fillable as $key => $property) {
            if ( isset( $data[$property] ) ) {
                $this -> $property = $data[$property];
            }
        }

        foreach ( $this -> manual as $key => $property) {
            if ( isset( $data[$property] ) ) {
                $this -> $property = $data[$property];
            }
        }

        $this -> save();

    }


    /**
     * Returns all matches for a query
     * NOTE: Unlike the where() method in the Query model, this one here does not accept the third propery. So it can't do advanced queries.
     * @param  array  $data [description]
     * @return [type]       [description]
     */
    public static function where( $data = [] ) {

        if ( empty( $data ) ) return [];

        $query = Query::new( static::$post_type );

        foreach ($data as $property => $value) {
            $query -> where( $property, $value );
        }

        return static::get($query);

    }


    /**
     * Returns the first match for a where query
     * Identical to where() above, but returns only 1 result.
     * @param  array  $data [description]
     * @return [type]       [description]
     */
    public static function firstWhere( $data = [] ) {

        if ( empty( $data ) ) return [];

        $query = Query::new( static::$post_type )
                -> take(1);

        foreach ($data as $property => $value) {
            $query -> where( $property, $value );
        }

        $result = static::get($query);

        if ( ! isset( $result[0] ) || empty( $result[0] ) ) return []; // TODO: See if it would be better to return false;
        return $result[0];

        return static::get($query);

    }


    /**
     * Loops through all the fillable properties and saves the value as a custom field.
     */
    public function save() {

        // Because stuff from Vue / Front end JS comes as string, we must filter the boolean strings back to proper booleans before saving to the database
        $this -> filter_front_boolean_fields();

        foreach ( $this -> fillable as $key => $property) {
            if ( isset( $this -> $property ) ) {

                $custom_field_name = static::$post_type . '_' . $property;

                update_post_meta( $this -> id, $custom_field_name, $this -> $property );

            }
        }


        // Do the same for the non-mass updatable
        foreach ( $this -> manual as $key => $property) {
            if ( isset( $this -> $property ) ) {

                $custom_field_name = static::$post_type . '_' . $property;

                update_post_meta( $this -> id, $custom_field_name, $this -> $property );

            }
        }

    }



    /**
     * Returns the current model with the ID
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public static function find( $id ) {
        return new static( $id );
    }



    public function extract_meta_properties() {

        $post_type = static::$post_type;

        $skip_prefix = '_' . $post_type . '_';
        $match_prefix = $post_type . '_';

        // HACK: For posts generated programatically, the ACF fields are not saved as expected.
        $meta = [];
        $raw_meta = get_post_meta( $this -> id, '', true );

        if ( empty( $raw_meta ) ) return;

        foreach ($raw_meta as $key => $item) {

            if (strpos($key, $skip_prefix) !== false) continue;
            if (strpos($key, $match_prefix) !== false) {

                $property = str_replace($match_prefix, "", $key);

                if ( in_array( $property, static::$serialized_props ) ) {
                    if ( isset( $item[0] ) && ! empty( $item[0] ) ) {
                        $this -> $property = unserialize( $item[0] );
                    } else {
                        $this -> $property = '';
                    }

                } else {

                    if ( isset( $item[0] ) && ! empty( $item[0] ) ) {
                        $this -> $property = $item[0];
                    } else {
                        $this -> $property = '';
                    }
                }

            };
        }

    }


    /**
     * Turns all ACF Checkboxes with a 'Yes' checked into Booleans.
     */
    protected function filter_boolean_fields() {

            foreach ($this -> boolean_fields as $key => $property) {

                if ( ! isset( $this -> $property ) ) continue;

                $data = $this -> $property;
                if ( ! isset( $data[0] ) ) continue;

                if ( $data[0] == '1' ) {
                    $this -> $property = true;
                } else {
                    $this -> $property = false;
                }

            }

    }


    protected function filter_front_boolean_fields() {

            foreach ($this -> boolean_fields as $key => $property) {

                if ( ! isset( $this -> $property ) ) continue;

                if ( $this -> $property == 'true' ) {
                    $this -> $property = true;
                } else {
                    $this -> $property = false;
                }

            }

    }


    protected function filter_multiline_fields() {

        foreach ($this -> multiline_fields as $key => $property) {

            if ( ! isset( $this -> $property ) ) continue;

            $this -> $property = $this -> clean_multiline( $this -> $property );

        } 

    }


    protected function clean_multiline( $string ) {

        $full_string = explode( PHP_EOL, $string );
        $clean_string = [];

        foreach ($full_string as $key => $paragraph) {
            $line = trim( $paragraph );
            if ( $line == '' ) {
                unset( $full_string[$key] );
            } else {
                $clean_string[] = $line ;
            }

        }

        return implode( "<br />", $clean_string );

    }


    public function initialize_array_properties() {

        foreach ($this -> array_props as $key => $property) {

            // If not yet initialized, set it as empty array.
            if ( ! isset( $this -> $property ) ) {
                $this -> $property = [];
                return;
            };

            if ( !is_array( $this -> $property ) ) {
                $this -> $property = [];
                return;

            }

            // TODO: Figure out if there are other situations where the property must be set as an array;
        }
    }


    public static function get( QueryAbstract $query ) {

        // $query -> build(); // This is for the Query Classic


        // For QueryClassic, if post__in is empty return an empty array. Otherwise, the Model will return all the posts.
        $class_name = get_class( $query );
        if ( $class_name == 'DM_PRW\Models\QueryClassic' ) { // TODO: This should reflect a dependency in the current repo
            if ( !isset( $query -> post__in ) || empty( $query -> post__in ) ) return [];
        }

        $args = (array)$query;

        $posts = get_posts( $args );

        if ( empty( $posts ) ) return [];

        $models = [];
        foreach ($posts as $key => $post) {
            $models[] = new static( $post );
        }

        return $models;

    }

}
