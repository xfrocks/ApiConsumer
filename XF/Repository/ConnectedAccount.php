<?php

namespace Xfrocks\ApiConsumer\XF\Repository;

use XF\ConnectedAccount\ProviderData\AbstractProviderData;
use XF\Entity\ConnectedAccountProvider;
use XF\Entity\User;
use XF\Entity\UserConnectedAccount;
use XF\Pub\Controller\Register;
use XF\Repository\Option;
use Xfrocks\ApiConsumer\ConnectedAccount\Provider;
use Xfrocks\ApiConsumer\ConnectedAccount\ProviderData;
use Xfrocks\ApiConsumer\Listener;
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
        /** @var UserConnectedAccount $userConnectedAccount */
        $userConnectedAccount = parent::getUserConnectedAccountFromProviderData($providerData);

        if ($userConnectedAccount === null) {
            $autoRegister = $this->options()->bdapi_consumer_autoRegister;
            if (!empty($autoRegister) &&
                $autoRegister !== 'off' &&
                strpos($providerData->getProviderId(), 'bdapi_') === 0) {
                /** @var ProviderData $ourProviderData */
                $ourProviderData = $providerData;
                try {
                    $autoResult = $this->autoRegisterApiConsumerUserConnectedAccount($autoRegister, $ourProviderData);
                    if ($autoResult !== null) {
                        $userConnectedAccount = $autoResult;
                    }
                } catch (\Exception $e) {
                    \XF::logException($e, false, __CLASS__);
                }
            }
        }

        if ($userConnectedAccount !== null) {
            $this->app()->session()->set(
                Listener::SESSION_KEY_USER_CONNECTED_ACCOUNT_FROM_PROVIDER_ID,
                [
                    'userId' => $userConnectedAccount->User->user_id,
                    'providerId' => $providerData->getProviderId()
                ]
            );
        }

        return $userConnectedAccount;
    }

    /**
     * @param string $mode
     * @param ProviderData $providerData
     * @return UserConnectedAccount|null
     *
     * @see Register::setupConnectedRegistration
     * @throws \Exception
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

        $db = $this->db();
        $db->beginTransaction();

        try {
            /** @var User $user */
            $user = $registration->save();
            $userConnectedAccount = $this->associateConnectedAccountWithUser($user, $providerData);
        } catch (\Exception $e) {
            $db->rollback();

            // handle concurrent issue when another duplicate request arrived and auto-registered
            // just before the current request. It's still unclear what triggered two requests but
            // it's best if we can recover smoothly. Let's try to do it now... Finger crossed!
            /** @var \XF\Repository\User $userRepo */
            $userRepo = $this->repository('XF:User');
            /** @var User $dbUser */
            $dbUser = $userRepo->getUserByNameOrEmail($registration->getUser()->username);
            if (!empty($dbUser)) {
                /** @var UserConnectedAccount $dbUserConnectedAccount */
                $dbUserConnectedAccount = $this->em->findOne('XF:UserConnectedAccount', [
                    'user_id' => $dbUser->user_id,
                    'provider_key' => strval($providerData->getProviderKey()),
                    'provider' => $providerData->getProviderId()
                ]);

                if (!empty($dbUserConnectedAccount)) {
                    $logMessage = sprintf(
                        'Exception while trying to auto-register, recovered successfully with user #%s',
                        $dbUserConnectedAccount->user_id
                    );
                    \XF::logException(new \RuntimeException($logMessage, 0, $e), false, __CLASS__, true);

                    return $dbUserConnectedAccount;
                }
            }

            throw $e;
        }

        $db->commit();

        return $userConnectedAccount;
    }

    public function rebuildProviderCount()
    {
        $count = parent::rebuildProviderCount();
        $this->rebuildApiConsumerProvidersOption();

        return $count;
    }

    /**
     * @return array
     */
    public function rebuildApiConsumerProvidersOption()
    {
        $providers = $this->finder('XF:ConnectedAccountProvider')->fetch();
        $optionValue = [];
        /** @var ConnectedAccountProvider $provider */
        foreach ($providers as $provider) {
            if ($provider->provider_class !== Provider::PROVIDER_CLASS) {
                continue;
            }

            $providerArray = $provider->toArray();
            $providerArray += [
                'version' => Provider::PROVIDERS_OPTION_VERSION,
                'time' => time(),
                'isUsable' => $provider->isUsable()
            ];

            $optionValue[] = $providerArray;
        }

        /** @var Option $optionRepo */
        $optionRepo = $this->repository('XF:Option');
        $optionRepo->updateOption('bdapi_consumer_providers', $optionValue);

        return $optionValue;
    }
}
