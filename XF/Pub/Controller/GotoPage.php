<?php

namespace Xfrocks\ApiConsumer\XF\Pub\Controller;

use Xfrocks\ApiConsumer\Service\RedirectBuilder;

class GotoPage extends XFCP_GotoPage
{
    public function actionApiConsumerLogout()
    {
        $input = $this->filter([
            'redirect' => 'str',
            'hash' => 'str'
        ]);

        /** @var RedirectBuilder $builder */
        $builder = $this->service('Xfrocks\ApiConsumer:RedirectBuilder');
        if ($input['hash'] !== $builder->calculateHash($input['redirect'])) {
            return $this->redirect('/');
        }

        return $this->redirectPermanently($input['redirect']);
    }
}
