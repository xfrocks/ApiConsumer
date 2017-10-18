<?php

namespace Xfrocks\ApiConsumer\XF\Admin\Controller;

use XF\Mvc\ParameterBag;
use Xfrocks\ApiConsumer\Util\Option;

class ConnectedAccount extends XFCP_ConnectedAccount
{
    public function actionApiConsumerAdd()
    {
        /** @var \XF\Entity\ConnectedAccountProvider $provider */
        $provider = $this->em()->create('XF:ConnectedAccountProvider');
        $provider->provider_id = Option::getRandomProviderId();
        $provider->provider_class = $this->getProviderClassShortName();

        $viewParams = [
            'provider' => $provider
        ];

        return $this->view(
            'XF:ConnectedAccount\Add',
            'bdapi_consumer_connected_account_provider_add',
            $viewParams
        );
    }

    public function actionApiConsumerSave(ParameterBag $params)
    {
        $this->assertPostOnly();

        /** @var \XF\Entity\ConnectedAccountProvider $provider */
        $provider = $this->em()->create('XF:ConnectedAccountProvider');
        $provider->provider_id = $params->provider_id;
        $provider->provider_class = $this->getProviderClassShortName();

        $this->providerSaveProcess($provider)->run();

        return $this->redirect($this->buildLink('connected-accounts') . $this->buildLinkHash($provider->provider_id));
    }

    protected function getProviderClassShortName()
    {
        return 'Xfrocks\ApiConsumer:Provider\Common';
    }
}