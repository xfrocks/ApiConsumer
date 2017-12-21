<?php

namespace Xfrocks\ApiConsumer\XF\Mvc;

use XF\Entity\ConnectedAccountProvider;
use XF\Mvc\Reply\Redirect;
use Xfrocks\ApiConsumer\ConnectedAccount\Provider;

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
            /** @var ConnectedAccountProvider $provider */
            $provider = $app->em()->find('XF:ConnectedAccountProvider', $providerId);
            if (!empty($provider) && $provider->provider_class === Provider::PROVIDER_CLASS) {
                $requestUri = $this->request->getFullRequestUri();
                $session->set('connectedAccountRequest', [
                    'provider' => $provider->provider_id,
                    'returnUrl' => $requestUri,
                    'test' => false
                ]);

                $handler = $provider->handler;
                $oauth = $handler->getOAuth($handler->getOAuthConfig($provider));
                $url = $oauth->getAuthorizationUri(['guest_redirect_uri' => $requestUri])->getAbsoluteUri();

                return $this->render(new Redirect($url), 'html');
            }
        }

        return parent::run($routePath);
    }
}
