/*!
 * ammana.es - job protocols generator
 * https://github.com/NoLegalTech/ammana
 * Copyright (C) 2018 Zeres Abogados y Consultores Laborales SLP <zeres@zeres.es>
 * https://github.com/NoLegalTech/ammana/blob/master/LICENSE
 */

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
    $('form[name="adviser_register_form"]').on('submit', function(e) {
        if (!$(this).data('encoded')) {
            e.preventDefault();
            var password = $('#adviser_register_form_password').val();
            $('#adviser_register_form_password').val(CryptoJS.SHA3(password, { outputLength: 128 }));
            $(this).data('encoded', true);
            $(this).submit();
        }
    });
    $('.has-condition').each(function() {
        var $element = $(this);
        var showElement = function(element) {
            element.show();
            element.prop("required", true);
        };
        var hideElement = function(element) {
            element.hide();
            element.removeAttr('required');
            element.find('input[type="radio"]:first').each(function() {
                $(this).attr("checked", "checked")
            });
        };

        var parts = $element.data('condition').split('=');
        var key = parts[0], value = parts[1];
        var $trigger = $('#form_' + key);
        if ($trigger.is('div')) {
            // if it's a div it's because it contains inputs of type radio
            $trigger = $trigger.find('input[name="form[' + key + ']"]');
        }
        $trigger.on('change', function() {
            if ($(this).val() == value) {
                showElement($element);
            } else {
                hideElement($element);
            }
        });
        if ($trigger.is('select')) {
            if ($trigger.val() == value) {
                showElement($element);
            } else {
                hideElement($element);
            }
        } else {
            hideElement($element);
        }
    });
});
