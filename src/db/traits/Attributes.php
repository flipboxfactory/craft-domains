<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\db\traits;

use craft\helpers\Db;
use yii\db\Expression;

trait Attributes
{
    /**
     * @var string|string[]|false|null The domain(s). Prefix domains with "not " to exclude them.
     */
    public $domain;

    /**
     * @var int|int[]|false|null The element ID(s). Prefix IDs with "not " to exclude them.
     */
    public $elementId;

    /**
     * @var int|int[]|false|null The field ID(s). Prefix IDs with "not " to exclude them.
     */
    public $fieldId;

    /**
     * Adds an additional WHERE condition to the existing one.
     * The new condition and the existing one will be joined using the `AND` operator.
     * @param string|array|Expression $condition the new WHERE condition. Please refer to [[where()]]
     * on how to specify this parameter.
     * @param array $params the parameters (name => value) to be bound to the query.
     * @return $this the query object itself
     * @see where()
     * @see orWhere()
     */
    abstract public function andWhere($condition, $params = []);

    /**
     * @param $value
     * @return static
     */
    public function fieldId($value)
    {
        $this->fieldId = $value;
        return $this;
    }

    /**
     * @param $value
     * @return static
     */
    public function field($value)
    {
        return $this->fieldId($value);
    }

    /**
     * @param $value
     * @return static
     */
    public function elementId($value)
    {
        $this->elementId = $value;
        return $this;
    }

    /**
     * @param $value
     * @return static
     */
    public function element($value)
    {
        return $this->elementId($value);
    }

    /**
     * @param $value
     * @return static
     */
    public function domain($value)
    {
        $this->domain = $value;
        return $this;
    }

    /**
     * Apply attribute conditions
     */
    protected function applyConditions()
    {
        if ($this->fieldId !== null) {
            $this->andWhere(Db::parseParam('fieldId', $this->fieldId));
        }

        if ($this->domain !== null) {
            $this->andWhere(Db::parseParam('domain', $this->domain));
        }

        if ($this->elementId !== null) {
            $this->andWhere(Db::parseParam('elementId', $this->elementId));
        }
    }
}
