jQuery(document).ready(function(){
    jQuery(document).on('click', '#wcml_translations_message', function( e ){
        e.preventDefault();
        jQuery.ajax({
            type : "post",
            url : ajaxurl,
            data : {
                action: "hide_wcml_translations_message",
                wcml_nonce: jQuery('#wcml_hide_languages_notice').val()
            },
            success: function(response) {
                jQuery('#wcml_translations_message').remove();
            }
        });
    });

});