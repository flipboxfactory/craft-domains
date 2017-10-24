<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/domains/organization/
 */

namespace flipbox\domains\events;

use craft\base\ElementInterface;
use craft\events\CancelableEvent;
use flipbox\domains\fields\Domains;

class RelationshipEvent extends CancelableEvent
{
    /**
     * @var Domains
     */
    public $field;

    /**
     * @var string
     */
    public $domain;

    /**
     * @var int
     */
    public $elementId;

    /**
     * @var int
     */
    public $siteId;
}
