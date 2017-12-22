<?php

namespace Xfrocks\ApiConsumer\XF\ConnectedAccount\Storage;

use Xfrocks\ApiConsumer\Listener;

class StorageState extends XFCP_StorageState
{
    public function getTokenObjectForUser()
    {
        $token = parent::getTokenObjectForUser();

        if ($token !== false) {
            $user = $this->user;
            $provider = $this->provider;
            $connectedAccount = $user->ConnectedAccounts[$provider->provider_id];
            if (!empty($connectedAccount->extra_data[Listener::CONNECTED_ACCOUNT_EXTRA_DATA_KEY])) {
                $ourData = $connectedAccount->extra_data[Listener::CONNECTED_ACCOUNT_EXTRA_DATA_KEY];
                if (!empty($ourData[Listener::CONNECTED_ACCOUNT_EXTRA_DATA_REFRESH_TOKEN])) {
                    $token->setRefreshToken($ourData[Listener::CONNECTED_ACCOUNT_EXTRA_DATA_REFRESH_TOKEN]);
                }
                if (!empty($ourData[Listener::CONNECTED_ACCOUNT_EXTRA_DATA_END_OF_LIFE])) {
                    $token->setEndOfLife($ourData[Listener::CONNECTED_ACCOUNT_EXTRA_DATA_END_OF_LIFE]);
                }
                if (!empty($ourData[Listener::CONNECTED_ACCOUNT_EXTRA_DATA_EXTRA_PARAMS])) {
                    $token->setExtraParams($ourData[Listener::CONNECTED_ACCOUNT_EXTRA_DATA_EXTRA_PARAMS]);
                }
            } else {
                $token->setEndOfLife(1);
            }
        }

        return $token;
    }
}
