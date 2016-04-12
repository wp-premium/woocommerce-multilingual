<?php

$checked = !isset($custom_prices['_wcml_custom_prices_status']) || (isset($custom_prices['_wcml_custom_prices_status']) && $custom_prices['_wcml_custom_prices_status'][0] == 0)?'checked="checked"':' ';

if($is_variation){
    $html_id = '['.$post_id.']'; ?>
    <tr><td>
<?php }else{
    $html_id = '';
}

$currencies = $woocommerce_wpml->multi_currency_support->get_currencies();
?>
<div class="wcml_custom_prices_block">
    <?php if(empty($currencies)): ?>
        <div class="custom_prices_message_block">
            <label><?php _e('Multi-currency is enabled but no secondary currencies have been set', 'woocommerce-multilingual')?></label>
        </div>
    <?php else: ?>
    <div class="wcml_custom_prices_options_block">

        <input type="radio" name="_wcml_custom_prices[<?php echo $post_id; ?>]" id="wcml_custom_prices_auto[<?php echo $post_id ?>]" value="0" class="wcml_custom_prices_input"<?php echo $checked ?> />
        <label for="wcml_custom_prices_auto[<?php echo $post_id ?>]"><?php _e('Calculate prices in other currencies automatically', 'woocommerce-multilingual')?>&nbsp;
            <span class="block_actions">(
                <a href="javascript:void(0);" class="wcml_custom_prices_auto_block_show" title="<?php _e('Click to see the prices in the other currencies as they are currently shown onthe front end.', 'woocommerce-multilingual') ?>"><?php _e('Show', 'woocommerce-multilingual') ?></a>
                <a href="javascript:void(0);" class="wcml_custom_prices_auto_block_hide"><?php _e('Hide', 'woocommerce-multilingual') ?></a>
            )</span>
        </label>

        <?php $checked = isset($custom_prices['_wcml_custom_prices_status']) && $custom_prices['_wcml_custom_prices_status'][0] == 1?'checked="checked"':' '; ?>

       <input type="radio" name="_wcml_custom_prices[<?php echo $post_id ?>]" value="1" id="wcml_custom_prices_manually[<?php echo $post_id ?>]" class="wcml_custom_prices_input" <?php echo $checked ?> />
       <label for="wcml_custom_prices_manually[<?php echo $post_id ?>]"><?php _e('Set prices in other currencies manually', 'woocommerce-multilingual') ?></label>
       <div class="wcml_custom_prices_manually_block_control">
           <a <?php if(!trim($checked)): ?>style="display:none"<?php endif ?> href="javascript:void(0);" class="wcml_custom_prices_manually_block_show">&raquo; <?php _e('Enter prices in other currencies', 'woocommerce-multilingual') ?></a>
           <a style="display:none" href="javascript:void(0);" class="wcml_custom_prices_manually_block_hide">- <?php _e('Hide prices in other currencies', 'woocommerce-multilingual') ?></a>
       </div>
    </div>

    <div class="wcml_custom_prices_manually_block">

        <?php 
        $wc_currencies = get_woocommerce_currencies();

        foreach($currencies as $currency_code => $currency){

            $regular_price = '';
            $sale_price = '';

            if(isset($custom_prices['_wcml_custom_prices_status'])){

                if(isset($custom_prices['_regular_price_'.$currency_code][0])){
                    $regular_price = $custom_prices['_regular_price_'.$currency_code][0];
                }

                if(isset($custom_prices['_sale_price_'.$currency_code][0])){
                    $sale_price    = $custom_prices['_sale_price_'.$currency_code][0];
                }
            } ?>
            <div class="currency_blck">
                <label>
                    <?php echo $wc_currencies[$currency_code].sprintf(__(' (%s)', 'woocommerce-multilingual'),get_woocommerce_currency_symbol($currency_code)) ?>
                </label>

                <?php if($regular_price == ''): ?>
                    <span class="wcml_no_price_message"><?php _e('Determined automatically based on exchange rate', 'woocommerce-multilingual'); ?></span>
                <?php endif; ?>

                <?php if($is_variation){
                    $custom_id = '['.$currency_code.']['.$post_id.']';
                    $wc_input_type = 'text';
                    ?>
                    <p>
                        <label><?php echo __( 'Regular Price', 'woocommerce-multilingual' ) . ' ('.get_woocommerce_currency_symbol($currency_code).')' ?></label>
                        <input type="<?php echo $wc_input_type; ?>" size="5" name="_custom_variation_regular_price<?php echo $custom_id ?>" class="wc_input_price wcml_input_price short wcml_regular_price" value="<?php echo $regular_price ?>" step="any" min="0" />
                    </p>

                    <p>
                        <label><?php echo __( 'Sale Price', 'woocommerce-multilingual' ) . ' ('.get_woocommerce_currency_symbol($currency_code).')' ?></label>
                        <input type="<?php echo $wc_input_type; ?>" size="5" name="_custom_variation_sale_price<?php echo $custom_id ?>" class="wc_input_price wcml_input_price short wcml_sale_price" value="<?php echo $sale_price ?>" step="any" min="0" />
                    </p>
                <?php }else{
                    $custom_id = '['.$currency_code.']';

                    $wc_input = array();

                    $wc_input['custom_attributes'] = array() ;
                    $wc_input['type_name'] = 'data_type';
                    $wc_input['type_val'] = 'price';

                    woocommerce_wp_text_input( array( 'id' => '_custom_regular_price'.$custom_id, 'value'=>$regular_price ,'class' => 'wc_input_price wcml_input_price short wcml_regular_price', 'label' => __( 'Regular Price', 'woocommerce-multilingual' ) . ' ('.get_woocommerce_currency_symbol($currency_code).')', $wc_input['type_name'] => $wc_input['type_val'], 'custom_attributes' => $wc_input['custom_attributes'] ) );

                    woocommerce_wp_text_input( array( 'id' => '_custom_sale_price'.$custom_id, 'value'=>$sale_price , 'class' => 'wc_input_price wcml_input_price short wcml_sale_price', 'label' => __( 'Sale Price', 'woocommerce-multilingual' ) . ' ('.get_woocommerce_currency_symbol($currency_code).')', $wc_input['type_name'] => $wc_input['type_val'], 'custom_attributes' => $wc_input['custom_attributes'] ) );
                    } ?>
                <div class="wcml_schedule">
                    <label><?php _e('Schedule', 'woocommerce-multilingual') ?></label>
                    <div class="wcml_schedule_options">
                        <?php $checked = (!isset($custom_prices['_wcml_schedule_'.$currency_code]) || (isset($custom_prices['_wcml_schedule_'.$currency_code]) && $custom_prices['_wcml_schedule_'.$currency_code][0] == 0))?'checked="checked"':' '; ?>

                        <input type="radio" name="_wcml_schedule[<?php echo $currency_code.']'.$html_id; ?>" id="wcml_schedule_auto[<?php echo $currency_code.']'.$html_id ?>" value="0" class="wcml_schedule_input"<?php echo $checked ?> />
                        <label for="wcml_schedule_auto[<?php echo $currency_code.']'.$html_id ?>"><?php _e('Same as default currency', 'woocommerce-multilingual')?></label>

                        <?php $checked = isset($custom_prices['_wcml_schedule_'.$currency_code]) && $custom_prices['_wcml_schedule_'.$currency_code][0] == 1?'checked="checked"':' '; ?>

                        <input type="radio" name="_wcml_schedule[<?php echo $currency_code.']'.$html_id ?>" value="1" id="wcml_schedule_manually[<?php echo $currency_code.']'.$html_id ?>" class="wcml_schedule_input" <?php echo $checked ?> />
                        <label for="wcml_schedule_manually[<?php echo $currency_code.']'.$html_id ?>"><?php _e('Set dates', 'woocommerce-multilingual') ?>
                            <span class="block_actions">(
                                <a href="javascript:void(0);" class="wcml_schedule_manually_block_show"><?php _e('Schedule', 'woocommerce-multilingual') ?></a>
                                <a href="javascript:void(0);" class="wcml_schedule_manually_block_hide"><?php _e('Collapse', 'woocommerce-multilingual') ?></a>
                            )</span>
                        </label>

                        <div class="wcml_schedule_dates">
                            <?php
                            $sale_price_dates_from 	= (isset($custom_prices['_sale_price_dates_from_'.$currency_code]) && $custom_prices['_sale_price_dates_from_'.$currency_code][0] != '') ? date_i18n( 'Y-m-d', $custom_prices['_sale_price_dates_from_'.$currency_code][0] ) : '';
                            $sale_price_dates_to 	= (isset($custom_prices['_sale_price_dates_to_'.$currency_code])  && $custom_prices['_sale_price_dates_to_'.$currency_code][0] != '') ? date_i18n( 'Y-m-d', $custom_prices['_sale_price_dates_to_'.$currency_code][0] ) : '';
                            ?>
                            <input type="text" class="short custom_sale_price_dates_from" name="_custom_sale_price_dates_from<?php echo $custom_id; ?>" id="_custom_sale_price_dates_from<?php echo $custom_id; ?>" value="<?php echo esc_attr( $sale_price_dates_from ) ?>" placeholder="<?php echo _x( 'From&hellip;', 'placeholder', 'woocommerce-multilingual' ) ?> YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
                            <input type="text" class="short custom_sale_price_dates_to" name="_custom_sale_price_dates_to<?php echo $custom_id; ?>" id="_custom_sale_price_dates_to<?php echo $custom_id; ?>" value="<?php echo esc_attr( $sale_price_dates_to ) ?>" placeholder="<?php echo _x( 'To&hellip;', 'placeholder', 'woocommerce-multilingual' ) ?>  YYYY-MM-DD" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />
                        </div>

                    </div>
                </div>

            </div>
        <?php } ?>


    </div>

    <div class="wcml_automaticaly_prices_block">

        <?php
        foreach($currencies as $currency){

            $regular_price = '';
            $sale_price = '';

            if($post_id){

                $regular_price = get_post_meta($post_id,'_regular_price',true);
                if($regular_price){
                    $regular_price = $regular_price*$currency['rate'];
                }

                $sale_price = get_post_meta($post_id,'_sale_price',true);
                if($sale_price){
                    $sale_price    = $sale_price*$currency['rate'];
                }
            } ?>

            <label><?php echo $wc_currencies[$currency_code].sprintf(__(' (%s)', 'woocommerce-multilingual'),get_woocommerce_currency_symbol($currency_code)) ?></label>

            <?php
                if($is_variation){ ?>
                    <p>
                        <label><?php echo __( 'Regular Price', 'woocommerce-multilingual' ) . ' ('.get_woocommerce_currency_symbol($currency_code).')' ?></label>
                        <input type="number" size="5" name="_readonly_regular_price" class="wc_input_price short" value="<?php echo $regular_price ?>" step="any" min="0" readonly = "readonly" rel="<?php echo $currency['rate'] ?>" />
                    </p>

                <p>
                    <label><?php echo __( 'Sale Price', 'woocommerce-multilingual' ) . ' ('.get_woocommerce_currency_symbol($currency_code).')' ?></label>
                    <input type="number" size="5" name="_readonly_sale_price" class="wc_input_price short" value="<?php echo $sale_price ?>" step="any" min="0" readonly = "readonly" rel="<?php echo $currency['rate'] ?>" />
                </p>
                <?php

                }else{

                    $wc_input['custom_attributes'] = array( 'readonly' => 'readonly', 'rel'=> $currency['rate'] ) ;

                    woocommerce_wp_text_input( array( 'id' => '_readonly_regular_price', 'value'=>$regular_price, 'class' => 'wc_input_price short', 'label' => __( 'Regular Price', 'woocommerce-multilingual' ) . ' ('.get_woocommerce_currency_symbol($currency_code).')', $wc_input['type_name'] => $wc_input['type_val'], 'custom_attributes' => $wc_input['custom_attributes'] ) );

                    woocommerce_wp_text_input( array( 'id' => '_readonly_sale_price', 'value'=>$sale_price, 'class' => 'wc_input_price short', 'label' => __( 'Sale Price', 'woocommerce-multilingual' ) . ' ('.get_woocommerce_currency_symbol($currency_code).')', $wc_input['type_name'] => $wc_input['type_val'], 'custom_attributes' => $wc_input['custom_attributes'] ) );
                }

        } ?>

    </div>
    <?php endif; ?>
    <?php if(!$is_variation): ?>
        <div class="wcml_price_error"><?php  _e( 'Please enter in a value less than the regular price', 'woocommerce-multilingual' ) ?></div>
    <?php endif; ?>
</div>
<?php if($is_variation): ?>
    </td></tr>
<?php endif; ?>