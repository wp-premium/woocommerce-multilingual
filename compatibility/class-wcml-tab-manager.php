<?php

class WCML_Tab_Manager{

    public $tp;

    function __construct(){
        add_action( 'wcml_update_extra_fields', array( $this, 'sync_tabs' ), 10, 4 );
        add_action( 'wcml_gui_additional_box_html', array( $this, 'custom_box_html'), 10, 3 );
        add_filter( 'wcml_gui_additional_box_data', array( $this, 'custom_box_html_data'), 10, 4 );
        add_filter( 'wpml_duplicate_custom_fields_exceptions', array( $this, 'duplicate_custom_fields_exceptions' ) );
        add_action( 'wcml_after_duplicate_product', array( $this, 'duplicate_product_tabs') , 10, 2 );
		
		add_filter('wc_tab_manager_tab_id', array($this, 'wc_tab_manager_tab_id'), 10, 1);

        if( version_compare( WCML_VERSION, '3.7.2', '>') ){
            add_filter( 'option_wpml_config_files_arr', array($this, 'make__product_tabs_not_translatable_by_default'), 0 );
        }

        if( is_admin() ){

            $this->tp = new WPML_Element_Translation_Package;

            add_action( 'save_post', array($this, 'force_set_language_information_on_product_tabs'), 10, 2);

            add_filter( 'wpml_tm_translation_job_data', array( $this, 'append_custom_tabs_to_translation_package' ), 10, 2 );
            add_action( 'wpml_translation_job_saved',   array( $this, 'save_custom_tabs_translation' ), 10, 3 );

        }

    }

    function make__product_tabs_not_translatable_by_default($wpml_config_array){

        if( isset( $wpml_config_array->plugins['WooCommerce Tab Manager'] ) ){
            $wpml_config_array->plugins['WooCommerce Tab Manager'] =
                str_replace('<custom-field action="translate">_product_tabs</custom-field>',
                            '<custom-field action="nothing">_product_tabs</custom-field>',
                            $wpml_config_array->plugins['WooCommerce Tab Manager'] );
        }

        return $wpml_config_array;

    }

    function sync_tabs( $original_product_id, $trnsl_product_id, $data, $lang ){
        global $sitepress, $woocommerce, $woocommerce_wpml;

        //check if "duplicate" product
        if( ( isset( $_POST['icl_ajx_action'] ) && ( $_POST['icl_ajx_action'] == 'make_duplicates' ) ) || ( get_post_meta( $trnsl_product_id , '_icl_lang_duplicate_of', true ) ) ){
            $this->duplicate_tabs( $original_product_id, $trnsl_product_id, $lang );
        }

        $orig_prod_tabs = $this->get_product_tabs( $original_product_id );

        if( $orig_prod_tabs ){
            $trnsl_product_tabs = array();
            $i = 0;
            foreach( $orig_prod_tabs as $key => $orig_prod_tab ){
                switch( $orig_prod_tab[ 'type' ] ){
                    case 'core':
                        $default_language = $woocommerce_wpml->products->get_original_product_language( $original_product_id );
                        $current_language = $sitepress->get_current_language();
                        $trnsl_product_tabs[ $key ] = $orig_prod_tabs[ $key ];
                        $title = '';
                        $heading = '';

                        $title = $data[ md5( 'coretab_'.$orig_prod_tab['id'].'_title' ) ] ? $data[ md5( 'coretab_'.$orig_prod_tab['id'].'_title' ) ] : '';
                        $heading = $data[ md5( 'coretab_'.$orig_prod_tab['id'].'_heading' ) ] ? $data[ md5( 'coretab_'.$orig_prod_tab['id'].'_heading' ) ] : '';


                        if( $default_language != $lang ){

                            $this->refresh_text_domain( $lang );

                            if( !$title ){
                                $title = isset( $_POST['product_tab_title'][ $orig_prod_tab['position'] ] ) ?  $_POST['product_tab_title'][ $orig_prod_tab['position'] ] : $orig_prod_tabs[ $key ][ 'title' ];
                                $title = __( $title, 'woocommerce' );
                            }

                            if( !$heading && ( isset( $orig_prod_tabs[ $key ][ 'heading' ] ) || isset( $_POST['product_tab_heading'][ $orig_prod_tab['position'] ] ) ) ){
                                $heading = isset( $_POST['product_tab_heading'][ $orig_prod_tab['position'] ] ) ?  $_POST['product_tab_heading'][ $orig_prod_tab['position'] ] : $orig_prod_tabs[ $key ][ 'heading' ];
                                $heading = __( $heading, 'woocommerce' );
                            }

                            $this->refresh_text_domain( $current_language );
                        }

                        $trnsl_product_tabs[ $key ][ 'title' ] = $title;
                        $trnsl_product_tabs[ $key ][ 'heading' ] = $heading;
                        break;
                    case 'global':
                        $trnsl_product_tabs = $this->set_global_tab( $orig_prod_tab, $trnsl_product_tabs, $lang );
                        break;
                    case 'product':
                        $tab_id = false;

                        $title = $data[ md5( 'tab_'.$orig_prod_tab['position'].'_title' ) ];
                        $content = $data[ md5( 'tab_'.$orig_prod_tab['position'].'_heading' ) ];

                        $trnsl_product_tabs = $this->set_product_tab( $orig_prod_tab, $trnsl_product_tabs, $lang, $trnsl_product_id, $tab_id, $title, $content );

                        $i++;
                        break;
                }
            }
            update_post_meta( $trnsl_product_id, '_product_tabs', $trnsl_product_tabs );
        }
    }

    function duplicate_tabs( $original_product_id, $trnsl_product_id, $lang ){
        global $sitepress;
        $orig_prod_tabs = maybe_unserialize( get_post_meta( $original_product_id, '_product_tabs', true ) );
        $prod_tabs = array();
        foreach( $orig_prod_tabs as $key => $orig_prod_tab ){
            switch( $orig_prod_tab[ 'type' ] ){
                case 'core':
                    $prod_tabs[ $key ] = $orig_prod_tab;
                    $this->refresh_text_domain( $lang );
                    $prod_tabs[ $key ][ 'title' ] = __( $orig_prod_tab[ 'title' ], 'woocommerce' );
                    if( isset( $orig_prod_tab[ 'heading' ] ) )
                        $prod_tabs[ $key ][ 'heading' ] = __( $orig_prod_tab[ 'heading' ], 'woocommerce' );
                    $orig_lang = $sitepress->get_language_for_element( $original_product_id, 'post_product' );
                    $this->refresh_text_domain( $orig_lang );
                break;
                case 'global':
                    $prod_tabs = $this->set_global_tab( $orig_prod_tab, $prod_tabs, $lang );
                break;
                case 'product':
                    $original_tab = get_post( $orig_prod_tab[ 'id' ] );
                    $prod_tabs = $this->set_product_tab( $orig_prod_tab, $prod_tabs, $lang, $trnsl_product_id, false, $original_tab->post_title , $original_tab->post_content );
                break;
            }
        }

        update_post_meta( $trnsl_product_id, '_product_tabs', $prod_tabs );
    }

    function refresh_text_domain( $lang ){
        global $sitepress, $woocommerce;

        unload_textdomain( 'woocommerce' );
        $sitepress->switch_lang( $lang );
        $woocommerce->load_plugin_textdomain();
    }

    function set_global_tab( $orig_prod_tab, $trnsl_product_tabs, $lang ){
        $tr_tab_id = apply_filters( 'translate_object_id', $orig_prod_tab[ 'id' ], 'wc_product_tab', true, $lang );
        $trnsl_product_tabs[ $orig_prod_tab[ 'type' ].'_tab_'.$tr_tab_id ] = array(
            'position' => $orig_prod_tab[ 'position' ],
            'type'     => $orig_prod_tab[ 'type' ],
            'id'       => $tr_tab_id,
            'name'     => get_post( $tr_tab_id )->post_name
        );
        return $trnsl_product_tabs;
    }

    function set_product_tab( $orig_prod_tab, $trnsl_product_tabs, $lang, $trnsl_product_id, $tab_id, $title, $content ){
        global $wpdb, $sitepress;

        if( !$tab_id ){
            $tr_tab_id = apply_filters( 'translate_object_id', $orig_prod_tab[ 'id' ], 'wc_product_tab', false, $lang );

            if( !is_null( $tr_tab_id ) ){
                $tab_id = $tr_tab_id;
            }
        }

        if( $tab_id ){
            //update existing tab
            $args = array();
            $args[ 'post_title' ] = $title;
            $args[ 'post_content' ] = $content;
            $wpdb->update( $wpdb->posts, $args, array( 'ID' => $tab_id ) );
        }else{
            //tab not exist creating new
            $args = array();
            $args[ 'post_title' ] = $title;
            $args[ 'post_content' ] = $content;
            $args[ 'post_author' ] = get_current_user_id();
            $args[ 'post_name' ] = sanitize_title( $title );
            $args[ 'post_type' ] = 'wc_product_tab';
            $args[ 'post_parent' ] = $trnsl_product_id;
            $args[ 'post_status' ] = 'publish';
            $wpdb->insert( $wpdb->posts, $args );

            $tab_id = $wpdb->insert_id;
            $tab_trid = $sitepress->get_element_trid( $orig_prod_tab[ 'id' ], 'post_wc_product_tab' );
            if( !$tab_trid ){
                $sitepress->set_element_language_details( $orig_prod_tab[ 'id' ], 'post_wc_product_tab', false, $sitepress->get_default_language() );
                $tab_trid = $sitepress->get_element_trid( $orig_prod_tab[ 'id' ], 'post_wc_product_tab' );
            }
            $sitepress->set_element_language_details( $tab_id, 'post_wc_product_tab', $tab_trid, $lang );
        }

        $trnsl_product_tabs[ $orig_prod_tab[ 'type' ].'_tab_'.$tab_id ] = array(
            'position' => $orig_prod_tab[ 'position' ],
            'type'     => $orig_prod_tab[ 'type' ],
            'id'       => $tab_id,
            'name'     => get_post( $tab_id )->post_name
        );

        return $trnsl_product_tabs;
    }

    function duplicate_custom_fields_exceptions( $exceptions ){
        $exceptions[] = '_product_tabs';
        return $exceptions;
    }

    function custom_box_html( $obj, $product_id, $data ){

        if( get_post_meta( $product_id, '_override_tab_layout', true ) != 'yes' ){
            return false;
        }

        $orig_prod_tabs = $this->get_product_tabs( $product_id );
        if( !$orig_prod_tabs ) return false;

        $tabs_section = new WPML_Editor_UI_Field_Section( __( 'Product tabs', 'woocommerce-multilingual' ) );
        end( $orig_prod_tabs );
        $last_key = key( $orig_prod_tabs );
        $divider = true;
        foreach( $orig_prod_tabs as $key => $prod_tab ) {
            if( $key ==  $last_key ){
                $divider = false;
            }

            if( in_array( $prod_tab['type'], array( 'product', 'core' ) ) ){
                if( $prod_tab['type'] == 'core' ){
                    $group = new WPML_Editor_UI_Field_Group( $prod_tab[ 'title' ], $divider );
                    $tab_field = new WPML_Editor_UI_Single_Line_Field( 'coretab_'.$prod_tab['id'].'_title', __( 'Title', 'woocommerce-multilingual' ), $data, false );
                    $group->add_field( $tab_field );
                    $tab_field = new WPML_Editor_UI_Single_Line_Field( 'coretab_'.$prod_tab['id'].'_heading' , __( 'Heading', 'woocommerce-multilingual' ), $data, false );
                    $group->add_field( $tab_field );
                    $tabs_section->add_field( $group );
                }else{
                    $group = new WPML_Editor_UI_Field_Group( ucfirst( str_replace( '-', ' ', $prod_tab[ 'name' ] ) ), $divider );
                    $tab_field = new WPML_Editor_UI_Single_Line_Field( 'tab_'.$prod_tab['position'].'_title', __( 'Title', 'woocommerce-multilingual' ), $data, false );
                    $group->add_field( $tab_field );
                    $tab_field = new WPML_Editor_UI_WYSIWYG_Field( 'tab_'.$prod_tab['position'].'_heading' , null, $data, false );
                    $group->add_field( $tab_field );
                    $tabs_section->add_field( $group );
                }
            }
        }
        $obj->add_field( $tabs_section );

    }


    function custom_box_html_data( $data, $product_id, $translation, $lang ){

        $orig_prod_tabs = $this->get_product_tabs( $product_id );

        if( empty($orig_prod_tabs) ){
            return $data;
        }

        foreach( $orig_prod_tabs as $key => $prod_tab ){
            if( in_array( $prod_tab['type'], array( 'product', 'core' ) ) ){
                if( $prod_tab['type'] == 'core' ){
                    $data[ 'coretab_'.$prod_tab['id'].'_title' ] = array( 'original' => $prod_tab['title'] );
                    $data[ 'coretab_'.$prod_tab['id'].'_heading' ] = array( 'original' => isset ( $prod_tab['heading'] ) ? $prod_tab['heading'] : '' );
                }else{
                    $data[ 'tab_'.$prod_tab['position'].'_title' ] = array( 'original' => get_the_title( $prod_tab['id'] ) );
                    $data[ 'tab_'.$prod_tab['position'].'_heading' ] = array( 'original' => get_post( $prod_tab['id'] )->post_content );
                }
            }
        }

        if( $translation ){
            $tr_product_id = $translation->ID;

            $tr_prod_tabs = $this->get_product_tabs( $translation->ID );

            if( !is_array( $tr_prod_tabs ) ){
                return $data; // __('Please update original product','woocommerce-multilingual');
            }

            foreach( $tr_prod_tabs as $key => $prod_tab ){
                if( in_array( $prod_tab['type'], array( 'product','core' ) ) ){
                    if($prod_tab['type'] == 'core'){
                        $data[ 'coretab_'.$prod_tab['id'].'_title' ][ 'translation' ] = $prod_tab['title'];
                        $data[ 'coretab_'.$prod_tab['id'].'_heading' ][ 'translation' ] = isset ( $prod_tab['heading'] ) ? $prod_tab['heading'] : '';
                    }else{
                        $data[ 'tab_'.$prod_tab['position'].'_title' ][ 'translation' ] = get_the_title( $prod_tab['id'] );
                        $data[ 'tab_'.$prod_tab['position'].'_heading' ][ 'translation' ] = get_post( $prod_tab['id'] )->post_content;
                    }
                }
            }
        }else{
            global $sitepress,$woocommerce;
            $current_language = $sitepress->get_current_language();
            foreach($orig_prod_tabs as $key=>$prod_tab){
                if($prod_tab['type'] == 'core'){
                    unload_textdomain('woocommerce');
                    $sitepress->switch_lang($lang);
                    $woocommerce->load_plugin_textdomain();
                    $title = __( $prod_tab['title'], 'woocommerce' );
                    if($prod_tab['title'] != $title){
                        $data[ 'coretab_'.$prod_tab['id'].'_title' ][ 'translation' ] = $title;

                    }

                    if(!isset($prod_tab['heading'])){
                        $data[ 'coretab_'.$prod_tab['id'].'_heading' ][ 'translation' ] = '';
                    }else{
                        $heading = __( $prod_tab['heading'], 'woocommerce' );
                        if($prod_tab['heading'] != $heading){
                            $data[ 'coretab_'.$prod_tab['id'].'_heading' ][ 'translation' ] = $heading;
                        }
                    }

                    unload_textdomain('woocommerce');
                    $sitepress->switch_lang($current_language);
                    $woocommerce->load_plugin_textdomain();
                }
            }
        }

        return $data;

    }

    function duplicate_product_tabs( $new_id, $original_post ){

        wc_tab_manager_duplicate_product( $new_id, $original_post );

    }

    function force_set_language_information_on_product_tabs($post_id, $post){
        global $sitepress;

        if( $post->post_type == 'wc_product_tab' ){

            $language = $sitepress->get_language_for_element($post->ID, 'post_wc_product_tab');
            if( empty ($language) && $post->post_parent ) {
                $parent_language = $sitepress->get_language_for_element($post->post_parent, 'post_product');
                if( $parent_language ){
                    $sitepress->set_element_language_details($post->ID, 'post_wc_product_tab', null, $parent_language);
                }
            }

        }

    }

    function append_custom_tabs_to_translation_package($package, $post){

        if( $post->post_type == 'product' ) {

            $override_tab_layout = get_post_meta( $post->ID , '_override_tab_layout', true);

            if( $override_tab_layout == 'yes' ){

                $meta = get_post_meta( $post->ID, '_product_tabs', true );

                foreach ( (array)$meta as $key => $value ) {

                    if ( preg_match( '/product_tab_([0-9]+)/', $key, $matches ) ) {

                        $wc_product_tab_id = $matches[1];
                        $wc_product_tab = get_post( $wc_product_tab_id );

                        $package['contents']['product_tabs:product_tab:' . $wc_product_tab_id . ':title'] = array(
                            'translate' => 1,
                            'data' => $this->tp->encode_field_data( $wc_product_tab->post_title, 'base64' ),
                            'format' => 'base64'
                        );

                        $package['contents']['product_tabs:product_tab:' . $wc_product_tab_id . ':description'] = array(
                            'translate' => 1,
                            'data' => $this->tp->encode_field_data( $wc_product_tab->post_content, 'base64' ),
                            'format' => 'base64'
                        );


                    } elseif ( preg_match( '/^core_tab_(.+)$/', $key, $matches ) ){

                        $package['contents']['product_tabs:core_tab_title:' . $matches[1]] = array(
                            'translate' => 1,
                            'data' => $this->tp->encode_field_data( $value['title'], 'base64' ),
                            'format' => 'base64'
                        );

                        if(isset( $value['heading'] )) {
                            $package['contents']['product_tabs:core_tab_heading:' . $matches[1]] = array(
                                'translate' => 1,
                                'data' => $this->tp->encode_field_data( $value['heading'], 'base64' ),
                                'format' => 'base64'
                            );
                        }


                    }

                }
            }


        }

        return $package;
    }

    function save_custom_tabs_translation( $post_id, $data, $job ){
        global $sitepress;


        $translated_product_tabs_updated    = false;

        $original_product_tabs = get_post_meta($job->original_doc_id, '_product_tabs', true);

        if( $original_product_tabs ) {

            // custom tabs
            $product_tab_translations  = array();

            foreach ( $data as $value ) {

                if ( preg_match( '/product_tabs:product_tab:([0-9]+):(.+)/', $value['field_type'], $matches ) ) {

                    $wc_product_tab_id = $matches[1];
                    $field = $matches[2];

                    $product_tab_translations[$wc_product_tab_id][$field] = $value['data'];
                }

            }

            if ( $product_tab_translations ) {

                $translated_product_tabs = get_post_meta( $post_id, '_product_tabs', true );

                foreach ( $product_tab_translations as $wc_product_tab_id => $value ) {

                    $new_wc_product_tab = array(
                        'post_type' => 'wp_product_tab',
                        'post_title' => $value['title'],
                        'post_content' => $value['description'],
                        'post_status' => 'publish'
                    );

                    $wc_product_tab_id_translated = wp_insert_post( $new_wc_product_tab );

                    if ( $wc_product_tab_id_translated ) {

                        $wc_product_tab_trid = $sitepress->get_element_trid( $wc_product_tab_id, 'post_wc_product_tab' );
                        $sitepress->set_element_language_details( $wc_product_tab_id_translated, 'post_wc_product_tab', $wc_product_tab_trid, $job->language_code );

                        $wc_product_tab_translated = get_post( $wc_product_tab_id_translated );

                        $translated_product_tabs['product_tab_' . $wc_product_tab_id_translated] = array(

                            'position' => $original_product_tabs['product_tab_' . $wc_product_tab_id]['position'],
                            'type' => 'product',
                            'id' => $wc_product_tab_id_translated,
                            'name' => $wc_product_tab_translated->post_name

                        );

                    }

                }


                $translated_product_tabs_updated = true;
            }

            // the other tabs
            $product_tab_translations  = array();

            foreach ( $data as $value ) {

                if ( preg_match( '/product_tabs:core_tab_(.+):(.+)/', $value['field_type'], $matches ) ) {

                    $tab_field  = $matches[1];
                    $tab_id     = $matches[2];

                    $product_tab_translations[$tab_id][$tab_field] = $value['data'];

                }

            }

            if( $product_tab_translations){
                foreach( $product_tab_translations as $id => $tab ){

                    $translated_product_tabs['core_tab_' . $id] = array(
                        'type'      => 'core',
                        'position'  => $original_product_tabs['core_tab_' . $id]['position'],
                        'id'        => $id,
                        'title'     => $tab['title']
                    );

                    if( isset( $tab['heading'] ) ){
                        $translated_product_tabs['core_tab_' . $id]['heading'] = $tab['heading'];
                    }

                }

                $translated_product_tabs_updated = true;
            }

            if ( $translated_product_tabs_updated ) {
                update_post_meta( $post_id, '_product_tabs', $translated_product_tabs );
            }

        }
    }

    public function get_product_tabs( $product_id ) {

        $override_tab_layout = get_post_meta( $product_id, '_override_tab_layout', true );

        if ( 'yes' == $override_tab_layout ) {
            // product defines its own tab layout?
            $product_tabs = get_post_meta( $product_id, '_product_tabs', true );
        } else {
            // otherwise, get the default layout if any
            $product_tabs = get_option( 'wc_tab_manager_default_layout', false );
        }

        return $product_tabs;
    }
	
	
	function wc_tab_manager_tab_id($tab_id) {
		$tab_id = apply_filters('wpml_object_id', $tab_id, 'wc_product_tab', true);
		
		return $tab_id;
	}

}
