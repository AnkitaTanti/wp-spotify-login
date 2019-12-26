<?php

// WP_List_Table is not loaded automatically so we need to load it in our application
if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
/**
 * Create a new table class that will extend the WP_List_Table
 */
class Spotify_Credential_List_Table extends WP_List_Table
{
    /**
     * Prepare the items for the table to process
     *
     * @return Void
     */
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
        
        $this->process_bulk_action();

        
        $data = $this->table_data();
        usort( $data, array( &$this, 'sort_data' ) );
        $perPage = 10;
        $currentPage = $this->get_pagenum();
        $totalItems = count($data);
        $this->set_pagination_args( array(
            'total_items' => $totalItems,
            'per_page'    => $perPage
        ) );
        $data = array_slice($data,(($currentPage-1)*$perPage),$perPage);
        $this->_column_headers = array($columns, $hidden, $sortable);
        $this->items = $data;
    }
    /**
     * Override the parent columns method. Defines the columns to use in your listing table
     *
     * @return Array
     */
    public function get_columns()
    {
        $columns = array(
            'cb'          => '<input type="checkbox" />',
            'country'       => __('Country','wp-spotify-login'),
            'app_id' => __('Spotify App ID','wp-spotify-login'),
            'app_secret'        => __('Spotify App Secret','wp-spotify-login'),
            'wpsl_shortcode'    => __('Shortcode','wp-spotify-login')
        );
        return $columns;
    }
    /**
     * Define which columns are hidden
     *
     * @return Array
     */
    public function get_hidden_columns()
    {
        return array();
    }
    /**
     * Define the sortable columns
     *
     * @return Array
     */
    public function get_sortable_columns()
    {
        return array('country' => array('country', false));
    }
    /**
     * Get the table data
     *
     * @return Array
     */
    private function table_data()
    {
        $data = array();
        
        global $wpdb;
        $prefix = $wpdb->prefix;
        $tablename = $prefix.'spotifylogin_credentials';
        $results = $wpdb->get_results("SELECT * FROM ".$tablename);
        if ( $results )
        {
            foreach ( $results as $result )
            {   
                $data[] = array(
                                    'id'          => $result->id,
                                    'country'       => $result->country_nm,
                                    'app_id' => $result->app_id,
                                    'app_secret'        => $result->app_secret,
                                    'wpsl_shortcode'    => '[CS-SPOTIFY-LOGIN wpsl_id="'.$result->id.'"]'
                                );
            }
        }
        
        return $data;
    }
    
    function no_items() {
        _e( 'No Credentials Found.','wp-spotify-login');
    }
    
    /**
     * Define what data to show on each column of the table
     *
     * @param  Array $item        Data
     * @param  String $column_name - Current column name
     *
     * @return Mixed
     */
    public function column_default( $item, $column_name )
    {
        switch( $column_name ) {
            case 'country':
            case 'app_id':
            case 'app_secret':
            case 'wpsl_shortcode':
                return $item[ $column_name ];
            default:
                return print_r( $item, true ) ;
        }
    }
    /**
     * Allows you to sort the data by the variables set in the $_GET
     *
     * @return Mixed
     */
    private function sort_data( $a, $b )
    {
        // If no sort, default to country
        $orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'country';
        // If no order, default to asc
        $order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
        // Determine sort order
        $result = strcmp( $a[$orderby], $b[$orderby] );
        // Send final sort direction to usort
        return ( $order === 'asc' ) ? $result : -$result;
    }
    
    function get_bulk_actions() {
        $actions = array(
          'delete'    => __('Delete','wp-spotify-login')
        );
        return $actions;
    }
    function get_current_action() {
        $action = $this -> current_action();
        if ( $action && array_key_exists( $action, $this -> get_bulk_actions() ) ) {
                return $action;
        }
        return false;
    }
    function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="spotify_credential[]" value="%s" />', $item['id']
        );    
    }
    function process_bulk_action() {
        //Detect when a bulk action is being triggered...
        if( 'delete'===$this->current_action() ) {
            global $wpdb;
            foreach($_REQUEST['spotify_credential'] as $id) {
                $wpdb->delete($wpdb->prefix.'spotifylogin_credentials', array('id' => $id));
            }
        }
    }
    function column_country($item) 
    {
        $actions = array(
                            'edit' => sprintf('<a href="?page=%s&action=%s&spotify_credential=%s">Edit</a>','wpsl-add-sf-credentials','edit',$item['id']),
                            'delete' => sprintf('<a href="?page=%s&action=%s&spotify_credential[]=%s">Delete</a>',$_REQUEST['page'],'delete',$item['id']),
                        );

        return sprintf('%1$s %2$s', $item['country'], $this->row_actions($actions) );
    }
}

