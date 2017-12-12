<?php

namespace Xfrocks\ApiConsumer\ConnectedAccount;

use XF\ConnectedAccount\ProviderData\AbstractProviderData;

class ProviderData extends AbstractProviderData
{
    public function getDefaultEndpoint()
    {
        return 'index.php?users/me';
    }

    public function getProviderKey()
    {
        return $this->retrieveUserInfo('user_id');
    }

    public function getUsername()
    {
        return $this->retrieveUserInfo('username');
    }

    public function getEmail()
    {
        return $this->retrieveUserInfo('user_email');
    }

    public function getDob()
    {
        $dobDay = $this->retrieveUserInfo('user_dob_day');
        $dobMonth = $this->retrieveUserInfo('user_dob_month');
        $dobYear = $this->retrieveUserInfo('user_dob_year');

        if (!empty($dobDay) && !empty($dobMonth) && !empty($dobYear)) {
            return [
                'dob_year' => $dobYear,
                'dob_month' => $dobMonth,
                'dob_day' => $dobDay
            ];
        }

        return null;
    }

    public function getAvatarUrl()
    {
        return $this->retrieveUserInfo('links.avatar_big');
    }

    public function getProfileLink()
    {
        return $this->retrieveUserInfo('links.permalink');
    }

    protected function retrieveUserInfo($key)
    {
        $user = $this->requestFromEndpoint('user');
        if (!is_array($user)) {
            return $user;
        }

        if (array_key_exists($key, $user)) {
            return $user[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (array_key_exists($segment, $user)) {
                $user = $user[$segment];
            } else {
                return null;
            }
        }

        return $user;
    }
}
