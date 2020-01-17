<?php
namespace DM_MVC\ViewModels;

use DM_MVC\Models\Model;

abstract class ViewModelAbstract {

    // static $escape = [];
    protected $record;
    protected $just_properties;

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


}

