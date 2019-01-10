<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\craft\domains\validators;

use Craft;
use yii\validators\Validator;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class DomainValidator extends Validator
{
    /**
     * The Domain pattern
     */
    const PATTERN = '/^(?!\-)(?:[a-zA-Z\d\-]{0,62}[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$/';

    /**
     * @inheritdoc
     */
    protected function validateValue($value)
    {
        if ($value) {
            if (preg_match(self::PATTERN, $value) !== 1) {
                return [
                    Craft::t('domains', "Invalid domain '{domain}'", ['domain' => $value]),
                    []
                ];
            }
        }

        return null;
    }
}
