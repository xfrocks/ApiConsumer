<?php

namespace Xfrocks\ApiConsumer\ConnectedAccount;

use XF\ConnectedAccount\ProviderData\AbstractProviderData;
use Xfrocks\ApiConsumer\Listener;

class ProviderData extends AbstractProviderData
{
    /**
     * @return array|null
     * @see Register::getConnectedRegistrationInput
     */
    public function getAutoRegistrationInput()
    {
        $username = $this->getUsername();
        $email = $this->getEmail();
        if (empty($username) || empty($email)) {
            return null;
        }

        $input = [
            'username' => $username,
            'email' => $email,
        ];

        $dob = $this->getDob();
        if (is_array($dob)) {
            $input += $dob;
        }

        $user = $this->requestFromEndpoint('user');
        if (!is_array($user)) {
            return $input;
        }

        if (isset($user['user_timezone_offset'])) {
            $timezoneOffset = $user['user_timezone_offset'];
            if (is_int($timezoneOffset)) {
                $timezone = timezone_name_from_abbr('', $timezoneOffset * 3600, 0);
                if (is_string($timezone)) {
                    $input['timezone'] = $timezone;
                }
            }
        }

        if (isset($user['fields']) && is_array($user['fields'])) {
            $input['custom_fields'] = [];
            foreach ($user['fields'] as $providerUserField) {
                if (!is_array($providerUserField) ||
                    empty($providerUserField['id']) ||
                    empty($providerUserField['value'])) {
                    continue;
                }

                switch ($providerUserField['id']) {
                    case 'location':
                        $input[$providerUserField['id']] = $providerUserField['value'];
                        break;
                    default:
                        $input['custom_fields'][$providerUserField['id']] = $providerUserField['value'];
                }
            }
        }

        return $input;
    }

    /**
     * @return string|null
     */
    public function getUsername()
    {
        return $this->getUserDataByKeys('username');
    }

    /**
     * @return mixed|null
     */
    private function getUserDataByKeys()
    {
        $keys = func_get_args();

        $user = $this->requestFromEndpoint('user');
        if (!is_array($user)) {
            return null;
        }

        $value = $user;
        foreach ($keys as $key) {
            if (!array_key_exists($key, $value)) {
                return null;
            }

            $value = $value[$key];
        }

        return $value;
    }

    /**
     * @return string|null
     */
    public function getEmail()
    {
        return $this->getUserDataByKeys('user_email');
    }

    /**
     * @return array|null
     */
    public function getDob()
    {
        $dobDay = $this->getUserDataByKeys('user_dob_day');
        $dobMonth = $this->getUserDataByKeys('user_dob_month');
        $dobYear = $this->getUserDataByKeys('user_dob_year');

        if (!empty($dobDay) && !empty($dobMonth) && !empty($dobYear)) {
            return [
                'dob_year' => $dobYear,
                'dob_month' => $dobMonth,
                'dob_day' => $dobDay
            ];
        }

        return null;
    }

    public function getDefaultEndpoint()
    {
        return 'index.php?users/me';
    }

    public function getExtraData()
    {
        $extraData = parent::getExtraData();

        $storageState = $this->storageState;
        $token = $storageState->getProviderToken();
        if (!empty($token)) {
            $extraData[Listener::CONNECTED_ACCOUNT_EXTRA_DATA_KEY] = [
                Listener::CONNECTED_ACCOUNT_EXTRA_DATA_REFRESH_TOKEN => $token->getRefreshToken(),
                Listener::CONNECTED_ACCOUNT_EXTRA_DATA_END_OF_LIFE => $token->getEndOfLife(),
                Listener::CONNECTED_ACCOUNT_EXTRA_DATA_EXTRA_PARAMS => $token->getExtraParams(),
            ];
        }

        return $extraData;
    }

    public function getProviderKey()
    {
        return $this->getUserId();
    }

    /**
     * @return int|null
     */
    public function getUserId()
    {
        return $this->getUserDataByKeys('user_id');
    }

    /**
     * @return string|null
     */
    public function getAvatarUrl()
    {
        return $this->getUserDataByKeys('links', 'avatar_big');
    }

    /**
     * @return string|null
     */
    public function getProfileLink()
    {
        return $this->getUserDataByKeys('links', 'permalink');
    }
}
