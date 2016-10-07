jQuery(document).ready(function ($) {
    var discard = false;

    window.onbeforeunload = function (e) {
        if (discard) {
            return $('#wcml_warn_message').val();
        }
    }

    $('.wcml-section input[type="submit"]').click(function () {
        discard = false;
    });

    $('.wcml_search').click(function () {
        window.location = $('.wcml_products_admin_url').val() + '&cat=' + $('.wcml_product_category').val() + '&trst=' + $('.wcml_translation_status').val() + '&st=' + $('.wcml_product_status').val() + '&slang=' + $('.wcml_translation_status_lang').val();
    });

    $('.wcml_search_by_title').click(function () {
        window.location = $('.wcml_products_admin_url').val() + '&s=' + $('.wcml_product_name').val();
    });

    $('.wcml_reset_search').click(function () {
        window.location = $('.wcml_products_admin_url').val();
    });

    if (typeof TaxonomyTranslation != 'undefined') {

        TaxonomyTranslation.views.TermView = TaxonomyTranslation.views.TermView.extend({
            initialize: function () {
                TaxonomyTranslation.views.TermView.__super__.initialize.apply(this, arguments);
                this.listenTo(this.model, 'translationSaved', this.render_overlay);
            },
            render_overlay: function () {
                var taxonomy = TaxonomyTranslation.classes.taxonomy.get("taxonomy");
                $.ajax({
                    type: "post",
                    url: ajaxurl,
                    dataType: 'json',
                    data: {
                        action: "wcml_update_term_translated_warnings",
                        taxonomy: taxonomy,
                        wcml_nonce: $('#wcml_update_term_translated_warnings_nonce').val()
                    },
                    success: function (response) {
                        if (response.hide) {
                            if (response.is_attribute) {
                                $('.tax-product-attributes').removeAttr('title');
                                $('.tax-product-attributes i.otgs-ico-warning').remove();
                            } else {
                                $('.js-tax-tab-' + taxonomy).removeAttr('title');
                                $('.js-tax-tab-' + taxonomy + ' i.otgs-ico-warning').remove();
                            }
                        }
                    }
                })
            }
        });

    }

    $(document).on('click', '.js-tax-translation li a[href^="#ignore-"]', function () {

        disable_tax_translation_toggling();

        var taxonomy = $(this).attr('href').replace(/#ignore-/, '');

        var spinner = '<span class="spinner" style="visibility: visible; position: absolute" />';
        $(this).append(spinner);

        $.ajax({
            type: "post",
            url: ajaxurl,
            dataType: 'json',
            data: {
                action: "wcml_ingore_taxonomy_translation",
                taxonomy: taxonomy,
                wcml_nonce: $('#wcml_ingore_taxonomy_translation_nonce').val()
            },
            success: function (response) {

                if (response.html) {
                    $('.js-tax-translation li.js-tax-translation-' + taxonomy).html(response.html);

                    $('.js-tax-tab-' + taxonomy).removeAttr('title');
                    $('.js-tax-tab-' + taxonomy + ' i.otgs-ico-warning').remove();
                }

                enable_tax_translation_toggling();
            }
        })

        return false;
    })

    $(document).on('click', '.js-tax-translation li a[href^="#unignore-"]', function () {

        disable_tax_translation_toggling();

        var taxonomy = $(this).attr('href').replace(/#unignore-/, '');

        var spinner = '<span class="spinner" style="visibility: visible; position: absolute" />';
        $(this).append(spinner);

        $.ajax({
            type: "post",
            url: ajaxurl,
            dataType: 'json',
            data: {
                action: "wcml_uningore_taxonomy_translation",
                taxonomy: taxonomy,
                wcml_nonce: $('#wcml_ingore_taxonomy_translation_nonce').val()
            },
            success: function (response) {
                if (response.html) {
                    $('.js-tax-translation li.js-tax-translation-' + taxonomy).html(response.html);
                    if (response.warn) {
                        $('.js-tax-tab-' + taxonomy).append('<i class="otgs-ico-warning"></i>');
                    }

                }
                enable_tax_translation_toggling();
            }
        })

        return false;
    })

    function disable_tax_translation_toggling() {
        $('.wcml-tax-translation-list .actions a')
            .bind('click', tax_translation_toggling_return_false)
            .css({cursor: 'wait'});
    }

    function enable_tax_translation_toggling() {
        $('.wcml-tax-translation-list .actions a')
            .unbind('click', tax_translation_toggling_return_false)
            .css({cursor: 'pointer'});
    }

    function tax_translation_toggling_return_false(event) {
        event.preventDefault();
        return false;
    }

    $(document).on('submit', '#wcml_tt_sync_variations', function () {

        var this_form = $('#wcml_tt_sync_variations');
        var data = this_form.serialize();
        this_form.find('.wcml_tt_spinner').fadeIn();
        this_form.find('input[type=submit]').attr('disabled', 'disabled');

        $.ajax({
            type: "post",
            url: ajaxurl,
            dataType: 'json',
            data: data,
            success: function (response) {
                this_form.find('.wcml_tt_sycn_preview').html(response.progress);
                if (response.go) {
                    this_form.find('input[name=last_post_id]').val(response.last_post_id);
                    this_form.find('input[name=languages_processed]').val(response.languages_processed);
                    this_form.trigger('submit');
                } else {
                    this_form.find('input[name=last_post_id]').val(0);
                    this_form.find('.wcml_tt_spinner').fadeOut();
                    this_form.find('input').removeAttr('disabled');
                    jQuery('#wcml_tt_sync_assignment').fadeOut();
                    jQuery('#wcml_tt_sync_desc').fadeOut();
                }

            }
        });

        return false;

    });


    $(document).on('submit', '#wcml_tt_sync_assignment', function () {

        var this_form = $('#wcml_tt_sync_assignment');
        var parameters = this_form.serialize();

        this_form.find('.wcml_tt_spinner').fadeIn();
        this_form.find('input').attr('disabled', 'disabled');

        $('.wcml_tt_sync_row').remove();

        $.ajax({
            type: "POST",
            dataType: 'json',
            url: ajaxurl,
            data: 'action=wcml_tt_sync_taxonomies_in_content_preview&wcml_nonce=' + $('#wcml_sync_taxonomies_in_content_preview_nonce').val() + '&' + parameters,
            success: function (ret) {

                this_form.find('.wcml_tt_spinner').fadeOut();
                this_form.find('input').removeAttr('disabled');

                if (ret.errors) {
                    this_form.find('.errors').html(ret.errors);
                } else {
                    jQuery('#wcml_tt_sync_preview').html(ret.html);
                    jQuery('#wcml_tt_sync_assignment').fadeOut();
                    jQuery('#wcml_tt_sync_desc').fadeOut();
                }

            }

        });

        return false;

    });

    $(document).on('click', 'form.wcml_tt_do_sync a.submit', function () {

        var this_form = $('form.wcml_tt_do_sync');
        var parameters = this_form.serialize();

        this_form.find('.wcml_tt_spinner').fadeIn();
        this_form.find('input').attr('disabled', 'disabled');

        jQuery.ajax({
            type: "POST",
            dataType: 'json',
            url: ajaxurl,
            data: 'action=wcml_tt_sync_taxonomies_in_content&wcml_nonce=' + $('#wcml_sync_taxonomies_in_content_nonce').val() + '&' + parameters,
            success: function (ret) {

                this_form.find('.wcml_tt_spinner').fadeOut();
                this_form.find('input').removeAttr('disabled');

                if (ret.errors) {
                    this_form.find('.errors').html(ret.errors);
                } else {
                    this_form.closest('.wcml_tt_sync_row').html(ret.html);
                }

            }

        });

        return false;


    });

    var wcml_product_rows_data = new Array();
    var wcml_get_product_fields_string = function (row) {
        var string = '';
        row.find('input[type=text], textarea').each(function () {
            string += $(this).val();
        });

        return string;
    }


    $('#wcml_custom_exchange_rates').submit(function () {

        var thisf = $(this);

        thisf.find(':submit').parent().prepend(icl_ajxloaderimg + '&nbsp;')
        thisf.find(':submit').prop('disabled', true);

        $.ajax({

            type: 'post',
            dataType: 'json',
            url: ajaxurl,
            data: thisf.serialize(),
            success: function () {
                thisf.find(':submit').prev().remove();
                thisf.find(':submit').prop('disabled', false);
            }

        })

        return false;
    })

    function wcml_remove_custom_rates(post_id) {

        var thisa = $(this);

        $.ajax({

            type: 'post',
            dataType: 'json',
            url: ajaxurl,
            data: {action: 'wcml_remove_custom_rates', 'post_id': post_id},
            success: function () {
                thisa.parent().parent().parent().fadeOut(function () {
                    $(this).remove()
                });
            }

        })

        return false;

    }

    $(document).on('click', '.wcml_save_base', function (e) {
        e.preventDefault();

        var elem = $(this);
        var dialog_saving_data = $(this).closest('.wcml-dialog-container');
        var link = '#wcml-edit-base-slug-' + elem.attr('data-base') + '-' + elem.attr('data-language') + '-link';
        var dialog_container = '#wcml-edit-base-slug-' + elem.attr('data-base') + '-' + elem.attr('data-language');
        $.ajax({
            type: "post",
            url: ajaxurl,
            dataType: 'json',
            data: {
                action: "wcml_update_base_translation",
                base: elem.attr('data-base'),
                base_value: dialog_saving_data.find('#base-original').val(),
                base_translation: dialog_saving_data.find('#base-translation').val(),
                language: elem.attr('data-language'),
                wcml_nonce: $('#wcml_update_base_nonce').val()
            },
            success: function (response) {
                $(dialog_container).remove();
                $(link).find('i').remove();
                $(link).append('<i class="otgs-ico-edit" >');
                $(link).parent().prepend(response);
            }
        })
    });

    $(document).on('click', '.hide-rate-block', function () {

        var wrap = $(this).closest('.wcml-wrap');

        $(this).attr('disabled', 'disabled');
        var ajaxLoader = $('<span class="spinner" style="visibility: visible;">');
        var setting = jQuery(this).data('setting');
        $(this).parent().prepend(ajaxLoader);
        $(this).remove();

        $.ajax({
            type: 'post',
            url: ajaxurl,
            dataType: 'json',
            data: {
                action: 'wcml_update_setting_ajx',
                setting: setting,
                value: 1,
                nonce: $('#wcml_settings_nonce').val()
            },
            success: function (response) {
                wrap.hide();
            }
        });
        return false;
    });

    $(document).on('click', '#term-table-sync-header', function () {
        $('#wcml_tt_sync_assignment').hide();
        $('#wcml_tt_sync_desc').hide();
    });

    $(document).on('click', '#term-table-header', function () {
        if( $('#wcml_tt_sync_assignment').data('sync') ) {
            $('#wcml_tt_sync_assignment').show();
            $('#wcml_tt_sync_desc').show();
        }
    });


});

