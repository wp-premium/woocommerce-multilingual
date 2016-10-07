jQuery(document).ready(function(){
    //troubleshooting page
    jQuery('#wcml_trbl').on('click',function(){
        var field = jQuery(this);
        field.attr('disabled', 'disabled');
        jQuery('.spinner').css('display','inline-block').css('visibility','visible');

        if(jQuery('#wcml_sync_update_product_count').is(':checked')){
            update_product_count();
        }else if(jQuery('#wcml_sync_product_variations').is(':checked')){
            sync_variations();
        }else if(jQuery('#wcml_sync_gallery_images').is(':checked')){
            sync_product_gallery();
        }else if(jQuery('#wcml_sync_categories').is(':checked')){
            sync_product_categories();
        }else if(jQuery('#wcml_duplicate_terms').is(':checked')){
            duplicate_terms();
        }
    });

    jQuery('#attr_to_duplicate').on('change',function(){
        jQuery('.attr_status').html(jQuery(this).find('option:selected').attr('rel'))
        jQuery('#count_terms').val(jQuery(this).find('option:selected').attr('rel'))
    });


});

function update_product_count(){
    jQuery.ajax({
        type : "post",
        url : ajaxurl,
        data : {
            action: "trbl_update_count",
            wcml_nonce: jQuery('#trbl_update_count_nonce').val()
        },
        success: function(response) {
            jQuery('.var_status').each(function(){
                jQuery(this).html(response);
            })
            jQuery('#count_prod_variat').val(response);
            if(jQuery('#wcml_sync_product_variations').is(':checked')){
                sync_variations();
            }else if(jQuery('#wcml_sync_gallery_images').is(':checked')){
                sync_product_gallery();
            }else if(jQuery('#wcml_sync_categories').is(':checked')){
                sync_product_categories();
            }else if(jQuery('#wcml_duplicate_terms').is(':checked')){
                duplicate_terms();
            }else{
                jQuery('#wcml_trbl').removeAttr('disabled');
                jQuery('.spinner').hide();
                jQuery('#wcml_trbl').next().fadeOut();
            }
        }
    });
}

function sync_variations(){
    jQuery.ajax({
        type : "post",
        url : ajaxurl,
        data : {
            action: "trbl_sync_variations",
            wcml_nonce: jQuery('#trbl_sync_variations_nonce').val()
        },
        success: function(response) {
            if(jQuery('#count_prod_variat').val() == 0){
                jQuery('.var_status').each(function(){
                    jQuery(this).html(0);
                });
                if(jQuery('#wcml_sync_gallery_images').is(':checked')){
                    sync_product_gallery();
                }else if(jQuery('#wcml_sync_categories').is(':checked')){
                    sync_product_categories();
                }else if(jQuery('#wcml_duplicate_terms').is(':checked')){
                    duplicate_terms();
                }else{
                    jQuery('#wcml_trbl').removeAttr('disabled');
                    jQuery('.spinner').hide();
                    jQuery('#wcml_trbl').next().fadeOut();
                }

            }else{
                var left = jQuery('#count_prod_variat').val()-3;
                if(left < 0 ){
                    left = 0;
                }
                jQuery('.var_status').each(function(){
                    jQuery(this).html(left);
                });
                jQuery('#count_prod_variat').val(left);
                sync_variations();
            }
        }
    });
}

function sync_product_gallery(){
    jQuery.ajax({
        type : "post",
        url : ajaxurl,
        data : {
            action: "trbl_gallery_images",
            wcml_nonce: jQuery('#trbl_gallery_images_nonce').val(),
            page: jQuery('#sync_galerry_page').val()
        },
        success: function(response) {
            if(jQuery('#count_prod').val() == 0){
                if(jQuery('#wcml_sync_categories').is(':checked')){
                    sync_product_categories();
                }else if(jQuery('#wcml_duplicate_terms').is(':checked')){
                    duplicate_terms();
                }else{
                    jQuery('#wcml_trbl').removeAttr('disabled');
                    jQuery('.spinner').hide();
                    jQuery('#wcml_trbl').next().fadeOut();
                }
                jQuery('.gallery_status').html(0);
            }else{
                var left = jQuery('#count_prod').val()-5;
                if(left < 0 ){
                    left = 0;
                }else{
                    jQuery('#sync_galerry_page').val(parseInt(jQuery('#sync_galerry_page').val())+1)
                }
                jQuery('.gallery_status').html(left);
                jQuery('#count_prod').val(left);
                sync_product_gallery();
            }
        }
    });
}

function sync_product_categories(){
    jQuery.ajax({
        type : "post",
        url : ajaxurl,
        data : {
            action: "trbl_sync_categories",
            wcml_nonce: jQuery('#trbl_sync_categories_nonce').val(),
            page: jQuery('#sync_category_page').val()
        },
        success: function(response) {
            if(jQuery('#count_categories').val() == 0){
                if(jQuery('#wcml_duplicate_terms').is(':checked')){
                    duplicate_terms();
                }else{
                    jQuery('#wcml_trbl').removeAttr('disabled');
                    jQuery('.spinner').hide();
                    jQuery('#wcml_trbl').next().fadeOut();
                }
                jQuery('.cat_status').html(0);
            }else{
                var left = jQuery('#count_categories').val()-5;
                if(left < 0 ){
                    left = 0;
                }else{
                    jQuery('#sync_category_page').val(parseInt(jQuery('#sync_category_page').val())+1)
                }
                jQuery('.cat_status').html(left);
                jQuery('#count_categories').val(left);
                sync_product_categories();
            }
        }
    });
}

function duplicate_terms(){
    jQuery.ajax({
        type : "post",
        url : ajaxurl,
        data : {
            action: "trbl_duplicate_terms",
            wcml_nonce: jQuery('#trbl_duplicate_terms_nonce').val(),
            attr: jQuery('#attr_to_duplicate option:selected').val()
        },
        success: function(response) {
            if(jQuery('#count_terms').val() == 0){
                jQuery('#wcml_trbl').removeAttr('disabled');
                jQuery('.spinner').hide();
                jQuery('#wcml_trbl').next().fadeOut();
                jQuery('.attr_status').html(0);
            }else{
                var left = jQuery('#count_terms').val()-5;
                if(left < 0 ){
                    left = 0;
                }
                jQuery('.attr_status').html(left);
                jQuery('#count_terms').val(left);

                duplicate_terms();
            }
        }
    });
}