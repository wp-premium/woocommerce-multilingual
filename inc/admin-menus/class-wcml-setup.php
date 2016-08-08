<?php

class WCML_Setup {

    private $step;
    private $woocommerce_wpml;
    private $sitepress;
    private $next_step = false;


    public function __construct( &$woocommerce_wpml, &$sitepress ){

        $this->woocommerce_wpml =& $woocommerce_wpml;
        $this->sitepress        =& $sitepress;

        $this->steps = array(
            'introduction' => array(
                'name'    =>  __( 'Introduction', 'woocommerce-multilingual' ),
                'view'    => array( $this, 'setup_introduction' ),
                'handler' => ''
            ),
            'store-pages' => array(
                'name'    =>  __( 'Store Pages', 'woocommerce-multilingual' ),
                'view'    => array( $this, 'setup_store_pages' ),
                'handler' => array( $this, 'install_store_pages' ),
            ),
            'attributes' => array(
                'name'    =>  __( 'Global Attributes', 'woocommerce-multilingual' ),
                'view'    => array( $this, 'setup_attributes' ),
                'handler' => array( $this, 'save_attributes' )
            ),
            'multi-currency' => array(
                'name'    =>  __( 'Multiple Currencies', 'woocommerce-multilingual' ),
                'view'    => array( $this, 'setup_multi_currency' ),
                'handler' => array( $this, 'save_multi_currency' )
            ),
            'translation-interface' => array(
                'name'    =>  __( 'Translation Interface', 'woocommerce-multilingual' ),
                'view'    => array( $this, 'setup_translation_interface' ),
                'handler' => array( $this, 'save_translation_interface' )
            ),
            'ready' => array(
                'name'    =>  __( 'Ready!', 'woocommerce-multilingual' ),
                'view'    => array( $this, 'setup_ready' ),
                'handler' => ''
            )
        );

        if( current_user_can( 'manage_options' ) ) {
            if ( isset( $_GET['page'] ) && $_GET['page'] === 'wcml-setup' ) {
                add_action( 'admin_menu', array( $this, 'admin_menus' ) );
            }
            add_action( 'admin_init', array($this, 'setup_wizard') );

            add_action( 'admin_init', array($this, 'handle_steps'), 0 );
            add_filter( 'wp_redirect', array($this, 'redirect_filters') );
        }

        if( !$this->has_completed()){
            add_filter( 'admin_notices', array( $this, 'setup_wizard_notice') );
            add_action( 'admin_init', array( $this, 'skip_setup' ), 1 );
        }

    }

    public function admin_menus() {
        add_dashboard_page( '', '', 'manage_options', 'wcml-setup', '' );
    }

    public function setup_wizard_notice(){
        ?>
        <div id="wcml-setup-wizard" class="updated message fade otgs-is-dismissible">
            <p><?php printf( __('Welcome to %sWooCommerce Multilingual!%s Please take a moment to configure the main settings and then you are ready to start translating your products.', 'woocommerce-multilingual'), '<strong>', '</strong>') ?></p>
            <p class="submit">
                <a href="<?php echo esc_url( admin_url('admin.php?page=wcml-setup') ); ?>" class="button-primary"><?php _e('Run the Setup Wizard', 'woocommerce-multilingual') ?></a>
                <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wcml-setup-skip', 1 ), 'wcml_setup_skip_nonce', '_wcml_setup_nonce' ) ); ?>" class="button-secondary skip"><?php _e('Skip Setup', 'woocommerce-multilingual') ?></a>
            </p>
        </div>
        <?php
    }

    public function skip_setup(){

        if ( isset( $_GET['wcml-setup-skip'] ) && isset( $_GET['_wcml_setup_nonce'] ) ) {
            if ( ! wp_verify_nonce( $_GET['_wcml_setup_nonce'], 'wcml_setup_skip_nonce' ) ) {
                wp_die( __( 'Action failed. Please refresh the page and retry.', 'woocommerce-multilingual' ) );
            }

            if ( ! current_user_can( 'manage_options' ) ) {
                wp_die( __( 'Cheatin&#8217; huh?', 'woocommerce' ) );
            }

            $this->complete_setup();
            remove_filter( 'admin_notices', array( $this, 'setup_wizard_notice') );
        }

    }

    public function complete_setup(){
        $this->woocommerce_wpml->settings['set_up_wizard_run'] = 1;
        $this->woocommerce_wpml->settings['set_up_wizard_splash'] = 1;
        $this->woocommerce_wpml->update_settings();
    }

    public function has_completed(){

        return !empty( $this->woocommerce_wpml->settings['set_up_wizard_run'] );

    }

    public function splash_wizard_on_wcml_pages(){

        if( isset( $_GET['src'] ) && $_GET['src'] == 'setup_later' ){
            $this->woocommerce_wpml->settings['set_up_wizard_splash'] = 1;
            $this->woocommerce_wpml->update_settings();
        }

        if( isset( $_GET['page'] ) && $_GET['page'] == 'wpml-wcml' && !$this->has_completed() && empty( $this->woocommerce_wpml->settings['set_up_wizard_splash'] )){
            wp_redirect('admin.php?page=wcml-setup');
            exit;
        }
    }

    public function setup_wizard() {

        $this->splash_wizard_on_wcml_pages();

        if ( empty( $_GET['page'] ) || 'wcml-setup' !== $_GET['page'] ) {
            return;
        }

        $this->step = isset( $_GET['step'] ) ? sanitize_key( $_GET['step'] ) : current( array_keys( $this->steps ) );

        wp_enqueue_style( 'wcml-setup', WCML_PLUGIN_URL . '/res/css/wcml-setup.css', array( 'dashicons', 'install' ), WCML_VERSION );
        wp_enqueue_script( 'wcml-setup', WCML_PLUGIN_URL . '/res/js/wcml-setup.js', array('jquery'), WCML_VERSION );

        $this->setup_header();
        $this->setup_steps();
        $this->setup_content();
        $this->setup_footer();

        if( $this->step == 'ready' ){
            $this->complete_setup();
        }

        exit;
    }

    private function setup_header() {
        set_current_screen('wcml-setup');
        ?>
        <!DOCTYPE html>
        <html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
            <head>
                <meta name="viewport" content="width=device-width" />
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
                <title><?php _e( 'WooCommerce Multilingual &rsaquo; Setup Wizard', 'woocommerce-multilingual' ); ?></title>
                <?php wp_print_scripts( 'wcml-setup' ); ?>
                <?php do_action( 'admin_print_styles' ); ?>
                <?php do_action( 'admin_head' ); ?>
            </head>
        <body class="wcml-setup wp-core-ui">
        <h1 id="wcml-logo"><a href="https://wpml.org/woocommerce-multilingual"><img src="<?php echo WCML_PLUGIN_URL ?>/res/images/banner-772x120.png" alt="WooCommerce Multilingual" /></a></h1>

        <?php if( !empty( $this->steps[ $this->step ]['handler'] ) ): ?>
        <form class="wcml-setup-form" method="post">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( $this->step ) ?>" />
        <input type="hidden" name="handle_step" value="<?php echo $this->step ?>" />
        <?php endif; ?>
        <?php
    }

    private function setup_content(){

        echo '<div class="wcml-setup-content">';
        call_user_func( $this->steps[ $this->step ]['view'] );
        echo '</div>';

    }

    private function setup_footer() {
        ?>
        <?php if( !empty( $this->steps[ $this->step ]['handler'] ) ): ?>
        </form>
        <?php endif ?>
        </body>
        </html>
        <?php
    }

    private function setup_steps() {

        $steps = array_keys( $this->steps );
        $step_index = array_search( $this->step, $steps );
        $this->next_step = isset( $steps[$step_index + 1] ) ? $steps[$step_index + 1] : '';

        $ouput_steps = $this->steps;
        array_shift( $ouput_steps );
        ?>
        <ol class="wcml-setup-steps">
            <?php foreach ( $ouput_steps as $step_key => $step ) : ?>
                <li class="<?php
                if ( $step_key === $this->step ) {
                    echo 'active';
                } elseif ( array_search( $this->step, array_keys( $this->steps ) ) > array_search( $step_key, array_keys( $this->steps ) ) ) {
                    echo 'done';
                }
                ?>"><?php echo esc_html( $step['name'] ); ?></li>
            <?php endforeach; ?>
        </ol>
        <?php
    }

    private function next_step_url(){
        $url = admin_url('admin.php?page=wcml-setup&step=' . $this->next_step );
        return $url;
    }

    public function setup_introduction(){
        $ui = new WCML_Setup_Introduction_UI( $this->woocommerce_wpml, $this->next_step_url() );
        echo $ui->get_view();
    }

    public function setup_store_pages(){

        $ui = new WCML_Setup_Store_Pages_UI( $this->woocommerce_wpml, $this->sitepress, $this->next_step_url() );
        echo $ui->get_view();
    }

    public function setup_attributes(){
        $ui = new WCML_Setup_Attributes_UI( $this->woocommerce_wpml, $this->next_step_url() );
        echo $ui->get_view();
    }

    public function setup_multi_currency(){
        $ui = new WCML_Setup_Multi_Currency_UI( $this->woocommerce_wpml, $this->next_step_url() );
        echo $ui->get_view();
    }

    public function setup_translation_interface(){
        $ui = new WCML_Setup_Translation_Interface_UI( $this->woocommerce_wpml, $this->next_step_url() );
        echo $ui->get_view();
    }

    public function setup_ready(){
        $ui = new WCML_Setup_Ready_UI( $this->woocommerce_wpml );
        echo $ui->get_view();
    }


    public function redirect_filters( $url ){

        if( isset($_POST['next_step_url']) && $_POST['next_step_url'] ){
            $url = sanitize_text_field( $_POST['next_step_url'] );
        }

        return $url;
    }


    private function get_handler( $step ){
        $handler = !empty( $this->steps[ $step ]['handler'] ) ? $this->steps[ $step ]['handler'] : '';
        return $handler;

    }

    public function handle_steps(){

        if( isset( $_POST[ 'handle_step' ] ) && $_POST['nonce'] == wp_create_nonce( $_POST[ 'handle_step' ] ) ){

            $step_name = sanitize_text_field( $_POST[ 'handle_step' ] );

            if( $handler = $this->get_handler( $step_name  )){
                call_user_func( $handler, $_POST );
            }

        }

    }

    /**
     * handler
     */
    public function save_attributes( $data ){

        if ( isset( $data['attributes'] ) ) {
            $this->woocommerce_wpml->attributes->set_translatable_attributes( $data['attributes'] );
        }

    }

    /**
     * handler
     */
    public function save_multi_currency( $data ){

        if( empty( $this->woocommerce_wpml->multi_currency )){
            $this->woocommerce_wpml->multi_currency = new WCML_Multi_Currency();
        }

        if( !empty( $data['enabled'] ) ){
            $this->woocommerce_wpml->multi_currency->enable();
        } else{
            $this->woocommerce_wpml->multi_currency->disable();
        }

    }

    /**
     * handler
     */
    public function save_translation_interface( $data ){

        $this->woocommerce_wpml->settings['trnsl_interface'] = intval( $data['translation_interface'] );
        $this->woocommerce_wpml->update_settings();

    }

    /**
     * handler
     */
    public function install_store_pages( $data ){

        if( !empty( $data['create_pages'] ) ) {
            $this->woocommerce_wpml->store->create_missing_store_pages_with_redirect();
        }elseif( !empty( $data['install_missing_pages'] ) ){
            WC_Install::create_pages();
            $this->woocommerce_wpml->store->create_missing_store_pages_with_redirect();
        }

    }



}