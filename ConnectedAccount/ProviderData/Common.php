<?php

namespace Xfrocks\ApiConsumer\ConnectedAccount\ProviderData;

use XF\ConnectedAccount\ProviderData\AbstractProviderData;

class Common extends AbstractProviderData
{
    public function getDefaultEndpoint()
    {
        return 'users/me';
    }

    public function getProviderKey()
    {
        return $this->requestFromEndpoint('user_id');
    }

    public function getUsername()
    {
        return $this->requestFromEndpoint('username');
    }

    public function getEmail()
    {
        return $this->requestFromEndpoint('user_email');
    }

    public function getDob()
    {
        $dobDay = $this->requestFromEndpoint('user_dob_day');
        $dobMonth = $this->requestFromEndpoint('user_dob_month');
        $dobYear = $this->requestFromEndpoint('user_dob_year');

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
        $data = $this->requestFromEndpoint('links');
        if (is_array($data) && isset($data['avatar_big'])) {
            return $data['avatar_big'];
        }

        return null;
    }

    public function getProfileLink()
    {
        $data = $this->requestFromEndpoint('links');
        if (is_array($data) && isset($data['permalink'])) {
            return $data['permalink'];
        }

        return null;
    }
}