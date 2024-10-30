<?php

/**
 * The rest functionality of the plugin.
 *
 * @link       https://influenet.com/
 * @since      1.0.0
 *
 */


class Influenet_Rest {
    
    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $actions    Actions availables on rest api.
     */
    private $actions = array( 'publish-post', 'list-categories' );

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name ) {

        $this->plugin_name = $plugin_name;

    }

    /**
     * Register rest routes for api requests.
     *
     * @since    1.0.0
     */
    public function register_routes() {

        foreach ( $this->actions as $action ) {
    
            register_rest_route(
                $this->plugin_name, $action,
                array(
                    'methods' => 'POST', 
                    'callback' => array( $this, 'action_' . str_replace( '-', '_', $action ) ),
                    'permission_callback' => array( $this, 'authenticate' ),
                )
            );

        }

    }

    /**
     * Register rest endpoints for direct requests.
     *
     * @since    1.0.0
     */
    public function register_endpoints() {

        foreach ( $this->actions as $action ) {
    
            add_rewrite_endpoint( $this->plugin_name . '-' . $action, EP_ROOT );
    
        }

    }

    /**
     * Intercept request to process.
     *
     * @since    1.0.0
     */
    public function intercept_request() {

        global $wp_query;

        foreach ( $this->actions as $action ) {
    
            if ( isset( $wp_query->query_vars[ $this->plugin_name . '-' . $action ] ) ) {
        
                $method = 'action_' . str_replace( '-', '_', $action );

                if ( $this->authenticate() ) {

                    $response = $this->$method();

                } else {
                    
                    $response = new WP_Error('rest_forbidden', __('Sorry, you are not allowed to do that.'), array( 'status' => 401 ) );

                }

                $this->response($response);
                
            }
        }

        return;

    }

    /**
     * Perform authentication.
     *
     * @since    1.0.0
     */
    public function authenticate() {

        $apikey = get_option( 'influenet_api_key' );

        if ( $json = json_decode( file_get_contents( 'php://input', true ) ) ) {

            foreach ( $json as $id => $value ) {

                if ( ! isset( $_POST[$id] ) ) $_POST[$id] = $value;

            }

        }

        return ((! empty($_POST['apikey']) && ! empty($apikey) && $_POST['apikey'] == $apikey) ? true : false);

    }

    /**
     * Perform authentication.
     *
     * @since    1.0.0
     */
    public function response( $response ) {
        
        if ( is_wp_error( $response ) ) {

            $code = $response->get_error_code();

            $message = $response->get_error_message($code);

            $data = $response->get_error_data($code);
        
            $status = isset($data['status']) ? $data['status'] : 400;

            $json = array('code' => $code, 'message' => $message, 'data' => $data);

        } else {

            $status = $response->get_status();

            $json = $response->get_data();

        }
        
        $statuses = array(
            100 => 'Continue', 101 => 'Switching Protocols', 200 => 'OK', 201 => 'Created', 202 => 'Accepted', 
            203 => 'Non-Authoritative Information', 204 => 'No Content', 205 => 'Reset Content', 206 => 'Partial Content', 
            300 => 'Multiple Choices', 301 => 'Moved Permanently', 302 => 'Moved Temporarily', 303 => 'See Other', 
            304 => 'Not Modified', 305 => 'Use Proxy', 400 => 'Bad Request', 401 => 'Unauthorized', 402 => 'Payment Required', 
            403 => 'Forbidden', 404 => 'Not Found', 405 => 'Method Not Allowed', 406 => 'Not Acceptable', 
            407 => 'Proxy Authentication Required', 408 => 'Request Time-out', 409 => 'Conflict', 410 => 'Gone', 
            411 => 'Length Required', 412 => 'Precondition Failed', 413 => 'Request Entity Too Large', 414 => 'Request-URI Too Large', 
            415 => 'Unsupported Media Type', 500 => 'Internal Server Error', 501 => 'Not Implemented', 502 => 'Bad Gateway', 
            503 => 'Service Unavailable', 504 => 'Gateway Time-out', 505 => 'HTTP Version not supported',
        );

        header( (isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0') . ' ' . $status . ' ' . $statuses[$status] );
        
        header( 'Content-Type:application/json; charset=UTF-8' );
        
        echo json_encode($json);

        exit;

    }

    /**
     * Determine current user.
     *
     * @since    1.0.0
     */
    public function determine_current_user( $user ) {

        global $wp_json_basic_auth_error;

        $wp_json_basic_auth_error = null;

        // Don't authenticate twice
        if ( ! empty( $user ) ) {

            return $user;

        }

        if ( !isset( $_SERVER['PHP_AUTH_USER'] ) && ( isset( $_SERVER['HTTP_AUTHORIZATION'] ) || isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ) ) {

            if ( isset( $_SERVER['HTTP_AUTHORIZATION'] ) ) {

                $header = $_SERVER['HTTP_AUTHORIZATION'];

            } else {

                $header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];

            }

            if ( !empty( $header ) ) {

                list( $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'] ) = explode( ':', base64_decode(substr( $header, 6 ) ) );

            }

        }

        // Check that we're trying to authenticate
        if ( !isset( $_SERVER['PHP_AUTH_USER'] ) ) {

            return $user;

        }

        $username = $_SERVER['PHP_AUTH_USER'];
        $password = $_SERVER['PHP_AUTH_PW'];

        remove_filter( 'determine_current_user', array( $this, 'determine_current_user' ), 10 );

        $user = wp_authenticate( $username, $password );

        add_filter( 'determine_current_user', array( $this, 'determine_current_user' ), 10 );

        if ( is_wp_error( $user ) ) {

            $wp_json_basic_auth_error = $user;

            return null;

        }

        $wp_json_basic_auth_error = true;

        return $user->ID;

    }

    /**
     * Rest authentication errors.
     *
     * @since    1.0.0
     */
    function rest_authentication_errors( $error ) {

        if ( ! empty( $error ) ) {

            return $error;

        }

        global $wp_json_basic_auth_error;

        return $wp_json_basic_auth_error;

    }

    /**
     * Perform action publish-post.
     *
     * @since    1.0.0
     */
    public function action_publish_post( $request = null ) {

        $post = array();

        $post_author = get_option( 'influenet_author_id' );

        if( ! ctype_digit(str_replace(',', '', $_POST['categories'])))
            return false;

        if ( isset( $_POST["categories"] ) ) $post['post_category'] = explode(',', $_POST['categories']);
        if ( isset( $_POST["title"] ) ) $post['post_title'] = sanitize_title_with_dashes( $_POST['title'] );
        if ( isset( $post_author ) ) $post['post_author'] = $post_author;
        if ( isset( $_POST['content'] ) ) $post['post_content'] = apply_filters( 'the_content', $_POST['content'] );        
        $post['post_status'] = 'draft';

        $args = array(
                        'post_type'   => 'post',                        
                        'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash'),
                        'meta_query'  => array(
                            array(
                                'key'           => 'meta_influenet',
                                'value'         => sanitize_key( $_POST['article_id'] )
                            )
                        )
                    );
        $the_query = new WP_Query( $args );
        if($the_query->have_posts())
        {
                $the_query->the_post();
                return get_the_ID();
        }

        $post_id = wp_insert_post( wp_slash( $post ), true );
        add_post_meta($post_id, 'meta_influenet', sanitize_key( $_POST['article_id'] ));

        return $post_id;
    }

    /**
     * Perform action list-categories.
     *
     * @since    1.0.0
     */
    public function action_list_categories( $request = null ) {

        if ( empty( $_POST['hide_empty'] ) ) $_POST['hide_empty'] = false;

        $categories = get_categories( $_POST );

        if ( $categories ) foreach ( $categories as $id => $category ) {
        
         $categories[$id] = array(
             'id'       => intval( $category->term_id ),
             'name'     => $category->name,
             'slug'     => $category->slug,
             'taxonomy' => $category->taxonomy,
         );
        
        }
        
        return new WP_REST_Response( $categories, 200 );

    }

}
