<?php

namespace Xfrocks\ApiConsumer\XF\SubContainer;

use XF\Container;
use Xfrocks\ApiConsumer\ConnectedAccount\Service;

class OAuth extends XFCP_OAuth
{
    /**
     * Use string instead of the constant here to avoid loading the class into memory unnecessary.
     * @see Provider::PROVIDER_ID_PREFIX
     */
    public function initialize()
    {
        parent::initialize();

        $this->container->extendFactory('provider', function ($serviceName, array $config, Container $c, $factory) {
            $providerId = null;
            if (strpos($serviceName, 'bdapi_') === 0) {
                $providerId = $serviceName;
                $serviceName = 'Xfrocks\ApiConsumer:Service';
            }

            /** @var Service $service */
            $service = $factory($serviceName, $config, $c);

            if ($providerId !== null) {
                $service->updateProviderIdAndConfig($providerId, $config);
            }

            return $service;
        });

        $this->container->extendFactory('providerData', function ($class, array $params, Container $c, $factory) {
            if (strpos($params[0], 'bdapi_') === 0) {
                $class = 'Xfrocks\ApiConsumer:ProviderData';
            }

            return $factory($class, $params, $c);
        });
    }
}
