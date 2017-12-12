<?php

namespace Xfrocks\ApiConsumer\Util;

class Option
{
    /**
     * @param string $key
     * @return mixed|null
     */
    public static function get($key)
    {
        $xfOptions = \XF::options();
        $key = sprintf('bdapi_consumer_%s', $key);
        if ($xfOptions->offsetExists($key)) {
            return $xfOptions->offsetGet($key);
        }

        return null;
    }

    /**
     * @param int $length
     * @return string
     */
    public static function getRandomProviderId($length = 12)
    {
        return 'bdapi_' . \XF::generateRandomString($length);
    }
}
