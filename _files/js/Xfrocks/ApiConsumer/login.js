!function ($, window, document, _undefined) {
    'use strict';

    // ################################################################################

    var onIsAuthorizedTimer = null;

    XF.Xfrocks_ApiConsumer_Provider = XF.Element.newHandler({

        options: {
            clientId: null,
            debug: false,
            loginLink: null,
            providerId: null,
            sdkUrl: null,
            scope: 'read'
        },

        init: function () {
            if (!this.options.clientId) {
                console.error('Provider must contain a data-client-id attribute.');
                return;
            }

            if (!this.options.loginLink) {
                console.error('Provider must contain a data-login-link attribute.');
                return;
            }

            if (!this.options.providerId) {
                console.error('Provider must contain a data-provider-id attribute.');
                return;
            }

            if (!this.options.sdkUrl) {
                console.error('Provider must contain a data-sdk-url attribute.');
                return;
            }

            var that = this,
                prefix = this.options.providerId.replace(/[^a-z]/, ''),
                scriptSrc = this.options.sdkUrl + '&prefix=' + prefix,
                initName = prefix + 'Init';

            window[initName] = function () {
                var SDK = window[prefix + 'SDK'];
                if (SDK === _undefined) {
                    return;
                }

                that.onSdkInit(SDK);
            };

            $('<script>').prop({src: scriptSrc, async: true}).appendTo(document.head);

            this.log('scriptSrc = %s', scriptSrc);
        },

        onSdkInit: function (SDK) {
            var that = this;

            SDK.init({client_id: this.options.clientId});

            // noinspection JSUnresolvedFunction
            SDK.isAuthorized(this.options.scope, function (isAuthorized, apiData) {
                that.log('isAuthorized = %s', isAuthorized);
                if (!isAuthorized) {
                    return;
                }

                if (onIsAuthorizedTimer) {
                    window.clearTimeout(onIsAuthorizedTimer);
                    that.log('Cleared existing onIsAuthorizedTimer');
                }
                onIsAuthorizedTimer = window.setTimeout(function () {
                    that.log('onIsAuthorizedTimer');
                    XF.ajax(
                        'POST',
                        that.options.loginLink,
                        {
                            apiData: apiData,
                            providerId: that.options.providerId,
                            _xfRedirect: window.location.href
                        },
                        $.proxy(that, 'onLoginSuccess'),
                        {
                            global: false,
                            skipDefault: true,
                            skipError: true
                        }
                    );
                }, 200);
            });
        },

        onLoginSuccess: function (ajaxData) {
            var redirecting = false;
            if (ajaxData.redirect !== _undefined) {
                if (!this.options.debug) {
                    window.location = ajaxData.redirect;
                } else {
                    alert('[DEBUG] window.location = ' + ajaxData.redirect);
                }
                redirecting = true;
            }

            if (ajaxData.message) {
                XF.flashMessage(ajaxData.message, redirecting ? 30000 : 5000);
            }
        },

        log: function () {
            if (!this.options.debug || console === _undefined || console.log === _undefined) {
                return;
            }

            var logParams = arguments;
            if (typeof logParams[0] === 'string') {
                logParams[0] = '[' + this.options.providerId + '] ' + logParams[0];
            }

            console.log.apply(console, logParams);
        }
    });

    // ################################################################################

    XF.Element.register('api-consumer-provider', 'XF.Xfrocks_ApiConsumer_Provider');

}(jQuery, window, document);