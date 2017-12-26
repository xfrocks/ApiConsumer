<?php

namespace Xfrocks\ApiConsumer\Service;

use XF\ControllerPlugin\Login;
use XF\Entity\ConnectedAccountProvider;
use XF\Entity\UserConnectedAccount;
use XF\Mvc\Reply\Exception;
use XF\Mvc\Reply\Redirect;
use XF\Pub\Controller\AbstractController;
use XF\Service\AbstractService;
use Xfrocks\ApiConsumer\ConnectedAccount\Provider;
use Xfrocks\ApiConsumer\ConnectedAccount\Service;
use Xfrocks\ApiConsumer\Listener;

class AutoLogin extends AbstractService
{
    public function getProviders()
    {
        $container = $this->app->container();
        if (!$container->offsetExists(__METHOD__)) {
            $container->set(__METHOD__, function () {
                $result = [];
                $providers = \XF::app()->options()->bdapi_consumer_providers;
                foreach ($providers as $provider) {
                    if (empty($provider['isUsable']) ||
                        empty($provider['options']['auto_login_js'])) {
                        continue;
                    }

                    $result[$provider['provider_id']] = $provider;
                }

                return $result;
            }, true);
        }

        return $container->offsetGet(__METHOD__);
    }

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

    /**
     * @param AbstractController $controller
     * @param ConnectedAccountProvider $provider
     * @param array $apiData
     * @return \XF\Mvc\Reply\AbstractReply
     */
    public function login($controller, $provider, $apiData)
    {
        /** @var Provider $handler */
        $handler = $provider->handler;
        /** @var Service $service */
        $service = $handler->getOAuth($handler->getOAuthConfig($provider));

        $this->app->session()->set(Listener::SESSION_KEY_LATEST_AUTO_LOGIN_TIME, time());

        if (empty($apiData['user_id']) || !$service->verifyJsSdkSignature($apiData)) {
            return $this->loginFail($controller);
        }

        $redirect = $controller->getDynamicRedirect(null, false);

        /** @var UserConnectedAccount $userConnectedAccount */
        $userConnectedAccount = $this->em()->findOne('XF:UserConnectedAccount', [
            'provider' => $provider->provider_id,
            'provider_key' => $apiData['user_id']
        ], ['User']);

        if (!$userConnectedAccount || !$userConnectedAccount->User) {
            return $this->registerOrFail($controller, $provider, $redirect);
        }

        $token = null;
        try {
            $token = $service->getFreshAccessTokenForUser($provider, $userConnectedAccount);
        } catch (\Exception $e) {
            \XF::logException($e);
        }
        if (empty($token)) {
            return $this->registerOrFail($controller, $provider, $redirect);
        }

        /** @var Login $login */
        $login = $controller->plugin('XF:Login');
        $user = $userConnectedAccount->User;

        try {
            $login->triggerIfTfaConfirmationRequired(
                $user,
                $controller->buildLink('login/two-step', null, [
                    '_xfRedirect' => $redirect,
                    'remember' => 1
                ])
            );
        } catch (Exception $e) {
            /** @var Redirect $tfaRedirect */
            $tfaRedirect = $e->getReply();
            $tfaMessage = \XF::phrase('bdapi_consumer_auto_login_user_x_requires_tfa', [
                'username' => $user->username,
                'twoStepLink' => $tfaRedirect->getUrl()
            ]);
            return $controller->message($tfaMessage);
        }

        $login->completeLogin($user, true);

        $this->app->session()->set(Listener::SESSION_KEY_LOGGED_IN_PROVIDER_ID, $provider->provider_id);

        $loginMessage = \XF::phrase('bdapi_consumer_auto_login_with_x_succeeded_y', [
            'username' => $user->username,
            'provider' => $provider->getTitle()
        ]);
        return $controller->redirect($redirect, $loginMessage);
    }

    /**
     * @param AbstractController $controller
     * @return \XF\Mvc\Reply\AbstractReply
     */
    public function loginFail($controller)
    {
        return $controller->notFound();
    }

    /**
     * @param AbstractController $controller
     * @param ConnectedAccountProvider $provider
     * @param string $redirect
     * @return \XF\Mvc\Reply\AbstractReply
     */
    public function registerOrFail($controller, $provider, $redirect)
    {
        $autoRegister = $this->app->options()->bdapi_consumer_autoRegister;
        if (empty($autoRegister) || $autoRegister === 'off') {
            return $this->loginFail($controller);
        }

        $linkParams = ['setup' => 1, '_xfRedirect' => $redirect];
        $link = $controller->buildLink('register/connected-accounts', $provider, $linkParams);
        $message = \XF::phrase(
            'bdapi_consumer_being_auto_login_auto_register_x',
            ['provider' => $provider->getTitle()]
        );

        return $controller->redirect($link, $message);
    }
}
