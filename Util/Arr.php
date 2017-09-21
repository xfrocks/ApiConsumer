<?php

namespace Xfrocks\ApiConsumer\Util;

class Arr extends \XF\Util\Arr
{
    public static function get($array, $key, $default = null)
    {
        if (!is_array($array)) {
            return $array;
        }

        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }
}