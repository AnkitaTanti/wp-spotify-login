<?php
require_once ( dirname(__FILE__) . '/Spotify_Credential_List_Table.php');
class WPSL_Common_Task_Manager 
{
    public function __construct() {
        $dir = dirname( __FILE__ );
        // ACTION TO START SESSION IS NOT STARTED
        add_action( 'init', function () {
            if( !session_id() )
                session_start();
        });
        // ACTION TO ADD PLUGIN ADMIN MENU/PAGE
        add_action('admin_menu',array($this,'wpsl_admin_menu'));        
        add_action('admin_action_save_credentials', array($this,'save_credentials_admin_action') );
    }
    
    function wpsl_admin_menu(){
        add_menu_page( __( 'Manage Spotify Credentials', 'wp-spotify-login' ), __( 'Manage Spotify Credentials', 'wp-spotify-login' ), 'manage_options', 'wpsl-manage-sf-credentials',array($this,'manage_spotifylogin_details'));

        add_submenu_page('wpsl-manage-sf-credentials',__( 'Add New Credentials', 'wp-spotify-login' ) ,__( 'Add New Credentials', 'wp-spotify-login' ), 'manage_options', 'wpsl-add-sf-credentials', array($this,'add_spotify_details'));
    }
     
    public function manage_spotifylogin_details()
    {
    ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Manage Spotify Credentials','wp-spotify-login'); ?></h1>
            <?php
                echo sprintf('<a href="%s" class="page-title-action">%s</a>',admin_url( 'admin.php?page=wpsl-add-sf-credentials' , is_ssl() ? 'https' : 'http'),__('Add New Credentials','wp-spotify-login'));
            ?>
            <form method="post">
                <?php
                $exampleListTable = new Spotify_Credential_List_Table();
                $exampleListTable->prepare_items();
                $exampleListTable->display();
                ?>
            </form>
        </div>
    <?php
    }

    public function add_spotify_details()
    {
        // INITIALIZATION
        $app_name = $app_id = $app_secret = $wpsl_id = "";
        $title = '<h1>Add Spotify Details</h1>';
        if(isset($_REQUEST['action']) && $_REQUEST['action']=='edit' && isset($_REQUEST['spotify_credential']) && $_REQUEST['spotify_credential']!="")
        {
            $wpsl_id = $_REQUEST['spotify_credential'];
            global $wpdb;
            $prefix = $wpdb->prefix;
            $tablename = $prefix.'spotifylogin_credentials';
            $app_details = $wpdb->get_row( "SELECT * FROM $tablename where id = ". $wpsl_id );
            if ( null !== $app_details ) 
            {
                $app_name = $app_details->country_nm;
                $app_id = $app_details->app_id;
                $app_secret = $app_details->app_secret;
                $title = '<h1 class="wp-heading-inline">Edit Spotify Details</h1>';
                $title .= sprintf('<a href="%s" class="page-title-action">%s</a>',admin_url( 'admin.php?page=wpsl-add-sf-credentials' , is_ssl() ? 'https' : 'http'),__('Add New Credentials','wp-spotify-login'));
                $title .= '<hr class="wp-header-end">';
            }
        }?>
        <div class="wrap">
            <?php
                echo $title; 
                if(isset($_SESSION['wpsl_admin_msg']) && $_SESSION['wpsl_admin_msg']!="")
                {
                    echo $_SESSION['wpsl_admin_msg'];
                    $_SESSION['wpsl_admin_msg'] = "";
                }
            ?>
            <form action="<?php echo admin_url( 'admin.php' , is_ssl() ? 'https' : 'http'); ?>" method="post">
                <table class="form-table">
                    <tbody>
                        <tr class="sf-app-name-wrap">
                            <th><label for="sf_app_name"><?php _e('App Name:','wp-spotify-login'); ?></label></th>
                            <td><input type="text" name="sf_app_name" id="sf_app_name" value="<?php echo $app_name; ?>" class="regular-text"></td>
                        </tr>
                        <tr class="sf-app-id-wrap">
                            <th><label for="sf_app_id"><?php _e('Spotify App ID:','wp-spotify-login'); ?></label></th>
                            <td><input type="text" name="sf_app_id" id="sf_app_id" value="<?php echo $app_id; ?>" class="regular-text"></td>
                        </tr>
                        <tr class="sf-app-secret-wrap">
                            <th><label for="sf_app_secret"><?php _e('Spotify App Secret:','wp-spotify-login'); ?></label></th>
                            <td><input type="text" name="sf_app_secret" id="sf_app_secret" value="<?php echo $app_secret; ?>" class="regular-text"></td>
                        </tr>
                    <tbody>
                </table> 
                <input type="hidden" name="wpsl_id" id="wpsl_id" value="<?php echo $wpsl_id; ?>" />
                <input type="hidden" name="action" value="save_credentials" />
                <?php submit_button();?>
            </form>
        </div>
    <?php
    }
    

    public function save_credentials_admin_action()
    {
        if(isset($_POST['submit']) && $_POST['submit']=="Save Changes")
        {
            $wpsl_id = $_POST['wpsl_id'];
            
            if(isset($_POST['sf_app_id']) && $_POST['sf_app_id']!="")
                $sf_app_id = $_POST['sf_app_id'];
            else
            {
                $_SESSION['wpsl_admin_msg'] = '<div id="error" class="notice notice-error is-dismissible"><p><strong>'.__('ERROR','wp-spotify-login').'</strong>: '.__('Spotify App ID can not be blank.','wp-spotify-login').'</p></div>';
                
                wp_redirect(admin_url( 'admin.php?page=wpsl-add-sf-credentials' , is_ssl() ? 'https' : 'http'));
                exit();
            }

            if(isset($_POST['sf_app_name']) && $_POST['sf_app_name']!="")
                $sf_app_name = $_POST['sf_app_name'];
            else
            {
                $_SESSION['wpsl_admin_msg'] = '<div id="error" class="notice notice-error is-dismissible"><p><strong>'.__('ERROR','wp-spotify-login').'</strong>: '.__('App Name can not be blank.','wp-spotify-login').'</p></div>';
                
                wp_redirect(admin_url( 'admin.php?page=wpsl-add-sf-credentials' , is_ssl() ? 'https' : 'http'));
                exit();
            }
            
            if(isset($_POST['sf_app_secret']) && $_POST['sf_app_secret']!="")
                $sf_app_secret = $_POST['sf_app_secret'];
            else
            {
                $_SESSION['wpsl_admin_msg'] = '<div id="error" class="notice notice-error is-dismissible"><p><strong>'.__('ERROR','wp-spotify-login').'</strong>: '.__('Spotify App Secret can not be blank.','wp-spotify-login').'</p></div>';
                
                wp_redirect(admin_url( 'admin.php?page=wpsl-add-sf-credentials' , is_ssl() ? 'https' : 'http'));
                exit();
            }
            
            global $wpdb;
            $prefix = $wpdb->prefix;
            $tablename = $prefix.'spotifylogin_credentials';
            
            // CHECK FOR DUPLICATION
            if(isset($wpsl_id) && $wpsl_id!="")
                $app_cnt = $wpdb->get_var( "SELECT COUNT(*) FROM $tablename where app_id = '". $sf_app_id ."' AND app_secret='".$sf_app_secret."' AND id!=".$wpsl_id );
            else
                $app_cnt = $wpdb->get_var( "SELECT COUNT(*) FROM $tablename where app_id = '". $sf_app_id ."' AND app_secret='".$sf_app_secret."'" );
            
            if($app_cnt > 0)
            {
                $_SESSION['wpsl_admin_msg'] = '<div id="error" class="notice notice-error is-dismissible"><p><strong>'.__('DUPLICATION ERROR','wp-spotify-login').'</strong>: '.__('This Spotify App ID and App Secret already registered. Please enter another one.','wp-spotify-login').'</p></div>';
                
                wp_redirect(admin_url( 'admin.php?page=wpsl-add-sf-credentials' , is_ssl() ? 'https' : 'http'));
                exit();
            }
            else
            {
                $query_args = array( 'country_nm'=>$sf_app_name, 'app_id'=>$sf_app_id, 'app_secret'=>$sf_app_secret);

                if(isset($wpsl_id) && $wpsl_id!=""){
                    $wpdb->update( $tablename, $query_args, array( 'ID' => $wpsl_id ) );
                    $url_to_pass = 'admin.php?page=wpsl-add-sf-credentials&action=edit&spotify_credential='.$wpsl_id;
                }
                else{
                    $wpdb->insert( $tablename, $query_args );
                    $url_to_pass = 'admin.php?page=wpsl-add-sf-credentials&action=edit&spotify_credential='.$wpdb->insert_id;
                }
                $_SESSION['wpsl_admin_msg'] = '<div id="message" class="updated notice notice-success is-dismissible"><p><strong>'.__('SUCCESS','wp-spotify-login').'</strong>: '.__('Spotify Credential Saved.','wp-spotify-login').'</p></div>';
             
                $url = admin_url( $url_to_pass , is_ssl() ? 'https' : 'http');
                wp_redirect($url);  
                exit();
            }
        }
        wp_redirect(admin_url( 'admin.php?page=wpsl-add-sf-credentials' , is_ssl() ? 'https' : 'http'));
        exit();
    }
}
?>