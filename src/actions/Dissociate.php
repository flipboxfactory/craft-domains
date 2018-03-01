<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\actions;

use flipbox\domains\Domains as DomainsPlugin;
use flipbox\domains\models\Domain;
use flipbox\ember\actions\model\traits\Delete;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since  1.0.0
 */
abstract class Dissociate extends Action
{
    use Delete, traits\Lookup;

    /**
     * @param Domain $model
     * @return bool
     * @throws \Exception
     */
    protected function performAction(Domain $model): bool
    {
        return DomainsPlugin::getInstance()->getDomainAssociations()->dissociate(
            $model
        );
    }
}
