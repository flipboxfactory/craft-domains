<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\actions;

use flipbox\domains\db\DomainsQuery;
use flipbox\spark\actions\model\traits\Index as IndexTrait;
use yii\data\DataProviderInterface;
use yii\db\QueryInterface;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since  1.0.0
 */
abstract class Index extends Action
{
    use IndexTrait;

    /**
     * @inheritdoc
     */
    protected function assembleQuery(array $config = []): QueryInterface
    {
        return new DomainsQuery(
            $this->getField(),
            $config
        );
    }

    /**
     * @param int|null $elementId
     *
     * @return DataProviderInterface
     */
    public function run(int $elementId = null): DataProviderInterface
    {
        $config = [];

        if ($elementId !== null) {
            $config['elementId'] = $elementId;
        }

        return $this->runInternal(
            $this->assembleDataProvider()
        );
    }
}
