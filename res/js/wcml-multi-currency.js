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

    if( typeof woocommerce_price_slider_params !== 'undefined' ){
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
            force_switch: force_switch
        },
        success: function(response) {
            if(typeof response.error !== 'undefined') {
                alert(response.error);
            }else if( typeof response.prevent_switching !== 'undefined' ){
                jQuery('body').append( response.prevent_switching );
            }else{

                var target_location = window.location.href;
                if(-1 !== target_location.indexOf('#') || wcml_mc_settings.w3tc ){

                    var url_dehash = target_location.split('#');
                    var hash = url_dehash.length > 1 ? '#' + url_dehash[1] : '';

                    target_location = url_dehash[0]
                                    .replace(/&wcmlc(\=[^&]*)?(?=&|$)|wcmlc(\=[^&]*)?(&|$)/, '')
                                    .replace(/\?$/, '');

                    var url_glue = target_location.indexOf('?') != -1 ? '&' : '?';
                    target_location += url_glue + 'wcmlc=' + currency + hash;

                }
                window.location = target_location;

            }
        }
    });
}
