<?php

namespace Xfrocks\ApiConsumer;

use XF\App;
use XF\Container;
use XF\Entity\User;
use XF\Session\Session;
use XF\Template\Templater;
use Xfrocks\ApiConsumer\Service\AutoLogin;

class Listener
{
    const CONNECTED_ACCOUNT_EXTRA_DATA_KEY = 'bdapi';
    const CONNECTED_ACCOUNT_EXTRA_DATA_REFRESH_TOKEN = 'refresh_token';
    const CONNECTED_ACCOUNT_EXTRA_DATA_END_OF_LIFE = 'end_of_life';
    const CONNECTED_ACCOUNT_EXTRA_DATA_EXTRA_PARAMS = 'extra_params';
    const SESSION_KEY_LATEST_AUTO_LOGIN_TIME = 'bdapi_consumer_lalt';
    const SESSION_KEY_LOGGED_IN_PROVIDER_ID = 'bdapi_consumer_alpi';
    const SESSION_KEY_USER_CONNECTED_ACCOUNT_FROM_PROVIDER_ID = 'bdapi_consumer_ucafpi';

    /**
     * @param Container $container
     * @param Templater $templater
     */
    public static function templaterSetup($container, &$templater)
    {
        $templater->addFunction(
            'get_api_consumer_auto_login_providers',
            function ($templater, &$escape, $xf) {
                /** @var Templater $templater */
                /** @var Session $session */
                if (($session = $xf['session']) && $session->offsetExists(self::SESSION_KEY_LATEST_AUTO_LOGIN_TIME)) {
                    // TODO: retry after some ttl?
                    return [];
                }

                /** @var User $visitor */
                if (($visitor = $xf['visitor']) && ($visitor->user_id > 0)) {
                    return [];
                }

                if (empty($xf['reply']['controller'])) {
                    return [];
                }
                switch ($xf['reply']['controller']) {
                    case 'XF:Error':
                    case 'XF:Login':
                    case 'XF:Register':
                        return [];
                }

                /** @var App $app */
                $app = $xf['app'];
                /** @var AutoLogin $autoLogin */
                $autoLogin = $app->service('Xfrocks\ApiConsumer:AutoLogin');
                $providers = $autoLogin->getProviders();

                foreach ($providers as &$providerRef) {
                    $providerRef['sdkJsUrl'] = sprintf(
                        '%s/index.php?assets/sdk.js&%s',
                        rtrim($providerRef['options']['root'], '/'),
                        $templater->getJsCacheBuster()
                    );
                }

                return $providers;
            }
        );
    }
}
