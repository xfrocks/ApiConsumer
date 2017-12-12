<?php

namespace Xfrocks\ApiConsumer;

use XF\AddOn\AbstractSetup;
use XF\AddOn\StepRunnerInstallTrait;
use XF\AddOn\StepRunnerUninstallTrait;
use XF\AddOn\StepRunnerUpgradeTrait;

class Setup extends AbstractSetup
{
    use StepRunnerInstallTrait;
    use StepRunnerUpgradeTrait;
    use StepRunnerUninstallTrait;

    public function uninstallStep1()
    {
        \XF::db()->delete(
            'xf_connected_account_provider',
            'provider_class = ?',
            'Xfrocks\ApiConsumer:Provider'
        );
    }
}
