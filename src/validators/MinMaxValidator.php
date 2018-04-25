<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\validators;

use craft\validators\ArrayValidator;
use yii\db\QueryInterface;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class MinMaxValidator extends ArrayValidator
{
    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        $value = $model->$attribute;

        if($value instanceof QueryInterface) {
            return $this->validateQueryAttribute($model, $attribute, $value);
        }

        return parent::validateAttribute($model, $attribute);
    }

    /**
     * @inheritdoc
     */
    protected function validateValue($value)
    {
        if(!$value instanceof QueryInterface) {
            return parent::validateValue($value);
        }

        $count = $value->count();

        if ($this->min !== null && $count < $this->min) {
            return [$this->tooFew, ['min' => $this->min]];
        }
        if ($this->max !== null && $count > $this->max) {
            return [$this->tooMany, ['max' => $this->max]];
        }
        if ($this->count !== null && $count !== $this->count) {
            return [$this->notEqual, ['count' => $this->count]];
        }

        return null;
    }

    /**
     * @param $model
     * @param $attribute
     * @param QueryInterface $query
     */
    protected function validateQueryAttribute($model, $attribute, QueryInterface $query)
    {
        $count = $query->count();

        if ($this->min !== null && $count < $this->min) {
            $this->addError($model, $attribute, $this->tooFew, ['min' => $this->min]);
        }
        if ($this->max !== null && $count > $this->max) {
            $this->addError($model, $attribute, $this->tooMany, ['max' => $this->max]);
        }
        if ($this->count !== null && $count !== $this->count) {
            $this->addError($model, $attribute, $this->notEqual, ['count' => $this->count]);
        }
    }
}
