jQuery(document).ready(function($){

    var discard = false;

    window.onbeforeunload = function(e) {
        if(discard){
            return $('#wcml_warn_message').val();
        }
    }

    $('.wcml-section input[type="submit"]').click(function(){
        discard = false;
    });

    $('.wcml-section input[type="radio"],#wcml_products_sync_date,#wcml_products_sync_order,#display_custom_prices').click(function(){
        discard = true;
        $(this).closest('.wcml-section').find('.button-wrap input').css("border-color","#1e8cbe");
    });

   $('#wcmp_hide').click(function(){
       $('.wcml_miss_lang').slideUp('3000',function(){$('#wcmp_show').show();});
   });

   $('#wcmp_show').click(function(){
       $('#wcmp_show').hide();
       $('.wcml_miss_lang').slideDown('3000');
   });

   $('.wcml_check_all').click(function(){
      if($(this).is(":checked")){
          $("table.wcml_products input[type='checkbox']").each(function(){
             $(this).attr("checked","checked");
          });
      }else{
          $("table.wcml_products input[type='checkbox']").each(function(){
             $(this).removeAttr("checked");
          });
      }
   });

   $('.wcml_search').click(function(){
       window.location = $('.wcml_products_admin_url').val()+'&s='+$('.wcml_product_name').val()+'&cat='+$('.wcml_product_category').val()+'&trst='+$('.wcml_translation_status').val()+'&st='+$('.wcml_product_status').val()+'&slang='+$('.wcml_translation_status_lang').val();
   });

   $('.wcml_reset_search').click(function(){
       window.location = $('.wcml_products_admin_url').val();
   });
   
    $('.wcml_pagin').keypress(function(e) {
        if(e.which == 13) {
            window.location = $('.wcml_pagination_url').val()+$(this).val();
            return false;
        }
    });
   
   $('.wcml_details').click(function(e){
        e.preventDefault();
        var element = $(this),
        textClosed = element.data('text-closed'),
        textOpened = element.data('text-opened'),
        $table = $(element.attr('href')),
        parent = element.closest('tr');

        if ( $table.is(':visible') ){
            $table.find('input').each(function(){
                element.val(element.data('def'));
            });
            $table.closest('.outer').hide();
            element.text(textClosed);
        }else {
            if($table.size() > 0){
            //set def data
            $table.find('input').each(function(){
                    element.data('def',element.val());
            });
            $table.closest('.outer').show();
                element.text(textOpened);
            }else{
                //load product data
                var id = $(this).attr('href').replace(/#prid_/, '');
                var job_id = $(this).attr('job_id');
                element.parent().find('.spinner').show();
                $.ajax({
                    type : "post",
                    url : ajaxurl,
                    dataType: 'json',
                    data : {
                        action: "wcml_product_data",
                        product_id : id,
                        job_id : job_id,
                        wcml_nonce: $('#get_product_data_nonce').val()
                    },
                    success: function(response) {
                        if(typeof response.error !== "undefined"){
                            alert(response.error);
                        }else{
                            //update status block
                            $(response.html).insertAfter(parent).css('display','table-row');

                            //set def data
                            $table.find('input').each(function(){
                                element.data('def',element.val());
                            });
                            element.text(textOpened);
                            element.parent().find('.spinner').hide();
                            wpLink.init();
                        }
                    }
                });
            }

        }
        return false;
   });

    $(document).on('click', 'button[name="cancel"]', function(){
       var $outer = $(this).closest('.outer');

       $outer.find('input').each(function(){
           $(this).val($(this).data('def'));
       });

       var prid = $outer.data('prid');
       $outer.hide('fast', function(){
            var $closeButton = $('#wcml_details_' + prid);
            $closeButton.text( $closeButton.data('text-closed') );
       });

       $(this).parent().find('input').each(function(){
           $(this).val($(this).data('def'));
       });
       $(this).closest('.outer').slideUp('3000');

   });

    $(document).on('click','.wcml_update', function() {
      var field = $(this);

      var spl = $(this).attr('name').split('#');

      var product_id = spl[1];
      var language   = spl[2];

      var records = '';
       field.closest('tr').find("input").each(function(){
          records += $(this).serialize()+"&";
      });
       field.closest('tr').find("textarea").each(function(){
           records += $(this).serialize()+"&";
       });
       field.hide();
       field.parent().find('.wcml_spinner').css('display','inline-block');

      $.ajax({
         type : "post",
         url : ajaxurl,
         dataType: 'json',
         data : {
             action: "wcml_update_product",
             product_id : product_id,
             language   : language,
             job_id     : field.closest('tr').find('input[name="job_id"]').val(),
             records    : records,
             slang      : $('.wcml_translation_status_lang').val(),
             wcml_nonce: $('#upd_product_nonce').val()
         },
         success: function(response) {
             if(typeof response.error !== "undefined"){
                 alert(response.error);
             }else{
             //update status block
             $('.translations_statuses.prid_'+product_id).html(response.status);

             //update slug
             if( field.closest('.outer').find('.edit_slug_warning').size() >0 ){
                 field.closest('.outer').find('input[name="post_name_'+language+'"]').removeAttr('disabled').removeClass('hidden');
                 field.closest('.outer').find('.edit_slug_show_link').removeClass('hidden');
                 field.closest('.outer').find('.edit_slug_hide_link').removeClass('hidden');
                 field.closest('.outer').find('.edit_slug_warning').remove();
             }
             field.closest('.outer').find('input[name="post_name_'+language+'"]').val(response.slug);

             //update images block
             if(language in response.images){
                 var value = response.images[language];
                 field.closest('.outer').find('tr[rel="'+language+'"] .prod_images').closest('td').html(value).find('.prod_images').css('display','none');
                 }

                 //update variations block

                 if(typeof response.variations !== "undefined" && (language in response.variations)){
                 var value = response.variations[language];
                 field.closest('.outer').find('tr[rel="'+language+'"] .prod_variations').closest('td').html(value).find('.prod_variations').css('display','none');
                 }

                 //set def data
                 field.closest('.outer').find('input').each(function(){
                     $(this).data('def',$(this).val());
                 });



                 field.val($('#wcml_product_update_button_label').html());

             }
             field.parent().find('.wcml_spinner').hide();
             field.prop('disabled', true).removeClass('button-primary').addClass('button-secondary');
             field.show();
             
             $('#prid_' + product_id + ' .js-wcml_duplicate_product_undo_' + language).fadeOut();
             
         }
      });

      return false;
   });
   
   if(typeof WPML_Translate_taxonomy != 'undefined' && typeof WPML_Translate_taxonomy.callbacks != 'undefined'){
       
       WPML_Translate_taxonomy.callbacks.add(function(func, taxonomy){
          
          if($('.js-tax-tab-' + taxonomy + ' i.icon-warning-sign').length){
              
              $.ajax({
                 type : "post",
                 url : ajaxurl,
                 dataType: 'json',
                 data : {
                     action: "wcml_update_term_translated_warnings",
                     taxonomy: taxonomy, 
                     wcml_nonce: $('#wcml_update_term_translated_warnings_nonce').val()
                 },
                 success: function(response) {
                     if(response.hide){
                        $('.js-tax-tab-' + taxonomy).removeAttr('title');
                        $('.js-tax-tab-' + taxonomy + ' i.icon-warning-sign').remove();
                     }
                 }
              })       
              
          }
          
          return false;
           
       });
   }
   
   $(document).on('click', '.wcml_duplicate_product_notice a[href^="#edit-"]', function(){
       
       var spl = $(this).attr('href').replace(/#edit-/, '').split('_');
       var pid = spl[0];
       var lng = spl[1];
       
       $('#prid_' + pid + ' tr[rel=' + lng + '] .js-dup-disabled').removeAttr('disabled');
       $('#prid_' + pid + ' tr[rel=' + lng + '] input[name^=end_duplication]').val(1);
       $('#prid_' + pid + ' .js-wcml_duplicate_product_notice_'+lng).hide();
       $('#prid_' + pid + ' .js-wcml_duplicate_product_undo_'+lng).show();
       
       return false;
       
   });

   $(document).on('click', '.wcml_duplicate_product_notice a[href^="#undo-"]', function(){
       
       var spl = $(this).attr('href').replace(/#undo-/, '').split('_');
       var pid = spl[0];
       var lng = spl[1];
       
       $('#prid_' + pid + ' tr[rel=' + lng + '] .js-dup-disabled').attr('disabled', 'disabled');
       $('#prid_' + pid + ' tr[rel=' + lng + '] input[name^="end_duplication"]').val(0);
       $('#prid_' + pid + ' .js-wcml_duplicate_product_undo_'+lng).hide();
       $('#prid_' + pid + ' .js-wcml_duplicate_product_notice_'+lng).show();
       
       return false;
       
   });
   
   $(document).on('click', '.js-tax-translation li a[href^="#ignore-"]', function(){
                
       var taxonomy = $(this).attr('href').replace(/#ignore-/, '');
                
       $.ajax({
           type : "post",
           url : ajaxurl,
           dataType: 'json',
           data : {
               action: "wcml_ingore_taxonomy_translation",
               taxonomy: taxonomy, 
               wcml_nonce: $('#wcml_ingore_taxonomy_translation_nonce').val()
           },
           success: function(response) {
               
               if(response.html){
                   
                   $('.js-tax-translation li.js-tax-translation-' + taxonomy).html(response.html);
                   
                   $('.js-tax-tab-' + taxonomy).removeAttr('title');
                   $('.js-tax-tab-' + taxonomy + ' i.icon-warning-sign').remove();
                   
                   
               }
               
           }
       })       

       return false;
   })
   
   $(document).on('click', '.js-tax-translation li a[href^="#unignore-"]', function(){
                
       var taxonomy = $(this).attr('href').replace(/#unignore-/, '');
                
       $.ajax({
           type : "post",
           url : ajaxurl,
           dataType: 'json',
           data : {
               action: "wcml_uningore_taxonomy_translation",
               taxonomy: taxonomy, 
               wcml_nonce: $('#wcml_ingore_taxonomy_translation_nonce').val()
           },
           success: function(response) {
               if(response.html){
                   $('.js-tax-translation li.js-tax-translation-' + taxonomy).html(response.html);
                   if(response.warn){
                        $('.js-tax-tab-' + taxonomy).append('&nbsp;<i class="icon-warning-sign"></i>');
                   }
                   
               }
           }
       })       

       return false;
   })
   
   
   $(document).on('submit', '#wcml_tt_sync_variations', function(){

       var this_form = $('#wcml_tt_sync_variations');
       var data = this_form.serialize();
       this_form.find('.wpml_tt_spinner').fadeIn();
       this_form.find('input[type=submit]').attr('disabled', 'disabled');
       
       $.ajax({
           type : "post",
           url : ajaxurl,
           dataType: 'json',
           data : data,
           success: function(response) {
               this_form.find('.wcml_tt_sycn_preview').html(response.progress);
               if(response.go){                   
                   this_form.find('input[name=last_post_id]').val(response.last_post_id);
                   this_form.find('input[name=languages_processed]').val(response.languages_processed);
                   this_form.trigger('submit');
               }else{
                   this_form.find('input[name=last_post_id]').val(0);
                   this_form.find('.wpml_tt_spinner').fadeOut();
                   this_form.find('input').removeAttr('disabled');               
               }
               
           }
       });
       
       return false;       
       
       
   });


    $(document).on('submit', '#wcml_tt_sync_assignment', function(){

        var this_form = $('#wcml_tt_sync_assignment');;
        var parameters = this_form.serialize();

        this_form.find('.wpml_tt_spinner').fadeIn();
        this_form.find('input').attr('disabled', 'disabled');

        $('.wcml_tt_sync_row').remove();

        $.ajax({
            type:       "POST",
            dataType:   'json',
            url:        ajaxurl,
            data:       'action=wcml_tt_sync_taxonomies_in_content_preview&wcml_nonce='+$('#wcml_sync_taxonomies_in_content_preview_nonce').val()+'&' + parameters,
            success:
                function(ret){

                    this_form.find('.wpml_tt_spinner').fadeOut();
                    this_form.find('input').removeAttr('disabled');

                    if(ret.errors){
                        this_form.find('.errors').html(ret.errors);
                    }else{
                        jQuery('#wcml_tt_sync_preview').html(ret.html);
                    }

                }

        });

        return false;


    });

    $(document).on('click', 'form.wcml_tt_do_sync a.submit', function(){

        var this_form = $('form.wcml_tt_do_sync');
        var parameters = this_form.serialize();

        this_form.find('.wpml_tt_spinner').fadeIn();
        this_form.find('input').attr('disabled', 'disabled');

        jQuery.ajax({
            type:       "POST",
            dataType:   'json',
            url:        ajaxurl,
            data:       'action=wcml_tt_sync_taxonomies_in_content&wcml_nonce='+$('#wcml_sync_taxonomies_in_content_nonce').val()+'&' + parameters,
            success:
                function(ret){

                    this_form.find('.wpml_tt_spinner').fadeOut();
                    this_form.find('input').removeAttr('disabled');

                    if(ret.errors){
                        this_form.find('.errors').html(ret.errors);
                    }else{
                        this_form.closest('.wcml_tt_sync_row').html(ret.html);
                    }

                }

        });

        return false;


    });

   var wcml_product_rows_data = new Array();
   var wcml_get_product_fields_string = function(row){
       var string = '';
       row.find('input[type=text], textarea').each(function(){
           string += $(this).val();
       });       
       
       return string;
   }

   $(document).on('focus','.wcml_products_translation input[type=text], .wcml_products_translation textarea',function(){

       var row_lang = $(this).closest('tr[rel]').attr('rel');
       var prod_id  = $(this).closest('div.wcml_product_row').attr('id');
       
       wcml_product_rows_data[prod_id + '_' + row_lang] = wcml_get_product_fields_string($(this).closest('tr'));

   });

   $(document).on('input keyup change paste mouseup','.wcml_products_translation input[type=text], .wcml_products_translation textarea',function(){
       
       if($(this).attr('disabled')) return;
        
       var row_lang = $(this).closest('tr[rel]').attr('rel');
       var prod_id  = $(this).closest('div.wcml_product_row').attr('id');
       
       if($(this).closest('tr[rel]').find('.wcml_update').prop('disabled')){       
           
           if(wcml_product_rows_data[prod_id + '_' + row_lang] != wcml_get_product_fields_string($(this).closest('tr'))){
               $(this).closest('tr[rel]').find('.wcml_update').prop('disabled',false).removeClass('button-secondary').addClass('button-primary');;
           }
           
       }

   })

    $(document).on('click','.wcml_edit_content',function(){
        $(".wcml_fade").show();
        $(this).parent().find('.wcml_editor').show();

        var txt_height = '90%';
        $(this).parent().find('.wcml_original_content').cleditor({
                    height: txt_height,
                    controls:     // controls to add to the toolbar
                    " | source "
                    });
        $(this).parent().find('.wcml_original_content').cleditor()[0].disable(true);

        if( !$(this).hasClass('origin_content') ){
            $(this).parent().find('textarea.wcml_content_tr').data('def',$(this).parent().find('textarea.wcml_content_tr').val());
            $(this).parent().find('.wcml_editor table.mceLayout').css('height','auto');
            $(this).parent().find('.wcml_editor table.mceLayout iframe').css('min-height','150px');
            var id = $(this).parent().find('.switch-tmce').attr('id').replace(/-tmce/, '');
            $(this).parent().find('.wp-editor-wrap').removeClass('html-active').addClass('tmce-active');

            if(  window.parent.tinyMCE.get(id)  == null ){
                tinymce.execCommand( 'mceAddEditor', false, id);
            }

            $(this).parent().find('.wp-editor-wrap').find('.mce-tinymce').show();
            $(this).parent().find('textarea.wcml_content_tr').hide();
        }

    });

    $(document).on('click','.cleditorButton',function(){
        if($(this).closest('.cleditorMain').find('textarea').is(':visible')){
            $(this).closest('.cleditorMain').find('textarea').hide();
            $(this).closest('.cleditorMain').find('iframe').show();
        }else{
            $(this).closest('.cleditorMain').find('textarea').show();
            $(this).closest('.cleditorMain').find('iframe').hide();
        }
    });

    $(document).on('click','.wcml_close_cross,.wcml_popup_cancel',function(){
        $(".wcml_fade").hide();
        if(tinyMCE.activeEditor != null){
            if($(this).closest('.wcml_editor').find('.wcml_editor_translation textarea').size() >0){
                tinyMCE.activeEditor.setContent($(this).closest('.wcml_editor').find('.wcml_editor_translation textarea').data('def'));
            }
        }
        $(this).closest('.wcml_editor').css('display','none');
        $(this).closest('.wcml_editor').find('.wcml_editor_translation textarea').val($(this).closest('.wcml_editor').find('.wcml_editor_translation textarea').data('def'));
    });

    $(document).on('click','.switch-tmce',function(){
        var id = $(this).attr('id').replace(/-tmce/, '');
        $(this).closest('.wp-editor-wrap').removeClass('html-active').addClass('tmce-active');
        $(this).closest('.wp-editor-wrap').find('textarea.wcml_content_tr').hide();
        if(  window.parent.tinyMCE.get(id)  == null ){
            tinymce.execCommand( 'mceAddEditor', false, id);
        }else{
            $(this).closest('.wp-editor-wrap').find('.mce-tinymce').show();
        }

        window.parent.tinyMCE.get(id).setContent( $(this).closest('.wp-editor-wrap').find('textarea.wcml_content_tr').val() );

    });

    $(document).on('click','.switch-html',function(){
        var id = $(this).attr('id').replace(/-html/, '');
        $(this).closest('.wp-editor-wrap').removeClass('tmce-active').addClass('html-active');
        $('#qt_'+id+'_toolbar').remove();
        QTags(id);
        QTags._buttonsInit();
        $(this).closest('.wp-editor-wrap').find('.mce-tinymce').hide();

        if(  window.parent.tinyMCE.get(id)  != null ){
            $(this).closest('.wp-editor-wrap').find('textarea.wcml_content_tr').val( window.parent.tinyMCE.get(id).getContent() );
        }

        $(this).closest('.wp-editor-wrap').find('textarea.wcml_content_tr').show();
    });

    $(document).on('click','.wcml_popup_close',function(){
        $(".wcml_fade").hide();
        $(this).closest('.wcml_editor').css('display','none');
    });


    $(document).on('click','.wcml_popup_ok',function(){
        var text_area = $(this).closest('.wcml_editor').find('.wcml_editor_translation textarea');
        $(".wcml_fade").hide();

        if(text_area.size()>0 && !text_area.is(':visible')){
            text_area.val(window.parent.tinyMCE.get(text_area.attr('id')).getContent());
        }
        $(this).closest('.wcml_editor').css('display','none');
        window.parent.tinyMCE.get(text_area.attr('id')).setContent( text_area.val() );

        var row_lang = $(this).closest('tr[rel]').attr('rel');
        var prod_id  = $(this).closest('div.wcml_product_row').attr('id');

        if(text_area.val() != ''){
            $(this).closest('tr').find('.wcml_field_translation_' + text_area.attr('name')).hide();
        }else{
            if($(this).closest('tr').find('.wcml_field_translation_' + text_area.attr('name')).length){
                $(this).closest('tr').find('.wcml_field_translation_' + text_area.attr('name')).show();
            }
        }

        if(wcml_product_rows_data[prod_id + '_' + row_lang] != wcml_get_product_fields_string($(this).closest('tr'))){
            $(this).closest('tr[rel]').find('.wcml_update').prop('disabled',false);
        }

    });

    $(document).on('click','.edit_slug_show_link,.edit_slug_hide_link',function(){
        if($(this).closest('div').find('.edit_slug_input').is(':visible')){
            $(this).closest('div').find('.edit_slug_input').hide();
            $(this).closest('div').find('.edit_slug_hide_link').hide();
            $(this).closest('div').find('.edit_slug_show_link').show();
        }else{
            $(this).closest('div').find('.edit_slug_input').show();
            $(this).closest('div').find('.edit_slug_hide_link').show();
            $(this).closest('div').find('.edit_slug_show_link').hide();
        }
    });


    //wc 2.0.*
    if($('.wcml_file_paths').size()>0){
        // Uploading files
        var downloadable_file_frame;
        var file_path_field;
        var file_paths;

        $(document).on( 'click', '.wcml_file_paths', function( event ){

            var $el = $(this);

            file_path_field = $el.parent().find('textarea');
            file_paths      = file_path_field.val();

            event.preventDefault();

            // If the media frame already exists, reopen it.
            if ( downloadable_file_frame ) {
                downloadable_file_frame.open();
                return;
            }

            var downloadable_file_states = [
                // Main states.
                new wp.media.controller.Library({
                    library:   wp.media.query(),
                    multiple:  true,
                    title:     $el.data('choose'),
                    priority:  20,
                    filterable: 'uploaded'
                })
            ];

            // Create the media frame.
            downloadable_file_frame = wp.media.frames.downloadable_file = wp.media({
                // Set the title of the modal.
                title: $el.data('choose'),
                library: {
                    type: ''
                },
                button: {
                    text: $el.data('update')
                },
                multiple: true,
                states: downloadable_file_states
            });

            // When an image is selected, run a callback.
            downloadable_file_frame.on( 'select', function() {

                var selection = downloadable_file_frame.state().get('selection');

                selection.map( function( attachment ) {

                    attachment = attachment.toJSON();

                    if ( attachment.url )
                        file_paths = file_paths ? file_paths + "\n" + attachment.url : attachment.url

                } );

                file_path_field.val( file_paths );
            });

            // Set post to 0 and set our custom type
            downloadable_file_frame.on( 'ready', function() {
                downloadable_file_frame.uploader.options.uploader.params = {
                    type: 'downloadable_product'
                };
            });

            downloadable_file_frame.on( 'close', function() {
                // TODO: /wp-admin should be a variable. Some plugions, like WP Better Security changes the name of this dir.
                $.removeCookie('_icl_current_language', { path: '/wp-admin' });
            });

            // Finally, open the modal.
            downloadable_file_frame.open();
        });
    }

    //wc 2.1.*
    if($('.wcml_file_paths_button').size()>0){
        // Uploading files
        var downloadable_file_frame;
        var file_path_field;
        var file_paths;

        $(document).on( 'click', '.wcml_file_paths_button', function( event ){

            var $el = $(this);

            file_path_field = $el.parent().find('.wcml_file_paths_file');
            file_paths      = file_path_field.val();

            event.preventDefault();

            // If the media frame already exists, reopen it.
            if ( downloadable_file_frame ) {
                downloadable_file_frame.open();
                return;
            }

            var downloadable_file_states = [
                // Main states.
                new wp.media.controller.Library({
                    library:   wp.media.query(),
                    multiple:  true,
                    title:     $el.data('choose'),
                    priority:  20,
                    filterable: 'uploaded'
                })
            ];

            // Create the media frame.
            downloadable_file_frame = wp.media.frames.downloadable_file = wp.media({
                // Set the title of the modal.
                title: $el.data('choose'),
                library: {
                    type: ''
                },
                button: {
                    text: $el.data('update')
                },
                multiple: true,
                states: downloadable_file_states
            });

            // When an image is selected, run a callback.
            downloadable_file_frame.on( 'select', function() {

                var selection = downloadable_file_frame.state().get('selection');

                selection.map( function( attachment ) {

                    attachment = attachment.toJSON();

                    if ( attachment.url )
                        file_paths = attachment.url

                } );

                file_path_field.val( file_paths );
            });

            // Set post to 0 and set our custom type
            downloadable_file_frame.on( 'ready', function() {
                downloadable_file_frame.uploader.options.uploader.params = {
                    type: 'downloadable_product'
                };
            });

            downloadable_file_frame.on( 'close', function() {
                // TODO: /wp-admin should be a variable. Some plugions, like WP Better Security changes the name of this dir.
                $.removeCookie('_icl_current_language', { path: '/wp-admin' });
            });

            // Finally, open the modal.
            downloadable_file_frame.open();
        });
    }

    if($(".wcml_editor_original").size() > 0 ){
        $(".wcml_editor_original").resizable({
            handles: 'n, s',
            resize: function( event, ui ) {
                $(this).find('.cleditorMain').css('height',$(this).height() - 60)
            },
            start: function(event, ui) {
                $('<div class="ui-resizable-iframeFix" style="background: #FFF;"></div>')
                    .css({
                        width:'100%', height: '100%',
                        position: "absolute", opacity: "0.001", zIndex: 160001
                    })
                    .prependTo(".wcml_editor_original");
            },
            stop: function(event, ui) {
                $('.ui-resizable-iframeFix').remove()
            }
        });
    }

    $('#multi_currency_option_select input[name=multi_currency]').change(function(){
        
        if($(this).attr('id') != 'multi_currency_independent'){
            $('.currencies-table-content').fadeOut();
            $('.wcml_add_currency').fadeOut();
            $('#currency-switcher').fadeOut();
            $('#display_custom_prices_select').fadeOut();
        }else{
            $('.currencies-table-content').fadeIn();
            $('.wcml_add_currency').fadeIn();
            $('#currency-switcher').fadeIn();
            $('#multi-currency-per-language-details').fadeIn();
            $('#display_custom_prices_select').fadeIn();
        }
        
    })
    
    $('#wcml_custom_exchange_rates').submit(function(){
        
        var thisf = $(this);
        
        thisf.find(':submit').parent().prepend(icl_ajxloaderimg + '&nbsp;')
        thisf.find(':submit').prop('disabled', true);
        
        $.ajax({
            
            type: 'post',
            dataType: 'json',
            url: ajaxurl,
            data: thisf.serialize(),
            success: function(){
                thisf.find(':submit').prev().remove();    
                thisf.find(':submit').prop('disabled', false);
            }
            
        })
        
        return false;
    })
    
    function wcml_remove_custom_rates(post_id){
        
        var thisa = $(this);
        
        $.ajax({
            
            type: 'post',
            dataType: 'json',
            url: ajaxurl,
            data: {action: 'wcml_remove_custom_rates', 'post_id': post_id},
            success: function(){
                thisa.parent().parent().parent().fadeOut(function(){ $(this).remove()});
            }
            
        })
        
        return false;
        
    }
    
    $(document).on('click', '#wcml_fix_strings_language', function(){  // TODO: remove after WPML release with support strings in different languages
        
        var thisb = $(this);
        thisb.prop('disabled', true);
        var $ajaxLoader = $('<span>&nbsp;</span>' + icl_ajxloaderimg);
        $ajaxLoader.insertAfter(thisb).show();
        
        $.ajax({
            
            type : "post",
            dataType:'json',
            url : ajaxurl,
            data : {
                action: "wcml_fix_strings_language",
                wcml_nonce: $('#wcml_fix_strings_language_nonce').val()
            },
            error: function(respnse) {
                thisb.prop('disabled', false);
            },
            success: function(response) {
                
                var sucess_1 = response.success_1;
                
                $.ajax({                    
                    type : "post",
                    dataType:'json',
                    url : icl_ajx_url,
                    data : {
                        iclt_st_sw_save: 1,
                        icl_st_sw: {strings_language: 'en'},
                        _wpnonce: response._wpnonce
                    },
                    complete: function(response){
                        $ajaxLoader.remove();
                        thisb.after(sucess_1);
                    }
                });
                

            }
        })
        
    });
          
    /*           
    $(document).on('click','.edit_currency',function(){
        var $tableRow = $(this).closest('tr');
        $tableRow.addClass('edit-mode');
        $tableRow.find('.currency_code .code_val').hide();
        $tableRow.find('.currency_code select').show();
        $tableRow.find('.currency_value span.curr_val').hide();
        $tableRow.find('.currency_value input').show();
        $tableRow.find('.currency_changed').hide();
        $tableRow.find('.edit_currency').hide();
        $tableRow.find('.delete_currency').hide();
        $tableRow.find('.save_currency').show();
        $tableRow.find('.cancel_currency').show();
    });
    */
    
    
    $(document).on('click', '.edit_currency', function(){
        $('.wcml_currency_options_popup').hide();
        
        var popup = $('#wcml_currency_options_' + $(this).data('currency'));
        popup.fadeIn();
        
        var win = $(window);
        var viewport = {
            top : win.scrollTop(),
            left : win.scrollLeft()
        };
        //viewport.right = viewport.left + win.width();
        viewport.bottom = viewport.top + win.height();

        var bounds = popup.offset();
        //bounds.right = bounds.left + popup.outerWidth();
        bounds.bottom = bounds.top + popup.outerHeight();
        
        var incr = 0;
        while(viewport.bottom < bounds.bottom){            
            
            var top = popup.css('top');
            if(top == 'auto'){
                top = parseInt(viewport.bottom) - parseInt(popup.outerHeight()) - 50;
            }else{
                top = parseInt(top) - 50;
            }
            popup.css({'top': top + 'px'});
            
            
            var bounds = popup.offset();
            //bounds.right = bounds.left + popup.outerWidth();
            bounds.bottom = bounds.top + popup.outerHeight();
            
            
            incr++;
            if(incr == 10) break;
            
        }
        
    });
    
    $(document).on('click', '.currency_options_cancel', function(){
        var currency = $(this).data('currency');
        $('#wcml_currency_options_' + currency).fadeOut(function(){
            
            $('#wcml_currency_options_' + currency).css('top', 'auto');
            
        });
        
    });


    $(document).on('click','.cancel_currency',function(){
        var $tableRow = $(this).closest('tr');
        $tableRow.removeClass('edit-mode');
        if($tableRow.find('.currency_current_code').val()){
            $tableRow.find('.currency_code .code_val').show();
            $tableRow.find('.currency_code select').hide();
            $tableRow.find('.currency_value span.curr_val').show();
            $tableRow.find('.currency_value input').hide();
            $tableRow.find('.currency_changed').show();
            $tableRow.find('.edit_currency').show();
            $tableRow.find('.delete_currency').show();
            $tableRow.find('.save_currency').hide();
            $tableRow.find('.cancel_currency').hide();
            $tableRow.find('.wcml-error').remove();
        }else{
            var index = $tableRow[0].rowIndex;
            $('#currency-lang-table tr').eq(index).remove();
            $tableRow.remove();
        }
    });

    $(document).on('change','.currency_code select',function(){
        $(this).parent().find('.curr_val_code').html($(this).val());
    });

    $('.wcml_add_currency button').click(function(){
        discard = true;
        $('.js-table-row-wrapper .curr_val_code').html($('.js-table-row-wrapper select').val());
        var $tableRow = $('.js-table-row-wrapper .js-table-row').clone();
        var $LangTableRow = $('.js-currency_lang_table tr').clone();
        $('#currency-table').find('tr.default_currency').before( $tableRow );
        $('#currency-lang-table').find('tr.default_currency').before( $LangTableRow );
    });

    $(document).on('click','.save_currency',function(e){
        discard = false;
        e.preventDefault();

        var $this = $(this);
        var $ajaxLoader = $('<span class="spinner">');
        var $messageContainer = $('<span class="wcml-error">');

        $this.prop('disabled',true);

        var parent = $(this).closest('tr');

        parent.find('.save_currency').hide();
        parent.find('.cancel_currency').hide();
        $ajaxLoader.insertBefore($this).show();

        $currencyCodeWraper = parent.find('.currency_code');
        $currencyValueWraper = parent.find('.currency_value');

        var currency_code = $currencyCodeWraper.find('select[name=code]').val();
        var currency_value = $currencyValueWraper.find('input').val();
        var flag = false;
        
        if(currency_code == ''){
            if(parent.find('.currency_code .wcml-error').size() == 0){
                parent.find('.currency_code').append( $messageContainer );
                $messageContainer.text( $currencyCodeWraper.data('message') );
                // empty
            }
            flag = true;
        }else{
            if(parent.find('.currency_code .wcml-error').size() > 0){
                parent.find('.currency_code .wcml-error').remove();
            }
        }

        if(currency_value == ''){
            if(parent.find('.currency_value .wcml-error').size() == 0){

                parent.find('.currency_value').append( $messageContainer );
                $messageContainer.text( $currencyCodeWraper.data('message') );
                // empty
            }
            flag = true;
        }else{
            if(parent.find('.currency_value .wcml-error').size() > 0){
                parent.find('.currency_value .wcml-error').remove();
            }
        }

        if(!isNumber(currency_value)){
            if(parent.find('.currency_value .wcml-error').size() == 0){
                parent.find('.currency_value').append( $messageContainer );
                $messageContainer.text( $currencyValueWraper.data('message') );
                // numeric
            }
            flag = true;
        }else{
            if(parent.find('.currency_value .wcml-error').size() > 0){
                parent.find('.currency_value .wcml-error').remove();
            }
        }

        if(flag){
            $ajaxLoader.remove();
            $this.prop('disabled',false);
            parent.find('.save_currency').show();
            parent.find('.cancel_currency').show();
            return false;
        }

        $.ajax({
            type : "post",
            url : ajaxurl,
            dataType: 'json',
            data : {
                action: "wcml_new_currency",
                wcml_nonce: $('#new_currency_nonce').val(),
                currency_code : currency_code,
                currency_value : currency_value
            },
            error: function(respnse) {
                // TODO: add error handling
            },
            success: function(response) {
                
                parent.closest('tr').attr('id', 'currency_row_' + currency_code);
                $('#currency-lang-table tr:last').prev().attr('id', 'currency_row_langs_' + currency_code);
                
                $('#currency_row_langs_' + currency_code + ' .off_btn').attr('data-currency', currency_code);
                $('#currency_row_langs_' + currency_code + ' .on_btn').attr('data-currency', currency_code);
                
                parent.find('.currency_code .code_val').html(response.currency_name_formatted);
                parent.find('.currency_code .currency_value span').html(response.currency_meta_info);
                
                parent.find('.currency_code').prepend(response.currency_options);

                parent.find('.currency_code select[name="code"]').remove();
                parent.find('.currency_value input').remove();
                
                parent.find('.edit_currency').data('currency', currency_code).show();
                parent.find('.delete_currency').data('currency', currency_code).show();

                $('.js-table-row-wrapper select option[value="'+currency_code+'"]').remove();    
                $('.currency_languages select').each(function(){
                   $(this).append('<option value="'+currency_code+'">'+currency_code+'</option>');
                });

                $('#wcml_currencies_order').append('<li class="wcml_currencies_order_'+currency_code+'" cur="'+currency_code+'>'+response.currency_name_formatted_without_rate+'</li>');
                currency_switcher_preview();
            },
            complete: function() {
                $ajaxLoader.remove();
                $this.prop('disabled',false);
            }
        });

        return false;
    });


    $(document).on('click','.delete_currency',function(e){
        e.preventDefault();

        var currency = $(this).data('currency');
        

        $('#currency_row_' + currency + ' .currency_action_update').hide();
        var ajaxLoader = $('<span class="spinner">');
        $(this).hide();
        $(this).parent().append(ajaxLoader).show();
                     
        $.ajax({
            type : "post",
            url : ajaxurl,
            data : {
                action: "wcml_delete_currency",
                wcml_nonce: $('#del_currency_nonce').val(),
                code: currency
            },
            success: function(response) {
                $('#currency_row_' + currency).remove();                
                $('#currency_row_langs_' + currency).remove();                
                $('#wcml_currencies_order .wcml_currencies_order_'+ currency).remove();

                $.ajax({
                    type : "post",
                    url : ajaxurl,
                    data : {
                        action: "wcml_currencies_list",
                        wcml_nonce: $('#currencies_list_nonce').val()
                    },
                    success: function(response) {
                        $('.js-table-row-wrapper select').html(response);
                    }
                });
                currency_switcher_preview();
            },
            done: function() {
                ajaxLoader.remove();
            }
        });

        return false;
    });

    
    $(document).on('click', '.wcml_currency_options_popup :submit', function(){
        var parent = $(this).closest('.wcml_currency_options_popup');

        var chk_rate = check_on_numeric(parent,'.ext_rate');
        var chk_deci = check_on_numeric(parent,'.decimals_number');
        var chk_autosub = check_on_numeric(parent,'.abstract_amount');

        if(chk_rate || chk_deci || chk_autosub){
            return false;
        }

        
        $('.wcml_currency_options_popup :submit, .wcml_currency_options_popup :button').prop('disabled', true);
        var currency = $(this).data('currency');

        var ajaxLoader = $('<span class="spinner" style="position:absolute;margin-left:-30px;"></span>');

        ajaxLoader.show();
        $(this).parent().prepend(ajaxLoader);
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: $('#wcml_mc_options').serialize() + '&action=wcml_save_currency&currency='+currency+'&wcml_nonce='+ $('#save_currency_nonce').val(),
            success: function(response){                
                $('.wcml_currency_options_popup').fadeOut(function(){
                    ajaxLoader.remove();
                    $('.wcml_currency_options_popup :submit, .wcml_currency_options_popup :button').prop('disabled', false);
                    
                    $('#currency_row_' + currency + ' .currency_code .code_val').html(response.currency_name_formatted);
                    $('#currency_row_' + currency + ' .currency_value span').html(response.currency_meta_info);
                    
                    
                });
            }
            
        })
        
        return false;
    })
    
    
    function check_on_numeric(parent, elem){
        var messageContainer = $('<span class="wcml-error">');

        if(!isNumber(parent.find(elem).val())){
            if(parent.find(elem).parent().find('.wcml-error').size() == 0){
                parent.find(elem).parent().append( messageContainer );
                messageContainer.text( parent.find(elem).data('message') );
            }
            return true;
        }else{
            if(parent.find(elem).parent().find('.wcml-error').size() > 0){
                parent.find(elem).parent().find('.wcml-error').remove();
            }
            return false;
        }
    }
    
    
    // expand|collapse for product images and product variations tables
    $(document).on('click','.js-table-toggle',function(e){

        e.preventDefault();

        var textOpened = $(this).data('text-opened');
        var textClosed = $(this).data('text-closed');
        var $table = $(this).next('.js-table');
        
        var this_id = $(this).attr('id');
        if($(this).hasClass('prod_images_link')){
            var id_and_language = this_id.replace(/^prod_images_link_/, '');
        }else{
            var id_and_language = this_id.replace(/^prod_variations_link_/, '');
        }
        var spl = id_and_language.split('_');
        var language    = spl[1];
        var product_id  = spl[0];
        
        if ( $table.is(':visible') ) {
            $table.hide();
            $(this)
                .find('span')
                .text( textClosed );
            $(this)
                .find('i')
                .removeClass('icon-caret-up')
                .addClass('icon-caret-down');
            if($(this).hasClass('prod_images_link')){
                $('#prod_images_' + product_id + '_' + language).hide();
            }else{
                $('#prod_variations_' + product_id  + language).hide();
            }

        }
        else {
            $table.show();
            if($(this).hasClass('prod_images_link')){
                $('#prod_images' + product_id  + language).show();
            }else{
                $('#prod_variations_' + product_id  + language).show();
            }
            $(this)
                .find('span')
                .text( textOpened );
            $(this)
                .find('i')
                .removeClass('icon-caret-down')
                .addClass('icon-caret-up');
        }

        return false;
    });

    // wp-pointers
    $('.js-display-tooltip').click(function(){
        var $thiz = $(this);

        // hide this pointer if other pointer is opened.
        $('.wp-pointer').fadeOut(100);

        $(this).pointer({
            content: '<h3>'+$thiz.data('header')+'</h3><p>'+$thiz.data('content')+'</p>',
            position: {
                edge: 'left',
                align: 'center',
                offset: '15 0'
            }
        }).pointer('open');
    });

    $(document).on('click','.currency_languages a.on_btn',function(e){
        $(this).closest('ul').find('.on').removeClass('on');
        $(this).parent().addClass('on');
        var index = $(this).closest('tr')[0].rowIndex;
        $('.currency_languages select[rel="'+$(this).data('language')+'"]').append('<option value="'+$(this).data('currency')+'">'+$(this).data('currency')+'</option>');
        update_currency_lang($(this),1,0);
    });

    $(document).on('click','.currency_languages a.off_btn',function(e){
        var enbl_elem = $(this).closest('ul').find('.on').removeClass('on');
        var flag = true;
        var lang = $(this).data('language');
        $('#currency-lang-table .on_btn[data-language="'+lang+'"]').each(function(){
            if($(this).parent().hasClass('on'))
                flag = false;
        });

        if(flag){
            enbl_elem.addClass('on');
            alert($('#wcml_warn_disable_language_massage').val());
            return;
        }

        $(this).parent().addClass('on');
        var index = $(this).closest('tr')[0].rowIndex;

        if($('.currency_languages select[rel="'+$(this).data('language')+'"]').val() == $(this).data('currency')){
            update_currency_lang($(this),0,1);
        }else{
            update_currency_lang($(this),0,0);
        }
        $('.currency_languages select[rel="'+$(this).data('language')+'"] option[value="'+$(this).data('currency')+'"]').remove();
    });

    function update_currency_lang(elem, value, upd_def){
        $('input[name="wcml_mc_options"]').attr('disabled','disabled');
        var lang = elem.data('language');
        var code = elem.data('currency');
        discard = true;
        $.ajax({
            type: 'post',
            url: ajaxurl,
            data: {
                action: 'wcml_update_currency_lang',
                value: value,
                lang: lang,
                code: code,
                wcml_nonce: $('#update_currency_lang_nonce').val()
            },
            success: function(){
                if(upd_def){
                    update_default_currency(lang,0);
                }
            },
            complete: function() {
                $('input[name="wcml_mc_options"]').removeAttr('disabled');
                discard = false;
            }
        });
    }

    $('.default_currency select').change(function(){
        update_default_currency($(this).attr('rel'), $(this).val());
    });

    function update_default_currency(lang,code){
        discard = true;
        $.ajax({
            type: 'post',
            url: ajaxurl,
            data: {
                action: 'wcml_update_default_currency',
                lang: lang,
                code: code,
                wcml_nonce: $('#wcml_update_default_currency_nonce').val()
            },
            complete: function(){
                discard = false;
            }
        });
    }

    function isNumber(n) {
        return !isNaN(parseFloat(n)) && isFinite(n);
    }
    
    $(document).on('click', '#wcml_dimiss_non_default_language_warning', function(){
        $(this).attr('disabled', 'disabled');   
        var ajaxLoader = $('<span class="spinner">');
        $(this).parent().append(ajaxLoader);
        ajaxLoader.show();
        $.ajax({
            type: 'post',
            url: ajaxurl,
            dataType:'json',
            data: {
                action: 'wcml_update_setting_ajx',
                setting: 'dismiss_non_default_language_warning',
                value: 1,
                nonce: $('#wcml_settings_nonce').val()
            },
            success: function(response){
                location.reload();
            }
        });
    });


    $('#wcml_currencies_order').sortable({
        update: function(){
            $('.wcml_currencies_order_ajx_resp').fadeIn();
            var currencies_order = [];
            $('#wcml_currencies_order').find('li').each(function(){
                currencies_order.push($(this).attr('cur'));
            });
            $.ajax({
                type: "POST",
                url: ajaxurl,
                dataType: 'json',
                data: {
                    action: 'wcml_currencies_order',
                    wcml_nonce: $('#wcml_currencies_order_order_nonce').val(),
                    order: currencies_order.join(';')
                },
                success: function(resp){
                    fadeInAjxResp('.wcml_currencies_order_ajx_resp', resp.message);
                    currency_switcher_preview();
                }
            });
        }
    });

    $(document).on('click','input[name="currency_switcher_style"]',function(e){
        $(this).closest('ul').find('select').hide();
        $(this).closest('li').find('select').show();
        currency_switcher_preview();
    });

    $(document).on('change','#wcml_curr_sel_orientation',function(e){
        currency_switcher_preview();
    });

    $(document).on('keyup','input[name="wcml_curr_template"]',function(e){
        discard = true;
        $(this).closest('.wcml-section').find('.button-wrap input').css("border-color","#1e8cbe");
        currency_switcher_preview();
    });

    $(document).on('change','input[name="wcml_curr_template"]',function(e){
        if(!$(this).val()){
            $('input[name="wcml_curr_template"]').val($('#currency_switcher_default').val())
        }
    });

    function currency_switcher_preview(){
        var template = $('input[name="wcml_curr_template"]').val();
        if(!template){
            template = $('#currency_switcher_default').val();
        }
        $.ajax({
            type: "POST",
            url: ajaxurl,
            data: {
                action: 'wcml_currencies_switcher_preview',
                wcml_nonce: $('#wcml_currencies_switcher_preview_nonce').val(),
                switcher_type: $('input[name="currency_switcher_style"]:checked').val(),
                orientation: $('#wcml_curr_sel_orientation').val(),
                template: template
            },
            success: function(resp){
                $('#wcml_curr_sel_preview').html(resp);
            }
        });
    }

});

