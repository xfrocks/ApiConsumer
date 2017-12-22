<?php

namespace Xfrocks\ApiConsumer\XF\Service\User;

class Registration extends XFCP_Registration
{
    protected $isApiConsumerAutoRegistration = false;

    public function setApiConsumerAutoRegistration()
    {
        $this->isApiConsumerAutoRegistration = true;
    }

    protected function sendRegistrationContact()
    {
        if ($this->isApiConsumerAutoRegistration) {
            return;
        }

        parent::sendRegistrationContact();
    }
}
