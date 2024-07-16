jQuery(document).ready(function ($) {
    $('#funeraria-login-form').submit(function (e) {
        e.preventDefault(); 
        var form = $(this);
        var data = form.serialize();
        $.ajax({
            url: funeraria_vars.ajaxurl,
            type: 'POST',
            data: data,
            success: function (response) {
                if (response.success) {
                    window.location.href = response.data.redirect;
                } else {
                    $('#login-error-message').html('<div class="alert alert-danger">' + response.data.message + '</div>');
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                console.log(jqXHR, textStatus, errorThrown);
                $('#login-error-message').html('<div class="alert alert-danger">Ha ocurrido un error durante el inicio de sesión.</div>');
            }
        });
    });
});



