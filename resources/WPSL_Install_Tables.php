<?php
class WPSL_Install_Tables  
{
    function wpsl_install()
    {
        global $wpdb;
        global $wpsl_db_version;
        
        $wpsl_credential_tbl = $wpdb->prefix . 'spotifylogin_credentials';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $wpsl_credential_tbl (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    country_nm varchar(255) NOT NULL,
                    app_id varchar(255) NOT NULL,
                    app_secret varchar(255) NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        add_option( 'reg_logindb_version', $wpsl_db_version );
    }
}
?>