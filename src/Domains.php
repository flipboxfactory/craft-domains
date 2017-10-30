<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains;

use craft\base\Plugin as BasePlugin;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields;
use flipbox\domains\fields\Domains as DomainsField;
use yii\base\Event;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since  1.0.0
 */
class Domains extends BasePlugin
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        // Do parent
        parent::init();

        // Register our fields
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function (RegisterComponentTypesEvent $event) {
                $event->types[] = DomainsField::class;
            }
        );
    }

    /**
     * @return services\Field
     */
    public function getField()
    {
        return $this->get('field');
    }

    /**
     * @return services\Relationship
     */
    public function getRelationship()
    {
        return $this->get('relationship');
    }
}
