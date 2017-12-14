<?php

namespace Xfrocks\ApiConsumer\XF\Pub\Controller;

use XF\Entity\ConnectedAccountProvider;
use Xfrocks\ApiConsumer\ControllerPlugin\AutoLogin;

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
}
