<?php

namespace Xfrocks\ApiConsumer\XF\Mvc;

use XF\Mvc\Reply\AbstractReply;
use XF\Mvc\Reply\Redirect;
use XF\Mvc\RouteMatch;
use Xfrocks\ApiConsumer\Service\AutoLogin;
use Xfrocks\ApiConsumer\Service\RedirectBuilder;

class Dispatcher extends XFCP_Dispatcher
{
    public function run($routePath = null)
    {
        $app = $this->app;
        if ($app->container('app.defaultType') === 'public' &&
            $app->request()->get('_xfResponseType') === false &&
            ($providerId = $app->options()->bdapi_consumer_autoLoginSession) &&
            ($session = $app->session()) &&
            !$session->exists() &&
            $session->robot === '') {
            /** @var AutoLogin $autoLogin */
            $autoLogin = $this->app->service('Xfrocks\ApiConsumer:AutoLogin');
            $url = $autoLogin->getRedirectUrl($providerId);
            if (is_string($url)) {
                return $this->render(new Redirect($url), 'html');
            }
        }

        return parent::run($routePath);
    }

    public function dispatchLoop(RouteMatch $match)
    {
        $preDispatchUserId = intval($this->app->session()->get('userId'));

        $reply = parent::dispatchLoop($match);

        $this->xfrocksApiConsumerBuildRedirects($preDispatchUserId, $reply);

        return $reply;
    }

    /**
     * @param int $preDispatchUserId
     * @param AbstractReply $reply
     */
    private function xfrocksApiConsumerBuildRedirects($preDispatchUserId, &$reply)
    {
        $userId = intval($this->app->session()->get('userId'));
        if ($userId === $preDispatchUserId ||
            $preDispatchUserId > 0 && $userId > 0 ||
            !($reply instanceof Redirect)) {
            return;
        }

        /** @var AutoLogin $autoLogin */
        $autoLogin = $this->app->service('Xfrocks\ApiConsumer:AutoLogin');
        $providers = $autoLogin->getProviders();
        if (count($providers) === 0) {
            return;
        }

        $action = 'login';
        $url = $this->app->request()->convertToAbsoluteUri($reply->getUrl());
        if ($preDispatchUserId > $userId) {
            $action = 'logout';
            $userId = $preDispatchUserId;
        }

        /** @var RedirectBuilder $builder */
        $builder = $this->app->service('Xfrocks\ApiConsumer:RedirectBuilder');
        $newUrl = $builder->build($providers, $action, $url, $userId);

        $reply->setUrl($newUrl);
    }
}
