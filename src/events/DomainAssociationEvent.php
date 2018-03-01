<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\events;

use craft\events\CancelableEvent;
use flipbox\domains\models\Domain;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class DomainAssociationEvent extends CancelableEvent
{
    /**
     * @var Domain
     */
    public $domain;
}