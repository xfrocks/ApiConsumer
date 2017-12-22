<?php

namespace Xfrocks\ApiConsumer\ConnectedAccount;

use XF\ConnectedAccount\Provider\AbstractProvider;
use XF\Entity\ConnectedAccountProvider;

class Provider extends AbstractProvider
{
    const PROVIDER_CLASS = 'Xfrocks\ApiConsumer:Provider';
    const PROVIDER_ID_PREFIX = 'bdapi_';
    const PROVIDERS_OPTION_VERSION = 2017121401;

    /**
     * @var \XF\Entity\ConnectedAccountProvider[]
     */
    protected static $providers = null;

    /**
     * @param int $length
     * @return string
     */
    public static function getRandomProviderId($length = 6)
    {
        return \XF::generateRandomString($length);
    }

    public function getDescription()
    {
        $provider = $this->getProviderArrayFromOption();
        if ($provider !== null) {
            return $provider['options']['description'];
        }

        return parent::getDescription();
    }

    /**
     * @return array|null
     */
    public function getProviderArrayFromOption()
    {
        $providers = \XF::options()->bdapi_consumer_providers;
        foreach ($providers as $provider) {
            if ($provider['provider_id'] === $this->providerId) {
                return $provider;
            }
        }

        return null;
    }

    public function getOAuthConfig(ConnectedAccountProvider $provider, $redirectUri = null)
    {
        return $this->getOAuthConfigFromOptions($provider->options, $redirectUri ?: $this->getRedirectUri($provider));
    }

    /**
     * @param array $options
     * @param string $redirectUri
     * @return array
     */
    public function getOAuthConfigFromOptions($options, $redirectUri)
    {
        return [
            'key' => $options['app_id'],
            'secret' => $options['app_secret'],
            'scopes' => [Service::SCOPE_READ],
            'root' => $options['root'],
            'redirect' => $redirectUri,
            'title' => $options['app_name'],
            'description' => isset($options['description']) ? $options['description'] : ''
        ];
    }

    public function getOAuthServiceName()
    {
        return $this->providerId;
    }

    public function getProviderDataClass()
    {
        return $this->providerId;
    }

    public function getTestTemplateName()
    {
        return 'admin:bdapi_consumer_connected_account_provider_test';
    }

    public function getTitle()
    {
        $provider = $this->getProviderArrayFromOption();
        if ($provider !== null) {
            return $provider['options']['app_name'];
        }

        return parent::getTitle();
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

    public function verifyConfig(array &$options, &$error = null)
    {
        $originalOptions = $options;

        $verified = parent::verifyConfig($options, $error);

        if ($verified) {
            if (!$this->verifyRoot($options)) {
                $error = \XF::phrase('bdapi_consumer_root_x_cannot_be_recognized', ['root' => $options['root']]);
                return false;
            }

            $options['description'] = $originalOptions['description'];
            $options['auto_login_js'] = !empty($originalOptions['auto_login_js']);
        }

        return $verified;
    }

    /**
     * @param array $options
     * @return bool
     */
    public function verifyRoot(array &$options)
    {
        $options['root'] = preg_replace('#index\.php$#', '', $options['root']);
        $options['root'] = rtrim($options['root'], '/') . '/';

        /** @var Service $service */
        $service = $this->getOAuth($this->getOAuthConfigFromOptions($options, ''));
        $client = \XF::app()->http()->client();
        $testUrl = sprintf(
            '%s/index.php?oauth_token=%s',
            rtrim($options['root'], '/'),
            $service->generateOneTimeToken()
        );

        try {
            $response = $client->get($testUrl)->json();
        } catch (\Exception $e) {
            return false;
        }

        if (empty($response['system_info']['api_revision']) || empty($response['system_info']['api_modules'])) {
            return false;
        }

        $options['apiRevision'] = $response['system_info']['api_revision'];
        $options['apiModules'] = $response['system_info']['api_modules'];
        // TODO: check for specific api revision / module version?

        return true;
    }

    protected function isConfigured(array $options)
    {
        foreach (array_keys($this->getDefaultOptions()) as $key) {
            if (empty($options[$key])) {
                return false;
            }
        }

        return true;
    }

    public function getDefaultOptions()
    {
        return [
            'app_name' => '',
            'app_id' => '',
            'app_secret' => '',

            'root' => ''
        ];
    }
}
