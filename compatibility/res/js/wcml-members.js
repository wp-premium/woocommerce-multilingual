jQuery( document ).ready( function( $ ){

    if( typeof endpoints != 'undefined' ){

        var tabs = [ 'my-membership-content', 'my-membership-products', 'my-membership-discounts', 'my-membership-notes'];

        for ( i = 0; i < tabs.length; i++ ) {
            var link = $('.'+tabs[i]+' a'),
                link_href = link.attr('href');
            if ( link_href ){
                link.attr('href', link_href.replace( endpoints.original, endpoints.translated ));
            }
        }
    }

});



