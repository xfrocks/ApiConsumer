<?php

namespace Xfrocks\ApiConsumer;

use XF\Container;
use XF\Entity\User;
use XF\Mvc\Dispatcher;
use XF\Mvc\Reply\AbstractReply;
use XF\Mvc\Reply\Redirect;
use XF\Mvc\RouteMatch;
use XF\Session\Session;
use XF\Template\Templater;
use Xfrocks\ApiConsumer\Service\RedirectBuilder;

class Listener
{
    const CONNECTED_ACCOUNT_EXTRA_DATA_KEY = 'bdapi';
    const CONNECTED_ACCOUNT_EXTRA_DATA_REFRESH_TOKEN = 'refresh_token';
    const CONNECTED_ACCOUNT_EXTRA_DATA_END_OF_LIFE = 'end_of_life';
    const CONNECTED_ACCOUNT_EXTRA_DATA_EXTRA_PARAMS = 'extra_params';
    const SESSION_KEY_LATEST_AUTO_LOGIN_TIME = 'bdapi_consumer_lalt';
    const SESSION_KEY_LOGGED_IN_PROVIDER_ID = 'bdapi_consumer_alpi';
    const SESSION_KEY_USER_CONNECTED_ACCOUNT_FROM_PROVIDER_ID = 'bdapi_consumer_ucafpi';

    private static $preDispatchSessionUserId = null;

    /**
     * @param Dispatcher $dispatcher
     * @param RouteMatch $match
     */
    public static function dispatcherMatch($dispatcher, $match)
    {
        self::$preDispatchSessionUserId = intval(\XF::session()->get('userId'));
    }

    /**
     * @param Dispatcher $dispatcher
     * @param AbstractReply $reply
     * @param RouteMatch $match
     * @param RouteMatch $originalMatch
     */
    public static function dispatcherPostDispatch($dispatcher, &$reply, $match, $originalMatch)
    {
        $userId = intval(\XF::session()->get('userId'));
        if (self::$preDispatchSessionUserId === $userId) {
            return;
        }
        if (self::$preDispatchSessionUserId > 0 && $userId > 0) {
            return;
        }

        if (!($reply instanceof Redirect)) {
            return;
        }

        $providers = self::getAutoLoginProviders();
        if (count($providers) === 0) {
            return;
        }

        $action = 'login';
        $url = \XF::app()->request()->convertToAbsoluteUri($reply->getUrl());
        if (self::$preDispatchSessionUserId > $userId) {
            $action = 'logout';
            $userId = self::$preDispatchSessionUserId;
        }

        /** @var RedirectBuilder $builder */
        $builder = \XF::service('Xfrocks\ApiConsumer:RedirectBuilder');
        $newUrl = $builder->build($providers, $action, $url, $userId);

        $reply->setUrl($newUrl);
    }

    /**
     * @return array
     */
    private static function getAutoLoginProviders()
    {
        static $result = null;

        if ($result === null) {
            $result = [];

            $providers = \XF::options()->bdapi_consumer_providers;
            foreach ($providers as $provider) {
                if (empty($provider['isUsable']) ||
                    empty($provider['options']['auto_login_js'])) {
                    continue;
                }

                $result[$provider['provider_id']] = $provider;
            }
        }

        return $result;
    }

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

                $providers = self::getAutoLoginProviders();
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
