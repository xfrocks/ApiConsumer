<?php

namespace Xfrocks\ApiConsumer\Service;

use XF\Entity\ConnectedAccountProvider;
use XF\Service\AbstractService;
use Xfrocks\ApiConsumer\ConnectedAccount\Provider;

class AutoLoginSession extends AbstractService
{
    /**
     * @param string $providerId
     * @return null|string
     */
    public function getRedirectUrl($providerId)
    {
        /** @var ConnectedAccountProvider $provider */
        $provider = $this->em()->find('XF:ConnectedAccountProvider', $providerId);
        if (empty($provider) || $provider->provider_class !== Provider::PROVIDER_CLASS) {
            return null;
        }

        $requestUri = $this->app->request()->getFullRequestUri();
        $this->app->session()->set('connectedAccountRequest', [
            'provider' => $provider->provider_id,
            'returnUrl' => $requestUri,
            'test' => false
        ]);

        $handler = $provider->handler;
        $oauth = $handler->getOAuth($handler->getOAuthConfig($provider));
        $url = $oauth->getAuthorizationUri(['guest_redirect_uri' => $requestUri])->getAbsoluteUri();

        return $url;
    }
}
