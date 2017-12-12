<?php

namespace Xfrocks\ApiConsumer\ConnectedAccount;

use OAuth\OAuth2\Token\StdOAuth2Token;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\Uri;
use OAuth\OAuth2\Service\AbstractService;

class Service extends AbstractService
{
    const SCOPE_READ = 'read';
    const SCOPE_POST = 'post';
    const SCOPE_USERCP = 'usercp';
    const SCOPE_CONVERSTATE = 'converstate';
    const SCOPE_ADMINCP = 'admincp';

    protected $providerId = null;

    public function getAuthorizationEndpoint()
    {
        if ($this->baseApiUri === null) {
            throw new \RuntimeException('Base API URI must be setup before usage');
        }

        return new Uri($this->baseApiUri . 'index.php?oauth/authorize');
    }

    public function getAccessTokenEndpoint()
    {
        if ($this->baseApiUri === null) {
            throw new \RuntimeException('Base API URI must be setup before usage');
        }

        return new Uri($this->baseApiUri . 'index.php?oauth/token');
    }

    public function service()
    {
        if ($this->providerId === null) {
            throw new \RuntimeException('Provider ID must be setup before usage');
        }

        return $this->providerId;
    }

    public function updateProviderIdAndConfig($providerId, array $config)
    {
        $this->providerId = $providerId;

        $baseApiUri = preg_replace('#index\.php$#', '', $config['root']);
        $baseApiUri = rtrim($baseApiUri, '/') . '/';
        $baseApiUri = new Uri($baseApiUri);
        $this->baseApiUri = $baseApiUri;
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
