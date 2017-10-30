<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\actions;

use flipbox\domains\Domains as DomainsPlugin;
use flipbox\domains\models\Domain;
use flipbox\spark\actions\model\traits\Delete;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since  1.0.0
 */
class Dissociate extends Action
{
    use Delete, traits\Lookup;

    /**
     * @param Domain $model
     *
     * @return bool
     */
    protected function performAction(Domain $model): bool
    {
        return DomainsPlugin::getInstance()->getRelationship()->delete(
            $this->getField(),
            $model
        );
    }
}
