<?php

class WCML_Switch_Lang_Request{

    /** @var string $default_language */
    protected $default_language;
    /** @var WPML_WP_API */
    protected $wp_api;
    /** @var WPML_Cookie */
    private   $cookie;
    /** @var Sitepress */
    private $sitepress;

    function __construct( WPML_Cookie $cookie, WPML_WP_API $wp_api, Sitepress $sitepress ){

        if( !is_admin() ){
            $this->cookie = $cookie;
            $this->wp_api = $wp_api;
            $this->sitepress = $sitepress;
            $this->default_language = $this->sitepress->get_default_language();
        }
    }

    public function add_hooks(){

        if( !is_admin() && $this->sitepress->get_setting( $this->wp_api->constant( 'WPML_Cookie_Setting::COOKIE_SETTING_FIELD' ) ) ) {
            add_action( 'wpml_before_init', array( $this, 'detect_user_switch_language' ) );
        }
    }

    public function detect_user_switch_language(){

        if ( ! $this->wp_api->constant( 'DOING_AJAX' ) ) {

            $lang_from = $this->get_cookie_lang();
            $lang_to   = $this->get_requested_lang();

            if ( $lang_from && $lang_from !== $lang_to ) {
                $referer_url = $this->get_referer_url_cookie();

                /**
                 * Hook fired when the user changes the site language
                 *
                 * @param string $lang_from   the previous language
                 * @param string $lang_to     the new language
                 * @param string $referer_url the previous URL
                 */
                do_action( 'wcml_user_switch_language', $lang_from, $lang_to, $referer_url );
            }

            $this->set_referer_url_cookie();
        }

    }

    /**
     * @return string language code stored in the user's _icl_current_language cookie
     */
    public function get_cookie_lang() {
        global $wpml_language_resolution;

        $cookie_name  = $this->get_cookie_name();
        $cookie_value = $this->cookie->get_cookie( $cookie_name );
        $lang         = $cookie_value ? substr( $cookie_value, 0, 10 ) : null;
        $lang         = $wpml_language_resolution->is_language_active( $lang ) ? $lang : $this->default_language;

        return $lang;
    }

    public function get_cookie_name() {

        return '_icl_current_language';
    }

    /**
     * @return string
     */
    public function get_referer_url_cookie() {
        return urldecode( $this->cookie->get_cookie( $this->get_referer_url_cookie_name() ) );
    }


    public function set_referer_url_cookie() {
        if ( ! $this->cookie->headers_sent() ) {

            $this->cookie->set_cookie(
                $this->get_referer_url_cookie_name(),
                $this->get_request_url(),
                time() + DAY_IN_SECONDS,
                defined( 'COOKIEPATH' ) ? COOKIEPATH : '/',
                $this->get_cookie_domain()
            );
        }
    }

    /**
     * @return bool|string
     */
    public function get_cookie_domain() {

        return defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : $this->get_server_host_name();
    }

    /**
     * @return string
     */
    public function get_request_url() {
        $scheme = isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host   = $this->get_server_host_name();
        $uri    = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';

        return $scheme . '://' . $host . $uri;
    }

    /**
     * Returns SERVER_NAME, or HTTP_HOST if the first is not available
     *
     * @return string
     */
    public function get_server_host_name() {
        $host = isset( $_SERVER[ 'HTTP_HOST' ] ) ? $_SERVER[ 'HTTP_HOST' ] : null;
        $host = $host !== null
            ? $host
            : ( isset( $_SERVER[ 'SERVER_NAME' ] )
                ? $_SERVER[ 'SERVER_NAME' ]
                . ( isset( $_SERVER[ 'SERVER_PORT' ] ) && ! in_array( $_SERVER[ 'SERVER_PORT' ], array( 80, 443 ) )
                    ? ':' . $_SERVER[ 'SERVER_PORT' ] : '' )
                : '' );

        //Removes standard ports 443 (80 should be already omitted in all cases)
        $result = preg_replace( "@:[443]+([/]?)@", '$1', $host );

        return $result;
    }

    /**
     * @return string
     */
    public function get_referer_url_cookie_name() {
        return 'wpml_referer_url';
    }

    public function get_requested_lang() {

        return $this->is_comments_post_page() ? $this->get_cookie_lang() : $this->get_request_uri_lang();
    }

    public function is_comments_post_page() {
        global $pagenow;

        return 'wp-comments-post.php' === $pagenow;
    }

    /**
     * @global $wpml_url_converter
     *
     * @return string|false language code that can be determined from the currently requested URI.
     */
    public function get_request_uri_lang() {
        global $wpml_url_converter;

        $req_url = isset($_SERVER[ 'HTTP_HOST' ])
            ? untrailingslashit($_SERVER[ 'HTTP_HOST' ] . $_SERVER[ 'REQUEST_URI' ] ) : "";

        return $wpml_url_converter->get_language_from_url ( $req_url );
    }

}