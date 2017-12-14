<?php

namespace Xfrocks\ApiConsumer\Option;

use XF\Entity\Option;
use XF\Option\AbstractOption;

class Providers extends AbstractOption
{
    /**
     * @param Option $option
     * @param array $htmlParams
     * @return string
     */
    public static function renderOption($option, $htmlParams)
    {
        return self::renderAutoLoginJs($option, $htmlParams);
    }

    /**
     * @param Option $option
     * @return array
     */
    protected static function getProviders($option)
    {
        $providers = [];
        foreach ($option->option_value as $provider) {
            if (empty($provider['isUsable'])) {
                continue;
            }
            $providers[] = $provider;
        }
        return $providers;
    }

    /**
     * @param Option $option
     * @param array $htmlParams
     * @return string
     */
    protected static function renderAutoLoginJs($option, $htmlParams)
    {
        $choices = [];
        foreach (self::getProviders($option) as $provider) {
            $choices[] = [
                'checked' => !empty($provider['options']['auto_login_js']),
                'disabled' => true,
                'label' => $provider['options']['app_name'],
                'value' => $provider['provider_id']
            ];
        }

        $rowOptions = [
            'explain' => \XF::phrase('bdapi_consumer_autoLoginJs_explain'),
            'hint' => $htmlParams['hintHtml'],
            'label' => \XF::phrase('bdapi_consumer_autoLoginJs')
        ];

        return self::getTemplater()->formCheckBoxRow([], $choices, $rowOptions);
    }
}
