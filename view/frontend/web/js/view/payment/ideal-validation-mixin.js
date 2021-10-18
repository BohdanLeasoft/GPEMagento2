define(['jquery'], function($) {
    'use strict';

    return function() {
        $.validator.addMethod(
            'validate-bank',
            function(value, element) {
                return value != "SELECTYOURBANK";
            },
            $.mage.__('Please select a bank, this field is required')
        )
    }
});
