<?php
/**
 * Plugin Name:       Spotify Login
 * Description:       A shortcode based spotify login plugin which allows you to create multiple spotify login buttons with different App Id and App Secret.
 * Version:           1.0.0
 * Author:            Ankita Tanti
 * Text Domain:       wp-spotify-login
 **/

// DON'T ALLOW DIRECT ACCESS TO FILE
if ( ! defined( 'ABSPATH' ) ) {
    die( '-1' );
}

define( 'SPOTIFY_PLUGIN_VERSION', '1.0' );

/* Plugin Constants */
if (!defined('SPOTIFY_PLUIGIN_URL')) {
    define('SPOTIFY_PLUIGIN_URL', plugin_dir_url(__FILE__));
}

if (!defined('SPOTIFY_PLUGIN_PATH')) {
    define('SPOTIFY_PLUGIN_PATH', plugin_dir_path(__FILE__));
}

require_once (SPOTIFY_PLUGIN_PATH . 'resources/WPSL_Install_Tables.php');
require_once (SPOTIFY_PLUGIN_PATH . 'resources/WPSL_Common_Task_Manager.php');

require (SPOTIFY_PLUGIN_PATH . 'SpotifyAPI/vendor/autoload.php');
require (SPOTIFY_PLUGIN_PATH . 'SpotifyAPI/Request.php');
require (SPOTIFY_PLUGIN_PATH . 'SpotifyAPI/SpotifyWebAPI.php');
require (SPOTIFY_PLUGIN_PATH . 'SpotifyAPI/Session.php');
require (SPOTIFY_PLUGIN_PATH . 'SpotifyAPI/SpotifyWebAPIException.php');
require (SPOTIFY_PLUGIN_PATH . 'SpotifyAPI/SpotifyWebAPIAuthException.php');

global $wpsl_db_version;
$wpsl_db_version = '1.0';
$install_tbl = new WPSL_Install_Tables();
register_activation_hook( __FILE__, array($install_tbl,'wpsl_install') );
$common_tasks_obj = new WPSL_Common_Task_Manager();

class SpotifyLogin {

    private $initiated = false;

    /**
     * Redirection Url
     *
     * @var string
     */
    private $redirect_url = '';

    public function __construct() {
        add_shortcode( 'CS-SPOTIFY-LOGIN', array($this, 'spotifylogin_front_button') );
        $this->init();
    }

    public function init() {
        if ( ! $this->initiated ) {
            $this->init_hooks();
        }
    }

    public function init_hooks() {
        $this->initiated = true;
        add_action( 'wp_enqueue_scripts', array($this,'load_resources'));
        add_action( 'wp_ajax_nopriv_ajax_spotify_login', array($this,'ajax_spotify_login'));
        add_action( 'wp_ajax_nopriv_process_spotify_login', array($this,'process_spotify_login'));  
    }

    public function load_resources() {
        wp_register_script( 'spotify.js', SPOTIFY_PLUIGIN_URL . 'resources/js/spotify.js' , array('jquery'), SPOTIFY_PLUGIN_VERSION );
        wp_enqueue_style( 'spotify.css', SPOTIFY_PLUIGIN_URL . 'resources/css/spotify.css' , array(), SPOTIFY_PLUGIN_VERSION );
        $localization_arr = array(  'ajaxurl' => admin_url( 'admin-ajax.php'),
                                    '_spotify_login_nonce' => wp_create_nonce('spotify_login_nonce'));

        wp_localize_script('spotify.js','spotify_exchanger',$localization_arr);
        wp_enqueue_script( 'spotify.js' );
    }

    function spotifylogin_front_button($atts) {

        // TO HIDE LOGIN WITH POTIFY BUTTON IF USER IS ALREADY LOGGED IN
        if(is_user_logged_in())
            return;

        /* INITIALIZATION */
        $return_string = '';
        global $post;
        $_SESSION['page_id'] = $post->ID;
        $session_key = 'page_'.$_SESSION['page_id'];
        $protocol = ( is_ssl() ? 'https' : 'http' );
        $_SESSION[$session_key]['spotify_redirection_url'] = $protocol.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        /* INITIALIZATION */

        if(isset($_SESSION[$session_key]['spotify_error_msg']) && $_SESSION[$session_key]['spotify_error_msg']!="") {
            $return_string .= sprintf('<div class="alert alert-danger alert-dismissable"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><strong> %s</strong> %s</div>',__('Failure!', 'wp-spotify-login'),$_SESSION[$session_key]['spotify_error_msg']);
            $_SESSION[$session_key]['spotify_error_msg'] = "";
        }

        $return_string .= '<div class="spotify-login-wrapper">';
        if(isset($atts['wpsl_id'])){
            $return_string .= '<input type="hidden" name="spotify_id" id="spotify_id" value="'.$atts['wpsl_id'].'" />';
        }

        $return_string .='<div id="spotify-login-btn" class="btn btn-light-green">'.__('Login with Spotify','wp-spotify-login').'</div>';
        $return_string .='</div>';
        return $return_string;
    }

    public function ajax_spotify_login() 
    {
        if(!session_id()) {
            session_start();
        }

        $session_key = '';
        if(isset($_SESSION['page_id']) && $_SESSION['page_id']!="") 
            $session_key = 'page_'.$_SESSION['page_id'];

        // REDIRECTION URL 
        $this->redirect_url = (isset($_SESSION[$session_key]['spotify_redirection_url'])) ? $_SESSION[$session_key]['spotify_redirection_url'] : home_url();
        if (wp_verify_nonce($_POST['_wpnonce'], 'spotify_login_nonce') === false) {
            $_SESSION[$session_key]['spotify_error_msg'] = __('Invalid Request! Nonce not verified.','wp-spotify-login');
        }
        else if(!(isset($_POST['spotify_id']) && $_POST['spotify_id'])){
            $_SESSION[$session_key]['spotify_error_msg'] = __('Invalid Request! Someting went wrong.','wp-spotify-login');
        }
        else {
            $spotify_id = $_POST['spotify_id'];
            global $wpdb;
            $prefix = $wpdb->prefix;
            $tablename = $prefix.'spotifylogin_credentials';
            
            $app_details = $wpdb->get_row( "SELECT * FROM $tablename where id = ". $spotify_id );
            if ( null !== $app_details ) {
                $_SESSION[$session_key]['spotify_id'] = $spotify_id;
                $spotify_redirect_url = admin_url('admin-ajax.php') . "?action=process_spotify_login";

                $session = new SpotifyWebAPI\Session(
                                    $app_details->app_id,
                                    $app_details->app_secret,
                                    $spotify_redirect_url
                                );
                $options = ['scope' =>['user-read-private','user-read-email']];
                try {
                    echo $redirect_url = $session->getAuthorizeUrl($options);
                    exit();
                }
                catch (SpotifyWebAPI\SpotifyWebAPIException $e) { 
                    $_SESSION[$session_key]['spotify_error_msg'] = __('Invalid Request! Someting went wrong during the authentication.','wp-spotify-login');
                }
                catch(Exception $e) {
                    $_SESSION[$session_key]['spotify_error_msg'] = __('Oops! Something went wrong. ','wp-spotify-login');
                }
            } 
            else {
                $_SESSION[$session_key]['spotify_error_msg'] = __('Invalid Request! Someting went wrong.','wp-spotify-login');
            }
        }

        // IF ANYTHING GOES WRONG, USER WILL GET REDIRECTED TO THE COMPETITION PAGE WITH AN ERROR MESSAGE
        if(isset($_SESSION[$session_key]['spotify_redirection_url']))
            $_SESSION[$session_key]['spotify_redirection_url'] = "";

        echo $this->redirect_url; exit();
        exit();
    }
    public function process_spotify_login(){
        if(!session_id()) {
            session_start();
        }
        $session_key = '';
        if(isset($_SESSION['page_id']) && $_SESSION['page_id']!="") 
            $session_key = 'page_'.$_SESSION['page_id'];

        // REDIRECTION URL 
        $this->redirect_url = (isset($_SESSION[$session_key]['spotify_redirection_url'])) ? $_SESSION[$session_key]['spotify_redirection_url'] : home_url();

        $spotify_id = 0;
        if(isset($_SESSION[$session_key]['spotify_id']))
            $spotify_id = $_SESSION[$session_key]['spotify_id'];

        if($spotify_id!=0)
        {
            global $wpdb;
            $prefix = $wpdb->prefix;
            $tablename = $prefix.'spotifylogin_credentials';
            
            $app_details = $wpdb->get_row( "SELECT * FROM $tablename where id = ". $spotify_id );
            if ( null !== $app_details ) {  
                $spotify_redirect_url = admin_url('admin-ajax.php') . "?action=process_spotify_login";
                $session = new SpotifyWebAPI\Session(
                                    $app_details->app_id,
                                    $app_details->app_secret,
                                    $spotify_redirect_url
                                );

                $session->requestAccessToken($_GET['code']);
                $accessToken = $session->getAccessToken();
                $_SESSION[$session_key]['spotify_accesstoken'] = $accessToken;

                /******************************** ADDED FOR SCORE CALCULATION **************************************/
                $competition_artistid = get_post_meta($_SESSION['page_id'],'spotify_artist_id',true);
                $headers = [
                                    'Accept: application/json',
                                    'Content-Type: application/json',
                                    'Authorization: Bearer '.$accessToken,
                            ];
                
                $api = new SpotifyWebAPI\SpotifyWebAPI();
                $api->setAccessToken($accessToken);
                $userData = $api->me();
                if (is_object($userData)) {
                    if(isset($userData->email) && isset($userData->external_urls) && isset($userData->id)) {
                        $email = $userData->email;
                        $profile = $userData->external_urls;
                        $profile_url = $profile->spotify;
                        $spotifyuserId = $userData->id;
                        $country = $userData->country;
                        $display_name = $userData->display_name;

                        // CHECK IF ANY ENTRY ALREADY SUBMITTED USING THIS EMAIL ADDRESS OF NOT
                        $username = sanitize_user(str_replace(' ', '-', $spotifyuserId));
                        // CREATING NEW USER ACCOUNT USING PROVIDED DETAILS
                        $new_user = wp_create_user($username, wp_generate_password(), $email);
                        if(is_wp_error($new_user)) 
                        {
                            // CHECK IF THE USER IS REGISTED USING SPOTIFY
                            $wp_user = get_users(array(
                                'search'       => $spotifyuserId,
                                'meta_key'     => 'user_spotify_id',
                                'number'       => 1,
                                'count_total'  => false,
                                'fields'       => 'id',
                                'role'         => 'subscriber'
                            ));

                            // IF NOT, THE SAME USER IS REGISTERED USING SOME OTHER METHOD
                            if(empty($wp_user[0])) { 
                                $error_arr = (array) $new_user;
                                // CHECK IF USER NAME EXIST 
                                if(array_key_exists('existing_user_login',$error_arr['errors'])) {
                                    //THROW AN ERROR AS USER ALREADY REGISTERED USING SOME OTHER LOGIN METHOD WITH SAME USER NAME
                                    $_SESSION['spotify_error_msg'] = $new_user->get_error_message();
                                }
                                else if(array_key_exists('existing_user_email',$error_arr['errors'])) {
                                    // REGISTER USER WITH USER NAME AND PASSWORD ONLY(WITHOUT ANY EMAIL ADDRESS)
                                    $new_user = wp_create_user( $username, wp_generate_password(),'');  
                                }
                                else {
                                    $_SESSION['spotify_error_msg'] = $new_user->get_error_message();
                                    wp_redirect( $this->redirect_url ); 
                                    exit(); 
                                }
                            } 
                            else {
                                // ELSE ALLOW THAT USER TO LOGIN
                                $new_user = $wp_user[0];
                            }
                        }

                        update_user_meta( $new_user, 'user_spotify_id', $spotifyuserId );
                        update_user_meta( $new_user, 'profile_url', $profile_url);
                        update_user_meta( $new_user, 'country', $country);
                        update_user_meta( $new_user, 'display_name', $display_name);
                        wp_set_auth_cookie( $new_user );
                    }
                    else {
                        $_SESSION[$session_key]['spotify_error_msg'] = __('Missing data! Please allow asked permission to login app.','wp-spotify-login');
                    }   
                }
            }
        } else {
            $_SESSION[$session_key]['spotify_error_msg'] = __('Invalid Request! Someting went wrong.','wp-spotify-login');
            if(isset($_SESSION[$session_key]['spotify_redirection_url']))
                $_SESSION[$session_key]['spotify_redirection_url'] = "";
        }
        wp_redirect($this->redirect_url);
        exit(); 
    }
}
$spotifyObj = new SpotifyLogin();