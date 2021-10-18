/*
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

define(
    [
        'ko',
        'jquery',
        'mage/translate',
        'EMSPay_Payment/js/view/payment/method-renderer/default'
    ],
    function (ko, $, $t, Component) {
        var checkoutConfig = window.checkoutConfig.payment;
        'use strict';
        return Component.extend({
            defaults: {
                template: 'EMSPay_Payment/payment/ideal',
                selectedIssuer: null
            },
            getIssuers: function () {
                var issuers = checkoutConfig[this.item.method].issuers;
                issuers.unshift({"id":"SELECTYOURBANK", "name":$t('-- Select your bank')});
                return issuers;
            },
            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        "issuer": this.selectedIssuer
                    }
                };
            },
            validate: function () {
                var form = $('#emspay_methods_ideal-form');
                return form.validation() && form.validation('isValid');
            }
        });
    }
);
