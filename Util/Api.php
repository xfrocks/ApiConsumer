<?php

namespace Xfrocks\ApiConsumer\Util;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\TransferException;

class Api
{
    /**
     * @param array $provider
     * @param array $data
     * @param string $prefix
     * @return bool
     */
    public static function verifyJsSdkSignature(array $provider, array $data, $prefix = '_api_data_')
    {
        $str = '';
        $prefixLength = utf8_strlen($prefix);

        $keys = array_keys($data);
        asort($keys);
        foreach ($keys as $key) {
            if (utf8_substr($key, 0, $prefixLength) !== $prefix) {
                continue;
            }

            $keySubstr = substr($key, $prefixLength);
            if ($keySubstr == 'signature') {
                continue;
            }

            $str .= sprintf('%s-%s&', $keySubstr, $data[$key]);
        }
        $str .= $provider['client_secret'];

        $signature = md5($str);
        return isset($data[$prefix . 'signature']) and ($signature === $data[$prefix . 'signature']);
    }

    /**
     * @param $method
     * @param array $provider
     * @param $path
     * @param bool $accessToken
     * @param bool $expectedKey
     * @param array $params
     * @return array|bool|mixed
     */
    protected static function request($method, $path, $accessToken = false, $expectedKey = false, array $params = [])
    {
        $method = strtolower($method);
        if (!in_array($method, ['get', 'post', 'put', 'delete', 'patch'])) {
            return false;
        }

        $options = [];
        if (stripos($path, 'http') !== 0) {
            $options['base_url'] = rtrim(\XF::app()->options()->boardUrl);
        }

        if ($accessToken !== false && !isset($params['oauth_token'])) {
            $params['oauth_token'] = $accessToken;
        }

        try {
            $client = new Client($options);

            /** @var \GuzzleHttp\Message\FutureResponse $response*/
            $response = call_user_func_array(array($client, $method), [$path, $params]);

            $body = $response->getBody();
            $json = @json_decode($body, true);

            if (!is_array($json)) {
                $json = ['_body' => $body];
            }

            if ($expectedKey !== false) {
                if (!isset($json[$expectedKey])) {
                    return false;
                }
            }

            $json['_headers'] = $response->getHeaderAsArray();
            $json['_responseStatus'] = $response->getStatusCode();

            return $json;
        } catch (TransferException $e) {
            return false;
        }
    }
}
