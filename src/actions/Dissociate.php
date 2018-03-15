<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\actions;

use flipbox\craft\sortable\associations\records\SortableAssociationInterface;
use flipbox\domains\Domains;
use yii\base\Model;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since  1.0.0
 */
class Dissociate extends Action
{
    /**
     * @param Model|SortableAssociationInterface $model
     * @return bool
     * @throws \Exception
     */
    protected function performAction(Model $model): bool
    {
        return Domains::getInstance()->getAssociations()->dissociate($model);
    }
}
