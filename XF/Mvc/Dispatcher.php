<?php

namespace Xfrocks\ApiConsumer\XF\Mvc;

use XF\Mvc\Reply\Redirect;
use Xfrocks\ApiConsumer\Service\AutoLoginSession;

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
            /** @var AutoLoginSession $autoLoginSession */
            $autoLoginSession = $this->app->service('Xfrocks\ApiConsumer:AutoLoginSession');
            $url = $autoLoginSession->getRedirectUrl($providerId);
            if (is_string($url)) {
                return $this->render(new Redirect($url), 'html');
            }
        }

        return parent::run($routePath);
    }
}
