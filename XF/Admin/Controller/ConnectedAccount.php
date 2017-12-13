<?php

namespace Xfrocks\ApiConsumer\XF\Admin\Controller;

use XF\Entity\ConnectedAccountProvider;
use XF\Mvc\ParameterBag;
use Xfrocks\ApiConsumer\ConnectedAccount\Provider;

class ConnectedAccount extends XFCP_ConnectedAccount
{
    public function actionApiConsumerAdd()
    {
        /** @var ConnectedAccountProvider $provider */
        $provider = $this->em()->create('XF:ConnectedAccountProvider');
        $provider->provider_class = Provider::PROVIDER_CLASS;

        $viewParams = [
            'provider' => $provider
        ];

        return $this->view(
            'Xfrocks/ApiConsumer:ConnectedAccount\Add',
            'bdapi_consumer_connected_account_provider_add',
            $viewParams
        );
    }

    public function actionApiConsumerSave(ParameterBag $params)
    {
        $this->assertPostOnly();

        $providerId = $this->filter('provider_id', 'str');
        if (empty($providerId)) {
            $providerId = Provider::getRandomProviderId();
        }

        /** @var ConnectedAccountProvider $provider */
        $provider = $this->em()->create('XF:ConnectedAccountProvider');
        $provider->provider_id = Provider::PROVIDER_ID_PREFIX . $providerId;
        $provider->provider_class = Provider::PROVIDER_CLASS;

        $this->providerSaveProcess($provider)->run();

        return $this->redirect($this->buildLink('connected-accounts') . $this->buildLinkHash($provider->provider_id));
    }
}
