<?php

namespace Xfrocks\ApiConsumer\OAuth2\Service;

use OAuth\OAuth2\Token\StdOAuth2Token;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\Uri;
use OAuth\OAuth2\Service\AbstractService;

class Common extends AbstractService
{
    const SCOPE_READ = 'read';
    const SCOPE_POST = 'post';
    const SCOPE_USERCP = 'usercp';
    const SCOPE_CONVERSTATE = 'converstate';
    const SCOPE_ADMINCP = 'admincp';

    public function getAuthorizationEndpoint()
    {
        return new Uri(rtrim($this->baseApiUri, '/') . '/oauth/authorize');
    }

    public function getAccessTokenEndpoint()
    {
        return new Uri(rtrim($this->baseApiUri, '/') . '/oauth/token');
    }

    public function setBaseApiUrl($url)
    {
        $this->baseApiUri = new Uri(rtrim($url, '/') . '/');

        return $this;
    }

    /**
     * @param string $responseBody
     * @return StdOAuth2Token
     * @throws TokenResponseException
     */
    protected function parseAccessTokenResponse($responseBody)
    {
        $data = @json_decode($responseBody, true);

        if (null === $data || !is_array($data)) {
            throw new TokenResponseException('Unable to parse response.');
        } elseif (isset($data['error'])) {
            throw new TokenResponseException('Error in retrieving token: "' . $data['error'] . '"');
        }

        $token = new StdOAuth2Token();
        $token->setAccessToken($data['access_token']);

        if (isset($data['expires_in'])) {
            $token->setLifeTime($data['expires_in']);
        }

        if (isset($data['refresh_token'])) {
            $token->setRefreshToken($data['refresh_token']);
            unset($data['refresh_token']);
        }

        unset($data['access_token']);
        unset($data['expires_in']);

        $token->setExtraParams($data);

        return $token;
    }

    protected function getAuthorizationMethod()
    {
        return static::AUTHORIZATION_METHOD_HEADER_BEARER;
    }
}