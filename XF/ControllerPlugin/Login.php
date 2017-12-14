<?php

namespace Xfrocks\ApiConsumer\XF\ControllerPlugin;

use Xfrocks\ApiConsumer\Listener;

class Login extends XFCP_Login
{
    public function completeLogin(\XF\Entity\User $user, $remember)
    {
        $ucafpi = $this->session->get(Listener::SESSION_KEY_USER_CONNECTED_ACCOUNT_FROM_PROVIDER_ID);

        parent::completeLogin($user, $remember);

        if (is_array($ucafpi) &&
            isset($ucafpi['userId']) &&
            isset($ucafpi['providerId']) &&
            $ucafpi['userId'] === $user->user_id) {
            $this->session->set(Listener::SESSION_KEY_LOGGED_IN_PROVIDER_ID, $ucafpi['providerId']);
        }
    }
}
