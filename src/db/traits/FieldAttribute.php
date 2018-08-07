<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\db\traits;

use craft\base\FieldInterface;
use craft\helpers\Db;
use yii\db\Expression;

trait FieldAttribute
{
    /**
     * @var int|int[]|string|string[]|null|false|FieldInterface|FieldInterface[]
     */
    public $field;

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
     * @param int|int[]|string|string[]|null|false|FieldInterface|FieldInterface[] $value
     * @return static
     */
    public function setField($value)
    {
        $this->field = $value;
        return $this;
    }

    /**
     * @param int|int[]|string|string[]|null|false|FieldInterface|FieldInterface[] $value
     * @return static
     */
    public function field($value)
    {
        return $this->setField($value);
    }

    /**
     * @param int|int[]|string|string[]|null|false|FieldInterface|FieldInterface[] $value
     * @return static
     */
    public function fieldId($value)
    {
        return $this->setField($value);
    }

    /**
     * @param int|int[]|string|string[]|null|false|FieldInterface|FieldInterface[] $value
     * @return static
     */
    public function setFieldId($value)
    {
        return $this->setField($value);
    }

    /**
     *  Apply query specific conditions
     */
    protected function applyFieldConditions()
    {
        if ($this->field !== null) {
            $this->andWhere(Db::parseParam('fieldId', $this->field));
        }
    }
}