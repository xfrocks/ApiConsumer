<?php

namespace Xfrocks\ApiConsumer\ConnectedAccount\Provider;

use XF\ConnectedAccount\Provider\AbstractProvider;
use XF\Entity\ConnectedAccountProvider;

class Common extends AbstractProvider
{
    protected static $providers = null;

    public function getOAuthServiceName()
    {
        return 'Xfrocks\ApiConsumer\OAuth2\Service\Common';
    }

    public function getProviderDataClass()
    {
        return 'Xfrocks\ApiConsumer:ProviderData\Common';
    }

    public function getDefaultOptions()
    {
        return [
            'root' => '',
            'app_name' => '',
            'app_id' => '',
            'app_secret' => '',
            'scopes' => []
        ];
    }

    public function getOAuthConfig(ConnectedAccountProvider $provider, $redirectUri = null)
    {
        return [
            'key' => $provider->options['app_id'],
            'secret' => $provider->options['app_secret'],
            'scopes' => explode(',', $provider->options['scopes']),
            'root' => $provider->options['root'],
            'redirect' => $redirectUri ?: $this->getRedirectUri($provider),
            'title' => $provider->options['app_name'],
            'description' => $provider->options['description']
        ];
    }

    public function getTitle()
    {
        $provider = $this->getProvider();
        if ($provider !== null) {
            $config = $this->getOAuthConfig($provider);
            return $config['title'];
        }

        return parent::getTitle();
    }

    public function getDescription()
    {
        $provider = $this->getProvider();
        if ($provider !== null) {
            $config = $this->getOAuthConfig($provider);
            return $config['description'];
        }

        return parent::getDescription();
    }

    public function verifyConfig(array &$options, &$error = null)
    {
        if (!empty($options['scopes'])) {
            $options['scopes'] = implode(',', $options['scopes']);
        }

        return parent::verifyConfig($options, $error);
    }

    public function getOAuth(array $config)
    {
        /** @var \Xfrocks\ApiConsumer\OAuth2\Service\Common $oauth */
        $oauth = parent::getOAuth($config);
        $oauth->setBaseApiUrl($config['root']);

        return $oauth;
    }

    /**
     * @return null|\XF\Entity\ConnectedAccountProvider
     */
    public function getProvider()
    {
        if (static::$providers === null) {
            static::$providers = \XF::repository('XF:ConnectedAccount')->findProvidersForList()->fetch();
        }

        foreach (static::$providers as $providerRef) {
            if ($providerRef->provider_id == $this->providerId) {
                return $providerRef;
            }
        }

        return null;
    }

    protected function getEffectiveOptions(array $options)
    {
        $options = parent::getEffectiveOptions($options);
        if (!empty($options['scopes'])) {
            $scopes = explode(',', $options['scopes']);
            $options['scopes'] = array();

            foreach ($scopes as $scopeRef) {
                $options['scopes'][$scopeRef] = 1;
            }
        }

        return $options;
    }
}