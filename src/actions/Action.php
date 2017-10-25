<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\actions;

use flipbox\domains\fields\Domains;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
abstract class Action extends \yii\base\Action
{
    /**
     * @return Domains
     */
    abstract protected function getField(): Domains;
}
