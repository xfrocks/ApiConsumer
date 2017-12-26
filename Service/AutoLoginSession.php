<?php

namespace Xfrocks\ApiConsumer\Service;

use XF\Entity\ConnectedAccountProvider;
use XF\Service\AbstractService;
use Xfrocks\ApiConsumer\ConnectedAccount\Provider;

class AutoLoginSession extends AbstractService
{
    /**
     * @param string $providerId
     * @param null|string $returnUrl
     * @return null|string
     */
    public function getRedirectUrl($providerId, $returnUrl = null)
    {
        /** @var ConnectedAccountProvider $provider */
        $provider = $this->em()->find('XF:ConnectedAccountProvider', $providerId);
        if (empty($provider) || $provider->provider_class !== Provider::PROVIDER_CLASS) {
            return null;
        }

        $authUriParams = [];
        if (empty($returnUrl)) {
            $returnUrl = $this->app->request()->getFullRequestUri();
            $authUriParams['guest_redirect_uri'] = $returnUrl;
        }
        $this->app->session()->set('connectedAccountRequest', [
            'provider' => $provider->provider_id,
            'returnUrl' => $returnUrl,
            'test' => false
        ]);

        $handler = $provider->handler;
        $oauth = $handler->getOAuth($handler->getOAuthConfig($provider));
        $url = $oauth->getAuthorizationUri($authUriParams)->getAbsoluteUri();

        return $url;
    }
}
