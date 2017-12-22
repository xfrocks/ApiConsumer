<?php

namespace Xfrocks\ApiConsumer\Service;

use XF\Entity\ConnectedAccountProvider;
use XF\Entity\UserConnectedAccount;
use XF\Mvc\Entity\Finder;
use XF\Service\AbstractService;
use Xfrocks\ApiConsumer\ConnectedAccount\Provider;
use Xfrocks\ApiConsumer\ConnectedAccount\Service;
use Xfrocks\ApiConsumer\Listener;

class RedirectBuilder extends AbstractService
{
    /**
     * @param array $providers
     * @param string $action
     * @param string $url
     * @param int $userId
     * @return string
     */
    public function build($providers, $action, $url, $userId)
    {
        $userConnectedAccounts = $this->getUserConnectedAccountFinder($userId)->fetch();
        if (count($userConnectedAccounts) === 0) {
            return $url;
        }

        if (!in_array($action, ['login', 'logout'], true)) {
            return $url;
        }

        $em = $this->em();
        $router = $this->app->router('public');
        $autoLoginProviderId = $this->app->session()->get(Listener::SESSION_KEY_LOGGED_IN_PROVIDER_ID);
        $url = $this->app->request()->convertToAbsoluteUri($url);

        /** @var UserConnectedAccount $userConnectedAccount */
        foreach ($userConnectedAccounts as $userConnectedAccount) {
            if ($action === 'login' && $userConnectedAccount->provider === $autoLoginProviderId) {
                continue;
            }
            if (!isset($providers[$userConnectedAccount->provider])) {
                continue;
            }

            $values = $providers[$userConnectedAccount->provider];
            foreach ($values as &$valueRef) {
                if (is_array($valueRef)) {
                    $valueRef = json_encode($valueRef);
                }
            }
            /** @var ConnectedAccountProvider $provider */
            $provider = $em->instantiateEntity(
                'XF:ConnectedAccountProvider',
                $values
            );

            /** @var Provider $handler */
            $handler = $provider->handler;
            /** @var Service $service */
            $service = $handler->getOAuth($handler->getOAuthConfig($provider));

            $token = null;
            try {
                $token = $service->getFreshAccessTokenForUser($provider, $userConnectedAccount);
            } catch (\Exception $e) {
                \XF::logException($e);
            }
            if (empty($token)) {
                continue;
            }

            $ott = $service->generateOneTimeToken($userConnectedAccount->provider_key, $token->getAccessToken());
            $gotoUrl = $router->buildLink('full:goto/api-consumer/logout', null, [
                'redirect' => $url,
                'hash' => $this->calculateHash($url)
            ]);
            if (parse_url($url, PHP_URL_HOST) === parse_url($gotoUrl, PHP_URL_HOST)) {
                $gotoUrl = $url;
            }
            $url = sprintf(
                '%s/index.php?tools/%s&oauth_token=%s&redirect_uri=%s',
                rtrim($provider->options['root'], '/'),
                $action,
                rawurlencode($ott),
                rawurlencode($gotoUrl)
            );
        }

        return $url;
    }

    /**
     * @param int $userId
     * @return Finder
     */
    protected function getUserConnectedAccountFinder($userId)
    {
        return $this->em()->getFinder('XF:UserConnectedAccount')
            ->where('user_id', $userId)
            ->with('User');
    }

    /**
     * @param string $url
     * @return string
     */
    public function calculateHash($url)
    {
        return md5($url . $this->app->config('globalSalt'));
    }
}
