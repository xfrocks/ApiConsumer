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

	protected function installStep1()
    {
        \XF::db()->insert('xf_connected_account_provider', [
            'provider_id' => 'bdapi_consumer',
            'provider_class' => 'Xfrocks\ApiConsumer:Provider\Common',
            'display_order' => 120,
            'options' => ''
        ]);
    }

    protected function uninstallStep1()
    {
        \XF::db()->delete(
            'xf_connected_account_provider',
            'provider_class = ' . \XF::db()->quote('Xfrocks\\ApiConsumer:Provider\\Common')
        );
    }
}