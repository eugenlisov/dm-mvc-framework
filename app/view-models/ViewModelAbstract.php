<?php
namespace DM_MVC\ViewModels;

use DM_MVC\Models\Model;

abstract class ViewModelAbstract {

    // static $escape = [];
    protected $record;
    protected $just_properties;
    protected static $field_groups = [
        'standard' => ['id'],
    ];
    

    public function __construct( Model $record = null ) {
        $this -> record = $record;
    }

    final public function with( $properties = [] ) {
        $this -> just_properties = $properties;

        foreach ($properties as $key => $property) {
            if ( ! empty( $this -> record -> $property ) ) {
                $this -> $property = $this -> record -> $property;
            }
        }

        $this -> set_speciffic_properties();
        unset( $this -> record );
        unset( $this -> just_properties );
        return $this;

    }


    protected function set_speciffic_properties() {
        // Define in each child.
    }


    public static function view( $record, $fields_data = 'standard' ) {
        $fields = static::fields( $fields_data );

        if ( is_array( $record ) ) {

            $return = [];
            foreach ($record as $key => $single_record) {
                $return[] = static::initialize( $single_record, $fields );
            }
            return $return;

        }

        return static::initialize( $record, $fields );

    }


    private static function initialize( Model $record, $fields = [] ) {
        return ( new static( $record ) ) -> with( $fields );
    }

    
    /**
     * Builds the 'fields' array from the data provided.
     * $fields_data can be:
     * - an array: In this case, we simply return $fields_data
     * - a string: In this case we return one of the static field groups.
     */
    private static function fields( $fields_data ) {
        if ( empty( $fields_data ) ) return static::$field_groups['standard'];

        if ( is_array( $fields_data ) ) return $fields_data;

        // After this point we asume it's a string.
        if ( empty( static::$field_groups[$fields_data] ) ) return static::$field_groups['standard'];

        return static::$field_groups[$fields_data];

    }


}

