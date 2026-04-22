jQuery(function ($) {
    function sendRequest($btn, payload) {
        if ($btn.prop('disabled')) return;
        $btn.prop('disabled', true);

        $.post(wlData.ajaxurl, payload)
            .done(function (res) {
                var msg = (res && res.data && res.data.msg) ? res.data.msg : '';
                if (msg) alert(msg);
                if (res && res.success) {
                    location.reload();
                }
            })
            .fail(function () {
                alert('Something went wrong. Please try again.');
            })
            .always(function () {
                $btn.prop('disabled', false);
            });
    }

    $(document).on('click', '#wl-join', function () {
        var $btn = $(this);
        var product_id = $btn.data('product');
        var email = '';

        if (!wlData.logged_in) {
            email = prompt('Enter your email:');
            if (!email) return;
        }

        sendRequest($btn, {
            action: 'waitlist_join',
            nonce: wlData.nonce,
            product_id: product_id,
            email: email
        });
    });

    $(document).on('click', '#wl-leave', function () {
        var $btn = $(this);
        sendRequest($btn, {
            action: 'waitlist_leave',
            nonce: wlData.nonce,
            product_id: $btn.data('product')
        });
    });
});
