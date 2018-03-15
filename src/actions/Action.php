<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\actions;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use flipbox\domains\records\Domain;
use flipbox\ember\actions\model\traits\Manage;
use yii\web\HttpException;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since  1.0.0
 */
abstract class Action extends \yii\base\Action
{
    use Manage;

    /**
     * @param string $field
     * @param string $source
     * @param string $target
     * @param int|null $siteId
     * @param int|null $sortOrder
     * @return mixed
     * @throws HttpException
     */
    public function run(
        string $field,
        string $source,
        string $target,
        int $siteId = null,
        int $sortOrder = null
    ) {
        // Resolve Field
        if (null === ($domainField = Craft::$app->getFields()->getFieldById($field))) {
            return $this->handleInvalidFieldResponse($field);
        }

        // Resolve Source
        if (null === ($sourceElement = Craft::$app->getElements()->getElementById($source))) {
            return $this->handleInvalidElementResponse($source);
        }

        // Resolve Target
        if (null === ($targetElement = Craft::$app->getElements()->getElementById($target))) {
            return $this->handleInvalidElementResponse($target);
        }

        // Resolve Site Id
        if (null === $siteId) {
            $siteId = Craft::$app->getSites()->currentSite->id;
        }

        $model = new Domain([
            'fieldId' => $domainField->id,
            'sourceId' => $sourceElement->getId(),
            'targetId' => $targetElement->getId(),
            'siteId' => $siteId,
            'sortOrder' => $sortOrder
        ]);

        return $this->runInternal($model);
    }

    /**
     * @param int $fieldId
     * @throws HttpException
     */
    protected function handleInvalidFieldResponse(int $fieldId)
    {
        throw new HttpException(sprintf(
            "The provided field '%s' must be an instance of '%s'",
            (string)$fieldId,
            (string)FieldInterface::class
        ));
    }

    /**
     * @param int $elementId
     * @throws HttpException
     */
    protected function handleInvalidElementResponse(int $elementId)
    {
        throw new HttpException(sprintf(
            "The provided source '%s' must be an instance of '%s'",
            (string)$elementId,
            (string)ElementInterface::class
        ));
    }
}
