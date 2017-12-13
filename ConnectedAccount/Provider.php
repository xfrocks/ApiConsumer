<?php

namespace Xfrocks\ApiConsumer\ConnectedAccount;

use XF\ConnectedAccount\Provider\AbstractProvider;
use XF\Entity\ConnectedAccountProvider;
use XF\Entity\User;
use Xfrocks\ApiConsumer\XF\ConnectedAccount\Storage\StorageState;

class Provider extends AbstractProvider
{
    const PROVIDER_ID_PREFIX = 'bdapi_';

    /**
     * @var \XF\Entity\ConnectedAccountProvider[]
     */
    protected static $providers = null;

    public function getOAuthServiceName()
    {
        return $this->providerId;
    }

    public function getProviderDataClass()
    {
        return $this->providerId;
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

    public function renderConfig(ConnectedAccountProvider $provider)
    {
        $template = 'admin:bdapi_consumer_connected_account_provider';
        $params = ['options' => $this->getEffectiveOptions($provider->options)];
        return \XF::app()->templater()->renderTemplate($template, $params);
    }

    public function renderAssociated(ConnectedAccountProvider $provider, \XF\Entity\User $user)
    {
        $template = 'public:bdapi_consumer_connected_account_associated';
        $params = [
            'provider' => $provider,
            'user' => $user,
            'providerData' => $provider->getUserInfo($user),
            'connectedAccounts' => $user->Profile->connected_accounts
        ];

        return \XF::app()->templater()->renderTemplate($template, $params);
    }

    public function getTestTemplateName()
    {
        return 'admin:bdapi_consumer_connected_account_provider_test';
    }

    protected function getOAuthRequestScopes()
    {
        return [
            Service::SCOPE_READ,
            Service::SCOPE_POST
        ];
    }

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

    public static function getRandomProviderId($length = 6)
    {
        return \XF::generateRandomString($length);
    }
}
