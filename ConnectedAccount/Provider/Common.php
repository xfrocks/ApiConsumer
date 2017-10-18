<?php

namespace Xfrocks\ApiConsumer\ConnectedAccount\Provider;

use XF\ConnectedAccount\Provider\AbstractProvider;
use XF\Entity\ConnectedAccountProvider;
use Xfrocks\ApiConsumer\OAuth2\Service\Common as OAuthCommon;

class Common extends AbstractProvider
{
    /**
     * @var \XF\Entity\ConnectedAccountProvider[]
     */
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
            'app_secret' => ''
        ];
    }

    public function getOAuthConfig(ConnectedAccountProvider $provider, $redirectUri = null)
    {
        return [
            'key' => $provider->options['app_id'],
            'secret' => $provider->options['app_secret'],
            'scopes' => $this->getOAuthRequestScopes(),
            'root' => $provider->options['root'],
            'redirect' => $redirectUri ?: $this->getRedirectUri($provider),
            'title' => $provider->options['app_name'],
            'description' => isset($provider->options['description']) ? $provider->options['description'] : ''
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

    /**
     * @param array $config
     * @return \Xfrocks\ApiConsumer\OAuth2\Service\Common
     */
    public function getOAuth(array $config)
    {
        /** @var \Xfrocks\ApiConsumer\OAuth2\Service\Common $oauth */
        $oauth = parent::getOAuth($config);
        $oauth->setBaseApiUrl($config['root']);

        return $oauth;
    }

    public function renderConfig(ConnectedAccountProvider $provider)
    {
        return \XF::app()->templater()->renderTemplate('admin:connected_account_provider_bdapi_consumer', [
            'options' => $this->getEffectiveOptions($provider->options)
        ]);
    }

    public function renderAssociated(ConnectedAccountProvider $provider, \XF\Entity\User $user)
    {
        return \XF::app()->templater()->renderTemplate('public:connected_account_associated_bdapi_consumer', [
            'provider' => $provider,
            'user' => $user,
            'providerData' => $provider->getUserInfo($user),
            'connectedAccounts' => $user->Profile->connected_accounts
        ]);
    }

    public function getTestTemplateName()
    {
        return 'admin:connected_account_provider_test_bdapi_consumer';
    }

    protected function getOAuthRequestScopes()
    {
        return [
            OAuthCommon::SCOPE_READ,
            OAuthCommon::SCOPE_POST
        ];
    }

    /**
     * @return null|\XF\Entity\ConnectedAccountProvider
     */
    public function getProvider()
    {
        if (static::$providers === null) {
            static::$providers = \XF::repository('XF:ConnectedAccount')
                ->findProvidersForList()
                ->fetch();
        }

        foreach (static::$providers as $providerRef) {
            if ($providerRef->provider_id == $this->providerId) {
                return $providerRef;
            }
        }

        return null;
    }
}