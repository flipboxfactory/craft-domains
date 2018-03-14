<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\services;

use flipbox\domains\db\DomainsQuery;
use flipbox\domains\records\Domain;
use flipbox\ember\services\traits\records\Accessor;
use yii\base\Component;
use yii\db\ActiveQuery;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Domains extends Component
{
    use Accessor;

    /**
     * @inheritdoc
     */
    public static function recordClass(): string
    {
        return Domain::class;
    }

    /**
     * @inheritdoc
     */
    public function getQuery($config = []): ActiveQuery
    {
        return new DomainsQuery(static::recordClass(), $config);
    }
}
