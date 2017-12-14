<?php

namespace Xfrocks\ApiConsumer\Option;

use XF\Entity\ConnectedAccountProvider;
use XF\Entity\Option;
use XF\Option\AbstractOption;
use Xfrocks\ApiConsumer\XF\Repository\ConnectedAccount;

class Provider extends AbstractOption
{
    /**
     * @param Option $option
     * @param array $htmlParams
     * @return string
     */
    public static function renderSelect($option, $htmlParams)
    {
        $data = self::getSelectData($option, $htmlParams);

        return self::getTemplater()->formSelectRow($data['controlOptions'], $data['choices'], $data['rowOptions']);
    }

    /**
     * @param Option $option
     * @param array $htmlParams
     * @return array
     */
    protected static function getSelectData($option, $htmlParams)
    {
        /** @var ConnectedAccount $repo */
        $repo = \XF::repository('XF:ConnectedAccount');

        $providers = $repo->findProvidersForList()->fetch();
        $choices = [['value' => '', 'label' => '']];

        /** @var ConnectedAccountProvider $provider */
        foreach ($providers as $provider) {
            if ($provider->provider_class !== \Xfrocks\ApiConsumer\ConnectedAccount\Provider::PROVIDER_CLASS) {
                continue;
            }
            if (!$provider->isUsable()) {
                continue;
            }

            $choices[] = [
                'value' => $provider->provider_id,
                'label' => sprintf('%s (%s)', $provider->options['app_name'], $provider->provider_id)
            ];
        }

        return [
            'choices' => $choices,
            'controlOptions' => self::getControlOptions($option, $htmlParams),
            'rowOptions' => self::getRowOptions($option, $htmlParams)
        ];
    }

    /**
     * @param string $providerId
     * @param Option $option
     * @return bool
     */
    public static function verifyProviderId(&$providerId, $option)
    {
        if ($providerId === '') {
            return true;
        }

        /** @var ConnectedAccountProvider $provider */
        $provider = \XF::em()->find('XF:ConnectedAccountProvider', $providerId);
        if (empty($provider)) {
            return false;
        }

        return true;
    }
}
