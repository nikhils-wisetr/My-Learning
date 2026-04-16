jQuery(function($){
    $(document).on('click', '#wl-join', function(){
        let product_id = $(this).data('product');
        let email = '';
        if ( ! wlData.logged_in ) {
            email = prompt("Enter your email:");
            if (!email) return;
        }
        $.post(wlData.ajaxurl, {
            action: 'waitlist_join',
            nonce: wlData.nonce,
            product_id: product_id,
            email: email
        }, function(res){
            alert(res.data.msg);
            if (res.success) {
                location.reload(); // simple state refresh
            }
        });
    });
    $(document).on('click', '#wl-leave', function(){
        let product_id = $(this).data('product');
        $.post(wlData.ajaxurl, {
            action: 'waitlist_leave',
            nonce: wlData.nonce,
            product_id: product_id
        }, function(res){
            alert(res.data.msg);
            if (res.success) {
                location.reload();
            }
        });
    });
});