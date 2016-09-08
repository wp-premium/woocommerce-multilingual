jQuery(document).ready(function(){

    jQuery('.wcml_currency_switcher').on('change', function(){
        wcml_load_currency( jQuery(this).val() );
    });
    jQuery('.wcml_currency_switcher li').on('click', function(){
        if(jQuery(this).hasClass('wcml-active-currency')){
            return;
        }
        wcml_load_currency( jQuery(this).attr('rel') );
    });

    if( typeof woocommerce_price_slider_params != 'undefined' ){
        woocommerce_price_slider_params.currency_symbol = wcml_mc_settings.current_currency.symbol;
    }
});


function wcml_load_currency( currency ){
    var ajax_loader = jQuery('<img style=\"margin-left:10px;\" width=\"16\" heigth=\"16\" src=\"' + wcml_mc_settings.wcml_spinner +'\" />')
    jQuery('.wcml_currency_switcher').attr('disabled', 'disabled');
    jQuery('.wcml_currency_switcher').after();
    ajax_loader.insertAfter(jQuery('.wcml_currency_switcher'));
    jQuery.ajax({
        type : 'post',
        url : woocommerce_params.ajax_url,
        data : {
            action: 'wcml_switch_currency',
            currency : currency,
            wcml_nonce: wcml_mc_settings.wcml_mc_nonce
        },
        success: function(response) {
            if(typeof response.error !== 'undefined'){
                alert(response.error);
            }else{
                jQuery('.wcml_currency_switcher').removeAttr('disabled');
                if(typeof wcml_mc_settings.w3tc !== 'undefined'){
                    var original_url = window.location.href;
                    original_url = original_url.replace(/&wcmlc(\=[^&]*)?(?=&|$)|wcmlc(\=[^&]*)?(&|$)/, '');
                    original_url = original_url.replace(/\?$/, '');

                    var url_glue = original_url.indexOf('?') != -1 ? '&' : '?';
                    var target_location = original_url + url_glue + 'wcmlc=' + currency;

                }else{
                    var target_location = window.location.href;
                }


                window.location = target_location;
            }
        }
    });
}
