<?php

namespace Xfrocks\ApiConsumer\Util;

use XF\Option\AbstractOption;

class Option extends AbstractOption
{
    /**
     * @param string $key
     * @return mixed|null
     */
    public static function get($key)
    {
        $key = sprintf('bdapi_consumer_%s', $key);
        if (\XF::options()->offsetExists($key)) {
            return \XF::options()->offsetGet($key);
        }

        return null;
    }
}