jQuery( document ).ready( function( $ ){

    if( $( '.wcml_custom_costs_input:checked' ).val() == 1 ){

        $( '.wcml_custom_cost_field' ).show();

    }

    $(document).on( 'change', '.wcml_custom_costs_input', function(){

        if( $(this).val() == 1 ){

            $( '.wcml_custom_cost_field' ).show();

        }else{

            $( '.wcml_custom_cost_field' ).hide();

        }

    });

    $(document).on( 'mouseout', '.add_row', function(){

        if( $( '.wcml_custom_costs_input:checked' ).val() == 1 ) {

            $( '.wcml_custom_cost_field' ).show();

        }

    });

    $(document).on( 'mouseout', '.add_person', function(){

        if( $( '.wcml_custom_costs_input:checked' ).val() == 1 ) {

            setTimeout(
                function() {
                    $( '.wcml_custom_cost_field' ).show();
                }, 3000);

        }

    });


    //lock fields
    if( lock_fields == 1 ){

        $('#bookings_pricing input[type="number"],#accommodation_bookings_rates input[type="number"], #bookings_resources input[type="number"], #bookings_availability input[type="number"], #bookings_availability input[type="text"], #bookings_persons input[type="number"]').each(function(){
            $(this).attr('readonly','readonly');
            $(this).after($('.wcml_lock_img').clone().removeClass('wcml_lock_img').show());
        });


        $('#bookings_pricing select, #bookings_resources select, #bookings_availability select,#bookings_persons input[type="checkbox"]').each(function(){
            $(this).attr('disabled','disabled');
            $(this).after($('.wcml_lock_img').clone().removeClass('wcml_lock_img').show());
        });

        var ids = [ '_wc_booking_has_resources', '_wc_booking_has_persons', '_wc_booking_duration_type', '_wc_booking_duration',
            '_wc_booking_duration_unit', '_wc_booking_calendar_display_mode', '_wc_booking_requires_confirmation', '_wc_booking_user_can_cancel',
            '_wc_accommodation_booking_min_duration', '_wc_accommodation_booking_max_duration', '_wc_accommodation_booking_max_duration', '_wc_accommodation_booking_calendar_display_mode',
            '_wc_accommodation_booking_requires_confirmation', '_wc_accommodation_booking_user_can_cancel', '_wc_accommodation_booking_cancel_limit', '_wc_accommodation_booking_cancel_limit_unit',
            '_wc_accommodation_booking_qty', '_wc_accommodation_booking_min_date', '_wc_accommodation_booking_min_date_unit', '_wc_accommodation_booking_max_date', '_wc_accommodation_booking_max_date_unit'
        ];

        for (i = 0; i < ids.length; i++) {
            $('#'+ids[i]).attr('disabled','disabled');
            $('#'+ids[i]).after($('.wcml_lock_img').clone().removeClass('wcml_lock_img').show());
        }

        var buttons = [ 'add_resource', 'add_row' ];

        for (i = 0; i < buttons.length; i++) {
            $('.'+buttons[i]).attr('disabled','disabled');
            $('.'+buttons[i]).unbind('click');
            $('.'+buttons[i]).after($('.wcml_lock_img').clone().removeClass('wcml_lock_img').show());
        }

        $('form#post input[type="submit"]').click(function(){

            for (i = 0; i < ids.length; i++) {
                $('#'+ids[i]).removeAttr('disabled');
            }

            $('#bookings_pricing select, #bookings_resources select, #bookings_availability select,#bookings_persons input[type="checkbox"]').each(function(){
                $(this).removeAttr('disabled');
            });

        });


    }
});

