<?php

namespace Xfrocks\ApiConsumer\ConnectedAccount\ProviderData;

use Xfrocks\ApiConsumer\Util\Arr;
use XF\ConnectedAccount\ProviderData\AbstractProviderData;

class Common extends AbstractProviderData
{
    public function getDefaultEndpoint()
    {
        return 'users/me';
    }

    public function getProviderKey()
    {
        return $this->retriveUserInfo('user_id');
    }

    public function getUsername()
    {
        return $this->retriveUserInfo('username');
    }

    public function getEmail()
    {
        return $this->retriveUserInfo('user_email');
    }

    public function getDob()
    {
        $dobDay = $this->retriveUserInfo('user_dob_day');
        $dobMonth = $this->retriveUserInfo('user_dob_month');
        $dobYear = $this->retriveUserInfo('user_dob_year');

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
        return $this->retriveUserInfo('links.avatar_big');
    }

    public function getProfileLink()
    {
        return $this->retriveUserInfo('links.permalink');
    }

    protected function retriveUserInfo($key)
    {
        $user = $this->requestFromEndpoint('user');
        return Arr::get($user, $key);
    }
}