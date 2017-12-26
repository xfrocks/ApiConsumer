<?php

namespace Xfrocks\ApiConsumer\XF\Pub\Controller;

use XF\Entity\ConnectedAccountProvider;
use Xfrocks\ApiConsumer\ControllerPlugin\AutoLogin;
use Xfrocks\ApiConsumer\Service\AutoLoginSession;

class Login extends XFCP_Login
{
    public function actionApiConsumerAutoLogin()
    {
        $this->assertPostOnly();

        $visitor = \XF::visitor();
        if ($visitor->user_id > 0) {
            return $this->noPermission();
        }

        $input = $this->filter([
            'providerId' => 'str',
            'apiData' => 'array'
        ]);

        /** @var ConnectedAccountProvider $provider */
        $provider = $this->assertRecordExists('XF:ConnectedAccountProvider', $input['providerId']);
        /** @var AutoLogin $autoLogin */
        $autoLogin = $this->plugin('Xfrocks\ApiConsumer:AutoLogin');

        $redirect = $this->getDynamicRedirect(null, false);
        return $autoLogin->login($provider, $input['apiData'], $redirect);
    }

    public function view($viewClass = '', $templateName = '', array $params = [])
    {
        if ($viewClass === 'XF:Login\Form' &&
            $templateName === 'login' &&
            ($xfOptions = $this->options()) &&
            $xfOptions->bdapi_consumer_loginRedirect &&
            ($providerId = $xfOptions->bdapi_consumer_autoLoginSession)) {
            /** @var AutoLoginSession $autoLoginSession */
            $autoLoginSession = $this->service('Xfrocks\ApiConsumer:AutoLoginSession');
            $redirect = $this->getDynamicRedirectIfNot($this->buildLink('login'));
            $url = $autoLoginSession->getRedirectUrl($providerId, $redirect);
            if (is_string($url)) {
                return $this->redirect($url, '');
            }
        }

        return parent::view($viewClass, $templateName, $params);
    }
}
