<?php
/**
 * Query v.1.1
 * @var [type]
 */
namespace DM_MVC\Models;

class Query extends QueryAbstract {

    public $meta_query = ['relation' => 'AND'];
    public $order = '';
    public $orderby = '';


    /**
     * [where description]
     * @param  string $property [description]
     * @param  string $value    [description]
     * @param  string $compare  [description]
     * @param  string $type     NUMERIC | ASD
     * @return [type]           [description]
     */
    public function where( $property = '', $value = '', $compare = '=', $type = '') {

        $full_property = $this -> post_type . '_' . $property;

        $where = [
            'key'     => $full_property,
            'value'   => $value,
            'compare' => $compare,
        ];

        if ( isset( $type ) && ! empty( $type ) ) {
            $where['type'] = $type;
        }

        $this -> meta_query[] = $where;

        return $this;

    }


    public function order_date_asc() {
        $this -> order = 'ASC';
        $this -> orderby = 'date';

        return $this;
    }

    /**
     * [relation description]
     * @param  string $relation 'OR' or 'AND'
     * @return [type]           [description]
     */
    public function relation( $relation = 'AND' ) {
        $this -> meta_query = ['relation' => $relation];

        return $this;
    }





}
