<?php

namespace Xfrocks\ApiConsumer\XF\Pub\Controller;

use XF\Mvc\ParameterBag;
use Xfrocks\ApiConsumer\ConnectedAccount\Provider;

class Register extends XFCP_Register
{
    private $xfrocksApiConsumerAction = null;

    public function preDispatch($action, ParameterBag $params)
    {
        if ($params->offsetExists('provider_id') && stripos($action, 'ConnectedAccount') !== false) {
            $this->xfrocksApiConsumerAction = [
                'action' => $action,
                'providerId' => $params->get('provider_id')
            ];
        }

        parent::preDispatch($action, $params);
    }

    /**
     * Use string instead of the constant here to avoid loading the class into memory unnecessary.
     * @see Provider::PROVIDER_CLASS
     */
    protected function assertRegistrationActive()
    {
        if (is_array($this->xfrocksApiConsumerAction)) {
            $bypassRegistrationActive = $this->options()->bdapi_consumer_bypassRegistrationActive;
            if ($bypassRegistrationActive) {
                $provider = $this->assertProviderExists($this->xfrocksApiConsumerAction['providerId']);
                if ($provider->provider_class === 'Xfrocks\ApiConsumer:Provider') {
                    return;
                }
            }
        }

        parent::assertRegistrationActive();
    }
}
