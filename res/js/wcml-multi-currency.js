jQuery(document).ready(function(){

    jQuery(document).on( 'click', '.wcml_currency_switcher a', function( event ){
        event.preventDefault();
        if( jQuery(this).is(':disabled') || jQuery(this).parent().hasClass('wcml-cs-active-currency') || jQuery(this).hasClass('wcml-cs-active-currency')){
            return false;
        }else{
            jQuery( this ).off( event );
        }

        wcml_load_currency( jQuery(this).attr('rel') );
    });

    if( typeof woocommerce_price_slider_params != 'undefined' ){
        woocommerce_price_slider_params.currency_symbol = wcml_mc_settings.current_currency.symbol;
    }
});

function wcml_load_currency( currency, force_switch ){
    var ajax_loader = jQuery('<img style=\"margin-left:10px;\" width=\"16\" heigth=\"16\" src=\"' + wcml_mc_settings.wcml_spinner +'\" />')
    jQuery('.wcml_currency_switcher').after();
    ajax_loader.insertAfter(jQuery('.wcml_currency_switcher'));

    if ( typeof force_switch === 'undefined') force_switch = 0;

    jQuery.ajax({
        type : 'post',
        url : woocommerce_params.ajax_url,
        dataType: "json",
        data : {
            action: 'wcml_switch_currency',
            currency : currency,
            force_switch: force_switch,
            wcml_nonce: wcml_mc_settings.wcml_mc_nonce
        },
        success: function(response) {
            if(typeof response.error !== 'undefined') {
                alert(response.error);
            }else if( typeof response.prevent_switching !== 'undefined' ){
                jQuery('body').append( response.prevent_switching );
            }else{
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
