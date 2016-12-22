<?php

class WCML_Pointers{

	public function __construct(){
		add_action( 'admin_head', array( $this, 'setup') );
	}

	public function setup(){
		$current_screen = get_current_screen();

		if( empty($current_screen) ) {
			return;
		}

		$tab     = isset( $_GET['tab'] ) ? $_GET['tab'] : '';
		$section = isset( $_GET['section'] ) ? $_GET['section'] : '';
		wp_register_style( 'wcml-pointers', WCML_PLUGIN_URL . '/res/css/wcml-pointers.css' );

		if( $current_screen->id == 'edit-product' ){
			add_action( 'admin_footer', array( $this, 'add_products_translation_link' ), 100 );
		}elseif( $current_screen->id == 'woocommerce_page_wc-settings' && $tab == 'shipping' && $section == 'classes' ){
			add_action( 'admin_footer', array( $this, 'add_shipping_classes_translation_link' ) );
		}elseif( $current_screen->id == 'woocommerce_page_wc-settings' && ( $tab == 'general' || empty($tab) ) ){
			add_filter( 'woocommerce_general_settings', array( $this, 'add_multi_currency_link' ) );
		}elseif( $current_screen->id == 'woocommerce_page_wc-settings' && $tab == 'account'){
			add_filter( 'woocommerce_account_settings', array( $this, 'add_endpoints_translation_link' ) );
		}

	}

	public function add_products_translation_link(){
		$link = admin_url('admin.php?page=wpml-wcml');
		$name = __('Translate WooCommerce products', 'woocommerce-multilingual');
		wp_enqueue_style( 'wcml-pointers');
		?>
		<script type="text/javascript">
			jQuery(".subsubsub").append('<a class="button button-small button-wpml wcml-pointer-products_translation" href="<?php echo $link ?>"><?php echo $name ?></a>');
		</script>
		<?php
	}

	public function add_shipping_classes_translation_link(){
		$link = admin_url('admin.php?page=wpml-wcml&tab=product_shipping_class');
		$name = __('Translate shipping classes', 'woocommerce-multilingual');
		wp_enqueue_style( 'wcml-pointers');
		?>
		<script type="text/javascript">
			jQuery(".wc-shipping-classes").before('<a class="button button-small button-wpml wcml-pointer-shipping_classes_translation" href="<?php echo $link ?>"><?php echo $name ?></a>');
		</script>
		<?php
	}

	public function add_multi_currency_link( $settings ){
		$link = admin_url('admin.php?page=wpml-wcml&tab=multi-currency');
		$name = __('Configure multi-currency for multilingual sites', 'woocommerce-multilingual');
		wp_enqueue_style( 'wcml-pointers');
		foreach( $settings as $key => $value ){
			if( $value['id'] == 'pricing_options' && isset( $value['desc'] ) ){

				$settings[$key]['desc'] = '<a class="button button-small button-wpml wcml-pointer-multi_currency" href="' . $link . '">' . $name .'</a><br />' . $value['desc'];
			}
		}

		return $settings;
	}

	public function add_endpoints_translation_link( $settings ){
		$link = admin_url('admin.php?page=wpml-wcml&tab=slugs');
		$name = __('Translate endpoints', 'woocommerce-multilingual');
		wp_enqueue_style( 'wcml-pointers');
		foreach( $settings as $key => $value ){
			if( $value['id'] == 'account_endpoint_options' && isset( $value['desc'] ) ){

				$settings[$key]['desc'] = '<a class="button button-small button-wpml wcml-pointer-endpoints_translation" href="' . $link . '">' . $name .'</a><br />' . $value['desc'];
			}
		}

		return $settings;
	}

}