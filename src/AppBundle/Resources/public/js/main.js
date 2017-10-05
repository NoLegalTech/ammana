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
    $('.has-condition').each(function() {
        var $element = $(this);
        var parts = $element.data('condition').split('=');
        var key = parts[0], value = parts[1];
        var showElements = function(toUpdate) {
            toUpdate.show();
            toUpdate.prop("required", true);
        };
        var hideElements = function(toUpdate) {
            toUpdate.hide();
            toUpdate.removeAttr('required');
            toUpdate.find('input[type="radio"]:first').each(function() {
                $(this).attr("checked", "checked")
            });
        };
        $('#form_' + key + ' input[name="form[' + key + ']"]').on('change', function() {
            if ($(this).val() == value) {
                showElements($element);
            } else {
                hideElements($element);
            }
        });
        hideElements($element);
    });
});
