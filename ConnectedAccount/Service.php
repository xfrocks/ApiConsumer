<?php

namespace Xfrocks\ApiConsumer\ConnectedAccount;

use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\Uri;
use OAuth\Common\Token\TokenInterface;
use OAuth\OAuth2\Service\AbstractService;
use OAuth\OAuth2\Token\StdOAuth2Token;
use XF\Entity\ConnectedAccountProvider;
use XF\Entity\UserConnectedAccount;

class Service extends AbstractService
{
    const SCOPE_READ = 'read';

    protected $providerId = null;

    /**
     * @param int $userId
     * @param string $accessToken
     * @param int $ttl
     * @return string
     */
    public function generateOneTimeToken($userId = 0, $accessToken = '', $ttl = 300)
    {
        $timestamp = time() + $ttl;
        $once = md5($userId . $timestamp . $accessToken . $this->credentials->getConsumerSecret());
        $ott = sprintf('%d,%d,%s,%s', $userId, $timestamp, $once, $this->credentials->getConsumerId());

        return $ott;
    }

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

    /**
     * @param ConnectedAccountProvider $provider
     * @param UserConnectedAccount $userConnectedAccount
     * @return TokenInterface
     * @throws \Exception
     * @throws \OAuth\OAuth2\Service\Exception\MissingRefreshTokenException
     * @throws \XF\PrintableException
     */
    public function getFreshAccessTokenForUser($provider, $userConnectedAccount)
    {
        /** @var Provider $handler */
        $handler = $provider->handler;
        $storageState = $handler->getStorageState($provider, $userConnectedAccount->User);
        $token = $storageState->getTokenObjectForUser();
        if (empty($token)) {
            return null;
        }

        if (!$token->isExpired()) {
            return $token;
        }

        $newTokenObj = $this->refreshAccessToken($token);

        if ($storageState->getStorage() !== $this->getStorage()) {
            $storageState->storeToken($newTokenObj);
        }

        $userConnectedAccount->extra_data = $handler->getProviderData($storageState)->getExtraData();
        $userConnectedAccount->save();

        return $newTokenObj;
    }

    public function service()
    {
        if ($this->providerId === null) {
            throw new \RuntimeException('Provider ID must be setup before usage');
        }

        return $this->providerId;
    }

    /**
     * @param $providerId
     * @param array $config
     */
    public function updateProviderIdAndConfig($providerId, $config)
    {
        $this->providerId = $providerId;

        if (!empty($config['root'])) {
            $this->baseApiUri = new Uri($config['root']);
        }
    }

    /**
     * @param array $data
     * @return bool
     */
    public function verifyJsSdkSignature($data)
    {
        static $keySignature = 'signature';
        if (empty($data[$keySignature])) {
            return false;
        }

        $str = '';
        ksort($data);
        foreach ($data as $key => $value) {
            if ($key === $keySignature) {
                continue;
            }

            $str .= sprintf('%s=%s&', $key, $value);
        }
        $str .= $this->credentials->getConsumerSecret();
        $signature = md5($str);

        return $data[$keySignature] === $signature;
    }

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
