jQuery(document).ready(function($){
    try {
        if (sessionStorage.getItem('wc_cart_hash') == '') {
            sessionStorage.removeItem('wc_fragments');
        }
    } catch(err){
        //console.log(err.message);
    }
});

