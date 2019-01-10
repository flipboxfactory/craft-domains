<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://github.com/flipboxfactory/craft-integration/blob/master/LICENSE
 * @link       https://github.com/flipboxfactory/craft-integration/
 */

namespace flipbox\craft\domains\actions;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use flipbox\craft\domains\fields\Domains;
use yii\web\HttpException;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 2.0.0
 */
trait ResolverTrait
{
    /**
     * @param string $field
     * @return Domains
     * @throws HttpException
     */
    protected function resolveField(string $field): Domains
    {
        $field = is_numeric($field) ?
            Craft::$app->getFields()->getFieldbyId($field) :
            Craft::$app->getFields()->getFieldByHandle($field);

        /** @var Domains $field */

        if (!$field instanceof Domains) {
            throw new HttpException(400, sprintf(
                "Field must be an instance of '%s', '%s' given.",
                Domains::class,
                get_class($field)
            ));
        }

        return $field;
    }

    /**
     * @param string $element
     * @return ElementInterface|Element
     * @throws HttpException
     */
    protected function resolveElement(string $element): ElementInterface
    {
        if (null === ($element = Craft::$app->getElements()->getElementById($element))) {
            throw new HttpException(400, 'Invalid element');
        };

        return $element;
    }
}
