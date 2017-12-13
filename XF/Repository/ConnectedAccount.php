<?php

namespace Xfrocks\ApiConsumer\XF\Repository;

use XF\ConnectedAccount\ProviderData\AbstractProviderData;
use XF\Entity\User;
use XF\Entity\UserConnectedAccount;
use XF\Pub\Controller\Register;
use Xfrocks\ApiConsumer\ConnectedAccount\ProviderData;
use Xfrocks\ApiConsumer\XF\Service\User\Registration;

class ConnectedAccount extends XFCP_ConnectedAccount
{
    /**
     * @param AbstractProviderData $providerData
     * @return UserConnectedAccount|null
     *
     * Use string instead of the constant here to avoid loading the class into memory unnecessary.
     * @see Provider::PROVIDER_ID_PREFIX
     */
    public function getUserConnectedAccountFromProviderData(AbstractProviderData $providerData)
    {
        $userConnectedAccount = parent::getUserConnectedAccountFromProviderData($providerData);

        if ($userConnectedAccount === null) {
            $autoRegister = $this->options()->bdapi_consumer_autoRegister;
            if (!empty($autoRegister) &&
                $autoRegister !== 'off' &&
                strpos($providerData->getProviderId(), 'bdapi_') === 0) {
                /** @noinspection PhpParamsInspection */
                return $this->autoRegisterApiConsumerUserConnectedAccount($autoRegister, $providerData);
            }
        }

        return $userConnectedAccount;
    }

    /**
     * @param string $mode
     * @param ProviderData $providerData
     * @return UserConnectedAccount|null
     *
     * @see Register::setupConnectedRegistration
     */
    protected function autoRegisterApiConsumerUserConnectedAccount($mode, $providerData)
    {
        if ($mode !== 'on' && $mode !== 'id_sync') {
            return null;
        }

        $input = $providerData->getAutoRegistrationInput();
        if ($input === null) {
            return null;
        }

        if ($mode === 'id_sync') {
            $userId = intval($providerData->getUserId());
            if ($userId < 1) {
                return null;
            }
            $existingUserId = $this->db()->fetchOne('SELECT user_id FROM xf_user WHERE user_id = ?', $userId);
            if (!empty($existingUserId)) {
                return null;
            }
            $input['user_id'] = $userId;
        }

        /** @var Registration $registration */
        $registration = $this->app()->service('XF:User\Registration');
        $registration->setApiConsumerAutoRegistration();
        $registration->setFromInput($input);
        $registration->setNoPassword();
        $registration->skipEmailConfirmation();

        $avatarUrl = $providerData->avatar_url;
        if ($avatarUrl) {
            $registration->setAvatarUrl($avatarUrl);
        }

        if (!$registration->validate($errors)) {
            return null;
        }

        if (!empty($input['user_id'])) {
            $registration->getUser()->set('user_id', $input['user_id'], ['forceSet' => true]);
        }

        /** @var User $user */
        $user = $registration->save();
        $userConnectedAccount = $this->associateConnectedAccountWithUser($user, $providerData);

        return $userConnectedAccount;
    }
}
