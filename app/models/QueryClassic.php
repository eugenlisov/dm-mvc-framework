<?php
/**
 * This will query just the custom fields table
 * It does individual SQL queries for each of the custom where clauses, then it intersects the arrays and populates the $post__in property.
 *
 * It uses regular SQL queries because we can't use WP's default meta_field search with too many where clauses.
 */
namespace DM_MVC\Models;

class QueryClassic extends QueryAbstract {

    public $post__in = [];
    public $post__not_in = [];
    public $query_strings = [];


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

        if ( $compare == 'LIKE' ) {
            $string = "meta_key = '$full_property' AND meta_value $compare '%$value%'";
        } else {
            $string = "meta_key = '$full_property' AND meta_value $compare '$value'";
        }


        $this -> query_strings[] = $string;

        return $this;

    }


    /**
     * This is called to do the SQL queries and populate the post__in property.
     * @return [type] [description]
     */
    public function build() {

        if ( !isset( $this -> query_strings) || empty( $this -> query_strings ) ) return;

        $light_queries = $this -> query_strings;
        $full_queries = [];

        foreach ($light_queries as $key => $query) {
            $full_queries[] = 'SELECT post_id FROM wp_postmeta WHERE ' . $query;
        }

        // echo '<pre>';
        // print_r( $full_queries  );
        // echo '</pre>';

        $id_list = [];

        global $wpdb;

        // TODO: Don't do all queries. If any of them is empty, break the loop.
        foreach ($full_queries as $key => $sql) {

            // echo $sql . '<br /><br />';

            $current_results = $wpdb -> get_col( $sql );

            // echo '<pre>';
            // print_r( $current_results );
            // echo '</pre>';

            // If there are any excluded ids, remove them from this array.
            if ( isset( $this -> post__not_in ) && ! empty( $this -> post__not_in ) ) {
                foreach ($this -> post__not_in as $key => $id) {
                    if (($key = array_search($id, $current_results)) !== false) {
                        unset($current_results[$key]);
                    }
                }
            }

            $results[] = $current_results;
        }

        if ( empty( $results ) ) return [];

        // Now intersect all arrays.
        $this -> post__in = $results[0];
        for ($i=1; $i < count( $results ) ; $i++) {
            $this -> post__in  = array_intersect( $this -> post__in, $results[$i] );
        }

        // Cleanup
        unset( $this -> query_strings );

    }


    /**
     * Specify the IDs that you don't want to appear in the results query.
     * NB: Likely used for excluding ids that were retrieved from previous queries.
     * @var Array $ids
     */
    public function exclude( Array $ids ) {
        $this -> post__not_in = $ids;
    }

}
