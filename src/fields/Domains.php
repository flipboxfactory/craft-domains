<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\fields;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\FieldInterface;
use craft\helpers\ArrayHelper;
use flipbox\craft\sourceTarget\db\AssociationQuery;
use flipbox\craft\sourceTarget\fields\traits\ModifyElementsQueryTrait;
use flipbox\craft\sourceTarget\fields\traits\NormalizeTrait;
use flipbox\craft\sourceTarget\records\AssociationRecordInterface;
use flipbox\domains\db\DomainsQuery;
use flipbox\domains\Domains as DomainsPlugin;
use flipbox\domains\records\Domain;
use flipbox\domains\validators\DomainsValidator;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Domains extends Field implements FieldInterface
{
    use NormalizeTrait, ModifyElementsQueryTrait;

    /**
     * @var bool
     */
    public $unique = true;

    /**
     * @var int|null The maximum number of relations this field can have (used if [[allowLimit]] is set to true)
     */
    public $limit;

    /**
     * @var bool Whether to allow the Limit setting
     */
    public $allowLimit = true;

    /**
     * @var string
     */
    public $defaultStatus = 'pending';

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('domains', 'Domains');
    }

    /**
     * @return array
     */
    public static function getStatuses(): array
    {
        return [
            'enabled' => Craft::t('domains', 'Enabled'),
            'pending' => Craft::t('domains', 'Pending'),
            'disabled' => Craft::t('domains', 'Disabled')
        ];
    }

    /**
     * @inheritdoc
     */
    public static function hasContentColumn(): bool
    {
        return false;
    }

    /*******************************************
     * QUERY
     *******************************************/

    /**
     * @inheritdoc
     * @return DomainsQuery
     */
    protected function newQuery(): AssociationQuery
    {
        $query = Domain::find()
            ->fieldId($this->id);

        if ($this->allowLimit === true && $this->limit !== null) {
            $query->limit = $this->limit;
        }

        return $query;
    }

    /**
     * The relations table name
     *
     * @return string
     */
    protected function sourceAttribute(): string
    {
        return Domain::SOURCE_ATTRIBUTE;
    }

    /**
     * The relations table alias
     *
     * @return string
     */
    protected function targetAttribute(): string
    {
        return Domain::TARGET_ATTRIBUTE;
    }

    /**
     * The relations table name
     *
     * @return string
     */
    public function getTableName(): string
    {
        return Domain::tableName();
    }

    /**
     * The relations table alias
     *
     * @return string
     */
    public function getTableAlias(): string
    {
        return Domain::tableAlias();
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();

        $rules[] = [
            DomainsValidator::class,
            'field' => $this
        ];

        return $rules;
    }


    /*******************************************
     * NORMALIZE VALUE
     *******************************************/

    /**
     * @inheritdoc
     */
    protected function normalizeQueryInputValue(
        $value,
        int &$sortOrder,
        ElementInterface $element = null
    ): AssociationRecordInterface {
        if (!is_array($value)) {
            $value = [
                'domain' => $value,
                'status' => $this->defaultStatus
            ];
        }

        return new Domain(
            [
                'fieldId' => $this->id,
                'domain' => ArrayHelper::getValue($value, 'domain'),
                'elementId' => $element ? $element->getId() : false,
                'status' => ArrayHelper::getValue($value, 'status'),
                'siteId' => $this->targetSiteId($element),
                'sortOrder' => $sortOrder++
            ]
        );
    }

    /**
     * @param DomainsQuery $value
     * @inheritdoc
     */
    public function getSearchKeywords($value, ElementInterface $element): string
    {
        $domains = [];

        foreach ($value->all() as $association) {
            array_push($domains, $association->domain);
        }

        return parent::getSearchKeywords($domains, $element);
    }


    /*******************************************
     * VIEWS
     *******************************************/

    /**
     * @param DomainsQuery $value
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        return DomainsPlugin::getInstance()->getField()->getTableHtml($this, $value, false);
    }


    /*******************************************
     * EVENTS
     *******************************************/

//    /**
//     * @inheritdoc
//     */
//    public function afterSave(bool $isNew)
//    {
//        DomainsPlugin::getInstance()->getField()->save($this);
//        parent::afterSave($isNew);
//    }
//
//    /**
//     * @inheritdoc
//     */
//    public function afterDelete()
//    {
//        DomainsPlugin::getInstance()->getField()->delete($this);
//        parent::afterDelete();
//    }

    /**
     * @inheritdoc
     */
    public function afterElementSave(ElementInterface $element, bool $isNew)
    {
        DomainsPlugin::getInstance()->getAssociations()->save(
            $this,
            $element->getFieldValue($this->handle),
            $element
        );

        parent::afterElementSave($element, $isNew);
    }


    /*******************************************
     * UTILITIES
     *******************************************/

    /**
     * Returns the site ID that target elements should have.
     *
     * @param ElementInterface|Element|null $element
     *
     * @return int
     */
    protected function targetSiteId(ElementInterface $element = null): int
    {
        /** @var Element $element */
        if (Craft::$app->getIsMultiSite() === true && $element !== null) {
            return $element->siteId;
        }

        return Craft::$app->getSites()->currentSite->id;
    }
}
