<?php

namespace Xfrocks\ApiConsumer\ControllerPlugin;

use XF\ControllerPlugin\AbstractPlugin;
use XF\ControllerPlugin\Login;
use XF\Entity\ConnectedAccountProvider;
use XF\Entity\UserConnectedAccount;
use XF\Mvc\Reply\Exception;
use XF\Mvc\Reply\Redirect;
use Xfrocks\ApiConsumer\ConnectedAccount\Provider;
use Xfrocks\ApiConsumer\ConnectedAccount\Service;
use Xfrocks\ApiConsumer\Listener;

class AutoLogin extends AbstractPlugin
{
    /**
     * @param ConnectedAccountProvider $provider
     * @param array $apiData
     * @param string $redirect
     * @return \XF\Mvc\Reply\AbstractReply
     */
    public function login($provider, $apiData, $redirect)
    {
        /** @var Provider $handler */
        $handler = $provider->handler;
        /** @var Service $service */
        $service = $handler->getOAuth($handler->getOAuthConfig($provider));

        $this->app->session()->set(Listener::SESSION_KEY_LATEST_AUTO_LOGIN_TIME, time());

        if (empty($apiData['user_id']) || !$service->verifyJsSdkSignature($apiData)) {
            return $this->loginFail();
        }

        /** @var UserConnectedAccount $userConnectedAccount */
        $userConnectedAccount = $this->em()->findOne('XF:UserConnectedAccount', [
            'provider' => $provider->provider_id,
            'provider_key' => $apiData['user_id']
        ], ['User']);

        if (!$userConnectedAccount || !$userConnectedAccount->User) {
            return $this->registerOrFail($provider, $redirect);
        }

        $token = null;
        try {
            $token = $service->getFreshAccessTokenForUser($provider, $userConnectedAccount);
        } catch (\Exception $e) {
            \XF::logException($e);
        }
        if (empty($token)) {
            return $this->registerOrFail($provider, $redirect);
        }

        /** @var Login $loginPlugin */
        $loginPlugin = $this->plugin('XF:Login');
        $user = $userConnectedAccount->User;

        try {
            $loginPlugin->triggerIfTfaConfirmationRequired(
                $user,
                $this->buildLink('login/two-step', null, [
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
            return $this->message($tfaMessage);
        }

        $loginPlugin->completeLogin($user, true);

        $this->app->session()->set(Listener::SESSION_KEY_LOGGED_IN_PROVIDER_ID, $provider->provider_id);

        $loginMessage = \XF::phrase('bdapi_consumer_auto_login_with_x_succeeded_y', [
            'username' => $user->username,
            'provider' => $provider->getTitle()
        ]);
        return $this->redirect($redirect, $loginMessage);
    }

    /**
     * @return \XF\Mvc\Reply\AbstractReply
     */
    public function loginFail()
    {
        return $this->notFound();
    }

    /**
     * @param ConnectedAccountProvider $provider
     * @param string $redirect
     * @return \XF\Mvc\Reply\AbstractReply
     */
    public function registerOrFail($provider, $redirect)
    {
        $autoRegister = $this->options()->bdapi_consumer_autoRegister;
        if (!empty($autoRegister) && $autoRegister !== 'off') {
            $linkParams = ['setup' => 1, '_xfRedirect' => $redirect];
            $link = $this->buildLink('register/connected-accounts', $provider, $linkParams);
            $message = \XF::phrase(
                'bdapi_consumer_being_auto_login_auto_register_x',
                ['provider' => $provider->getTitle()]
            );

            return $this->redirect($link, $message);
        }

        return $this->loginFail();
    }
}
