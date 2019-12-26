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
        add_action('add_meta_boxes',array($this, 'competition_spotify_artist_metabox'));
        add_action('admin_action_save_credentials', array($this,'save_credentials_admin_action') );
        add_action('save_post', array($this,'add_competitions_logincountry_field'));
    }
    
    function wpsl_admin_menu(){
        add_menu_page( __( 'Manage Spotify Credentials', 'wp-spotify-login' ), __( 'Manage Spotify Credentials', 'wp-spotify-login' ), 'manage_options', 'wpsl-manage-sf-credentials',array($this,'manage_spotifylogin_details'));

        add_submenu_page('wpsl-manage-sf-credentials',__( 'Add New Credentials', 'wp-spotify-login' ) ,__( 'Add New Credentials', 'wp-spotify-login' ), 'manage_options', 'wpsl-add-sf-credentials', array($this,'add_spotify_details'));
    }

    function competition_spotify_artist_metabox()
    {
        add_meta_box('spotify_country_meta_box', __('Select Country','wp-spotify-login'),array($this,'spotify_country_lists_competition'), 'competition', 'normal', 'high');
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
        $country_nm = $app_id = $app_secret = $wpsl_id = "";
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
                $country_nm = $app_details->country_nm;
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
                        <?php
                            $countries = array("Afghanistan", "Albania", "Algeria", "American Samoa", "Andorra", "Angola", "Anguilla", "Antarctica", "Antigua and Barbuda", "Argentina", "Armenia", "Aruba", "Australia", "Austria","Azerbaijan", "Bahamas", "Bahrain", "Bangladesh", "Barbados", "Belarus", "Belgium", "Belize","Benin", "Bermuda", "Bhutan", "Bolivia", "Bosnia and Herzegowina", "Botswana", "Bouvet Island","Brazil", "British Indian Ocean Territory", "Brunei Darussalam", "Bulgaria", "Burkina Faso","Burundi", "Cambodia", "Cameroon", "Canada", "Cape Verde", "Cayman Islands", "Central African Republic","Chad", "Chile", "China", "Christmas Island", "Cocos (Keeling) Islands", "Colombia", "Comoros", "Congo","Congo, the Democratic Republic of the", "Cook Islands", "Costa Rica", "Cote d'Ivoire", "Croatia (Hrvatska)", "Cuba", "Cyprus", "Czech Republic", "Denmark", "Djibouti", "Dominica","Dominican Republic", "East Timor", "Ecuador", "Egypt", "El Salvador", "Equatorial Guinea","Eritrea", "Estonia", "Ethiopia", "Falkland Islands (Malvinas)", "Faroe Islands", "Fiji","Finland", "France", "France Metropolitan", "French Guiana", "French Polynesia", "French Southern Territories", "Gabon", "Gambia", "Georgia", "Germany", "Ghana", "Gibraltar", "Greece", "Greenland", "Grenada", "Guadeloupe", "Guam", "Guatemala", "Guinea", "Guinea-Bissau","Guyana", "Haiti", "Heard and Mc Donald Islands", "Holy See (Vatican City State)", "Honduras", "Hong Kong", "Hungary", "Iceland", "India", "Indonesia", "Iran (Islamic Republic of)", "Iraq", "Ireland", "Israel", "Italy", "Jamaica", "Japan", "Jordan", "Kazakhstan", "Kenya", "Kiribati", "Korea, Democratic People's Republic of", "Korea, Republic of", "Kuwait", "Kyrgyzstan", "Lao, People's Democratic Republic", "Latvia", "Lebanon", "Lesotho", "Liberia", "Libyan Arab Jamahiriya", "Liechtenstein", "Lithuania", "Luxembourg", "Macau", "Macedonia, The Former Yugoslav Republic of", "Madagascar", "Malawi", "Malaysia", "Maldives", "Mali", "Malta", "Marshall Islands", "Martinique", "Mauritania", "Mauritius", "Mayotte", "Mexico", "Micronesia, Federated States of", "Moldova, Republic of", "Monaco", "Mongolia", "Montserrat", "Morocco", "Mozambique", "Myanmar", "Namibia", "Nauru", "Nepal", "Netherlands", "Netherlands Antilles", "New Caledonia","New Zealand", "Nicaragua", "Niger", "Nigeria", "Niue", "Norfolk Island", "Northern Mariana Islands", "Norway", "Oman", "Pakistan", "Palau", "Panama", "Papua New Guinea", "Paraguay", "Peru", "Philippines", "Pitcairn", "Poland", "Portugal", "Puerto Rico", "Qatar", "Reunion", "Romania", "Russian Federation", "Rwanda", "Saint Kitts and Nevis", "Saint Lucia", "Saint Vincent and the Grenadines", "Samoa", "San Marino", "Sao Tome and Principe", "Saudi Arabia", "Senegal", "Seychelles", "Sierra Leone", "Singapore", "Slovakia (Slovak Republic)", "Slovenia", "Solomon Islands", "Somalia", "South Africa", "South Georgia and the South Sandwich Islands", "Spain", "Sri Lanka", "St. Helena", "St. Pierre and Miquelon", "Sudan", "Suriname", "Svalbard and Jan Mayen Islands", "Swaziland", "Sweden", "Switzerland", "Syrian Arab Republic", "Taiwan, Province of China", "Tajikistan", "Tanzania, United Republic of", "Thailand", "Togo", "Tokelau", "Tonga", "Trinidad and Tobago", "Tunisia", "Turkey", "Turkmenistan", "Turks and Caicos Islands", "Tuvalu", "Uganda", "Ukraine", "United Arab Emirates", "United Kingdom", "United States", "United States Minor Outlying Islands", "Uruguay","Uzbekistan", "Vanuatu", "Venezuela", "Vietnam", "Virgin Islands (British)", "Virgin Islands (U.S.)", "Wallis and Futuna Islands", "Western Sahara", "Yemen", "Yugoslavia", "Zambia", "Zimbabwe");
                        ?>
                        <tr class="sf-app-country-wrap">
                            <th><label for="sf_app_id"><?php _e('Select Country:','wp-spotify-login'); ?></label></th>
                            <td>
                                <select name="sf_app_country" id="sf_app_country">
                                    <?php 
                                        foreach ($countries as $value):
                                            $selected = "";
                                            if($country_nm == $value)
                                                $selected = ' selected="selected"';
                                        ?>
                                        <option value="<?php echo $value; ?>" <?php echo $selected; ?>>
                                        <?php echo $value; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
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
            $sf_app_country = $_POST['sf_app_country'];
            $wpsl_id = $_POST['wpsl_id'];
            
            if(isset($_POST['sf_app_id']) && $_POST['sf_app_id']!="")
                $sf_app_id = $_POST['sf_app_id'];
            else
            {
                $_SESSION['wpsl_admin_msg'] = '<div id="error" class="notice notice-error is-dismissible"><p><strong>'.__('ERROR','wp-spotify-login').'</strong>: '.__('Spotify App ID can not be blank.','wp-spotify-login').'</p></div>';
                
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
                $query_args = array( 'country_nm'=>$sf_app_country, 'app_id'=>$sf_app_id, 'app_secret'=>$sf_app_secret);

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

    /* RENDERING METABOX WITH OPTIONS STARTS HERE */
    function spotify_country_lists_competition($post) {
        global $wpdb;
        $selected_country = get_post_meta($post->ID, 'spotify_country_table_id');
        $fb_tablenm = $wpdb->prefix . 'spotifylogin_credentials';
        $sql = 'SELECT * FROM '.$fb_tablenm;
        $country_apps = $wpdb->get_results($sql);
        echo '<select name="spotifycountry_id" id="spotifycountry_id" class="">';
        echo '<option value="">'.__("SELECT COUNTRY",'spotifyartists').'</option>';

        if($country_apps)
        {
            foreach ($country_apps as $countryObj) 
            {
                $selected = '';
                if (!empty($selected_country))
                {   
                    if($selected_country[0]==$countryObj->id){
                        $selected = ' selected';
                    }
                }
                echo '<option value="'.$countryObj->id.'"'.$selected.'>'. $countryObj->country_nm.'</option>';
            }
        }
        echo '</select>';
    }
    /* RENDERING METABOX WITH OPTIONS ENDS HERE */


    ## CODE TO SAVE METABOX DATA IN COMPITTION PAGE
    function add_competitions_logincountry_field($post) {
        if (get_post_type($post) == 'competition')
        {
            if (isset($_POST['spotifycountry_id']) && $_POST['spotifycountry_id'] != "")
            {
                if (!empty(get_post_meta($post, 'spotify_country_table_id')))
                {
                    update_post_meta($post, 'spotify_country_table_id', $_POST['spotifycountry_id']);
                }
                else
                {
                    add_post_meta($post, 'spotify_country_table_id', $_POST['spotifycountry_id'], $unique = false);
                }
            }
        }
    }
    
}
?>