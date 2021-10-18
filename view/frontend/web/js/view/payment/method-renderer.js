/*
 * Copyright © Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

define(
    [
        'jquery',
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        $,
        Component,
        rendererList
    ) {
        'use strict';
        var defaultComponent = 'EMSPay_Payment/js/view/payment/method-renderer/default';
        var idealComponent = 'EMSPay_Payment/js/view/payment/method-renderer/ideal';
        var klarnaComponent = 'EMSPay_Payment/js/view/payment/method-renderer/klarna';
        var afterpayComponent = 'EMSPay_Payment/js/view/payment/method-renderer/afterpay';
        var methods = [
            {type: 'emspay_methods_bancontact', component: defaultComponent},
            {type: 'emspay_methods_banktransfer', component: defaultComponent},
            {type: 'emspay_methods_creditcard', component: defaultComponent},
            {type: 'emspay_methods_applepay', component: defaultComponent},
            {type: 'emspay_methods_klarnadirect', component: defaultComponent},
            {type: 'emspay_methods_paypal', component: defaultComponent},
            {type: 'emspay_methods_amex', component: defaultComponent},
            {type: 'emspay_methods_tikkie', component: defaultComponent},
            {type: 'emspay_methods_payconiq', component: defaultComponent},
            {type: 'emspay_methods_klarna', component: klarnaComponent},
            {type: 'emspay_methods_afterpay', component: afterpayComponent},
            {type: 'emspay_methods_ideal', component: idealComponent}
        ];
        $.each(methods, function (k, method) {
            var paymentMethod = window.checkoutConfig.payment[method['type']];
            if (paymentMethod.isActive) {
                rendererList.push(method);
            }
        });
        return Component.extend({});
    }
);
