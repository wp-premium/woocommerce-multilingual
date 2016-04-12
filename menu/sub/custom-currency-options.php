<?php 
    $currency_name =  $wc_currencies[$code];
    $currency_symbol = get_woocommerce_currency_symbol($code);
?>
<table id="wcml_currency_options_<?php echo $code ?>" class="wcml_currency_options_popup">
    <tr>
        <td>
            <h4><?php printf(__('Currency options for %s', 'woocommerce-multilingual'), '<strong>' . $currency_name . ' (' . $currency_symbol . ')</strong>') ?></h4>
            <hr />
            <table>
            
                <tr>
                    <td align="right"><?php _e('Exchange Rate', 'woocommerce-multilingual') ?></td>
                    <td>
                        <?php printf("1 %s = %s %s", $wc_currency, '<input name="currency_options[' . $code . '][rate]" type="number" class="ext_rate" step="0.01" value="' . $currency['rate'] .  '" data-message="'. __( 'Only numeric', 'woocommerce-multilingual' ) .'" />', $code) ?>
                    </td>        
                </tr>
                <tr>   
                    <td>&nbsp;</td>
                    <td><small><i><?php printf(__('Set on %s', 'woocommerce-multilingual'), date('F j, Y, H:i', strtotime(isset($currency['updated'])? $currency['updated']: time()))); ?></i></small></td>
                </tr>
            
                <tr>    
                    <td colspan="2"><hr /></td>
                </tr>
                <tr>
                    <td align="right"><?php _e('Currency Position', 'woocommerce-multilingual') ?></td>
                        <td>
                                <select name="currency_options[<?php echo $code ?>][position]">
                                    <option value="left" <?php selected('left', $currency['position'], 1); ?>><?php 
                                        echo $post_str['left'] = sprintf(__('Left (%s99.99)', 'woocommerce-multilingual'),
                                        $currency_symbol); ?></option>
                                    <option value="right" <?php selected('right', $currency['position'], 1); ?>><?php 
                                        echo $post_str['right'] = sprintf(__('Right (99.99%s)', 'woocommerce-multilingual'),
                                        $currency_symbol); ?></option>
                                    <option value="left_space" <?php selected('left_space', $currency['position'], 1); ?>><?php 
                                        echo $post_str['left_space'] = sprintf(__('Left with space (%s 99.99)', 'woocommerce-multilingual'),
                                        $currency_symbol); ?></option>
                                    <option value="right_space" <?php selected('right_space', $currency['position'], 1); ?>><?php 
                                        echo $post_str['right_space'] = sprintf(__('Right with space (99.99 %s)', 'woocommerce-multilingual'),
                                        $currency_symbol); ?></option>
                                </select>
                        </td>
                </tr>
                <tr>
                    <td align="right"><?php _e('Thousand Separator', 'woocommerce-multilingual') ?></td>
                    <td><input name="currency_options[<?php echo $code ?>][thousand_sep]" type="text" class="currency_option_input" value="<?php echo esc_attr($currency['thousand_sep']) ?>" /></td>
                </tr>
                <tr>
                    <td align="right"><?php _e('Decimal Separator', 'woocommerce-multilingual') ?></td>
                    <td><input name="currency_options[<?php echo $code ?>][decimal_sep]" type="text" class="currency_option_input" value="<?php echo esc_attr($currency['decimal_sep']) ?>" /></td>
                </tr>
                <tr>
                    <td align="right"><?php _e('Number of Decimals', 'woocommerce-multilingual') ?></td>
                    <td><input name="currency_options[<?php echo $code ?>][num_decimals]" type="number" class="decimals_number" value="<?php echo esc_attr($currency['num_decimals']) ?>" min="0" step="1" data-message="<?php _e( 'Only numeric', 'woocommerce-multilingual' ); ?>" /></td>
                </tr>  
                
                <tr>    
                    <td colspan="2"><hr /></td>
                </tr>
                <tr>
                    <td align="right"><?php _e('Rounding to the nearest integer', 'woocommerce-multilingual') ?></td>
                    <td>    
                        <select name="currency_options[<?php echo $code ?>][rounding]">
                            <option value="disabled" <?php selected('disabled', $currency['rounding']) ?> ><?php _e('disabled', 'woocommerce-multilingual') ?></option>
                            <option value="up" <?php selected('up', $currency['rounding']) ?>><?php _e('up', 'woocommerce-multilingual') ?></option>
                            <option value="down" <?php selected('down', $currency['rounding']) ?>><?php _e('down', 'woocommerce-multilingual') ?></option>
                            <option value="down" <?php selected('nearest', $currency['rounding']) ?>><?php _e('nearest', 'woocommerce-multilingual') ?></option>
                        </select>
                    </td>
                </tr>  
                <tr>
                    <td align="right"><?php _e('Increment for nearest integer', 'woocommerce-multilingual') ?></td>
                    <td>    
                        <select name="currency_options[<?php echo $code ?>][rounding_increment]">
                            <option value="1" <?php selected('1', $currency['rounding_increment']) ?> >1</option>
                            <option value="10" <?php selected('10', $currency['rounding_increment']) ?>>10</option>
                            <option value="100" <?php selected('100', $currency['rounding_increment']) ?>>100</option>
                            <option value="1000" <?php selected('1000', $currency['rounding_increment']) ?>>1000</option>
                        </select>
                    </td>
                </tr>                  
                <tr>
                    <td align="right"><?php _e('Autosubtract amount', 'woocommerce-multilingual') ?></td>
                    <td>   
                        <input name="currency_options[<?php echo $code ?>][auto_subtract]" class="abstract_amount" value="<?php echo $currency['auto_subtract'] ?>" type="number" value="0" data-message="<?php _e( 'Only numeric', 'woocommerce-multilingual' ); ?>" />
                    </td>
                </tr>                  
            </table>            
            
        </td>
    </tr>
    
    
    <tr>
        <td colspan="2" align="right">
            <input type="button" class="button-secondary currency_options_cancel" value="<?php esc_attr_e('Cancel', 'woocommerce-multilingual') ?>" data-currency="<?php echo $code ?>" />&nbsp;
            <input type="submit" class="button-primary currency_options_save" value="<?php esc_attr_e('Save', 'woocommerce-multilingual') ?>" data-currency="<?php echo $code ?>" />
            <input type="hidden" id="save_currency_nonce" value="<?php echo wp_create_nonce('save_currency'); ?>" />
            <br /><br />
        </td>
    </tr>
</table>
