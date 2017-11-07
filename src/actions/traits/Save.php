<?php

namespace flipbox\domains\actions\traits;

use flipbox\domains\Domains as DomainsPlugin;
use flipbox\domains\models\Domain;
use flipbox\ember\actions\model\traits\Save as SaveTrait;

trait Save
{
    use SaveTrait;

    /**
     * @return array
     */
    protected function validBodyParams(): array
    {
        return [
            'elementId',
            'domain'
        ];
    }

    /**
     * @param Domain $model
     *
     * @return bool
     */
    protected function performAction(Domain $model): bool
    {
        return DomainsPlugin::getInstance()->getRelationship()->associate(
            $model
        );
    }
}
