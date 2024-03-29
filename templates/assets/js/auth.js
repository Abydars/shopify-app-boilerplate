jQuery(function ($) {
    $('#form').submit(function () {
        try {
            var formData = $(this).serializeArray();
            $.post(window.ajx_url + "/auth", formData, function (res) {
                if (res.status) {
                    window.location.href = window.url + "/dashboard";
                }
            }, 'json');
        } catch (e) {
            console.log(e);
        }

        return false;
    });
});
