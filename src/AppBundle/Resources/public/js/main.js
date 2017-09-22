jQuery(document).ready(function () {
    $('form[name="credentials_form"]').on('submit', function(e) {
        if (!$(this).data('encoded')) {
            e.preventDefault();
            var password = $('#credentials_form_password').val();
            $('#credentials_form_password').val(CryptoJS.SHA3(password, { outputLength: 128 }));
            $(this).data('encoded', true);
            $(this).submit();
        }
    });
    $('form[name="password_form"]').on('submit', function(e) {
        if (!$(this).data('encoded')) {
            e.preventDefault();
            var password = $('#password_form_password').val();
            $('#password_form_password').val(CryptoJS.SHA3(password, { outputLength: 128 }));
            $(this).data('encoded', true);
            $(this).submit();
        }
    });
});
