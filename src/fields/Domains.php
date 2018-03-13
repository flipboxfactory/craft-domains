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
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\ArrayHelper;
use flipbox\craft\sourceTarget\db\AssociationQueryInterface;
use flipbox\craft\sourceTarget\fields\FieldAssociationInterface;
use flipbox\craft\sourceTarget\fields\traits\NormalizeTrait;
use flipbox\craft\sourceTarget\models\AssociationModelInterface;
use flipbox\domains\db\DomainsQuery;
use flipbox\domains\Domains as DomainsPlugin;
use flipbox\domains\models\Domain;
use flipbox\domains\validators\DomainsValidator;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Domains extends Field implements FieldAssociationInterface
{
    use NormalizeTrait;

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
    public function getStatuses(): array
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
     * TABLE
     *******************************************/

    /**
     * @inheritdoc
     */
    public function getTableName(): string
    {
        return DomainsPlugin::getInstance()->getField()->getTableName($this);
    }

    /**
     * @inheritdoc
     */
    public function getTableAlias(): string
    {
        return DomainsPlugin::getInstance()->getField()->getTableAlias($this);
    }


    /*******************************************
     * SOURCE / TARGET ATTRIBUTES
     *******************************************/

    /**
     * @inheritdoc
     */
    protected function sourceAttribute(): string
    {
        return 'elementId';
    }

    /**
     * @inheritdoc
     */
    protected function targetAttribute(): string
    {
        return 'domain';
    }

    /*******************************************
     * ELEMENT
     *******************************************/

    /**
     * @inheritdoc
     */
    public function modifyElementsQuery(ElementQueryInterface $query, $value)
    {
        /** @var ElementQuery $query */
        if ($value === 'not :empty:') {
            $value = ':notempty:';
        }

        if ($value === ':notempty:' || $value === ':empty:') {
            $fieldService = DomainsPlugin::getInstance()->getField();
            $alias = $fieldService->getTableAlias($this);
            $name = $fieldService->getTableName($this);
            $operator = ($value === ':notempty:' ? '!=' : '=');

            $query->subQuery->andWhere([
                "(select count([[{$alias}.id]]) from " .
                $name .
                " {{{$alias}}} where [[{$alias}.elementId]] = [[elements.id]]) {$operator} 0"
            ]);
        } else {
            if ($value !== null) {
                return false;
            }
        }

        return null;
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

    /**
     * @inheritdoc
     * @return DomainsQuery
     */
    protected function newQuery(): AssociationQueryInterface
    {
        return new DomainsQuery($this);
    }

    /*******************************************
     * NORMALIZE VALUE
     *******************************************/

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        if ($value instanceof DomainsQuery) {
            return $value;
        }

        $query = $this->newQuery();
        $query->siteId = $this->targetSiteId($element);

        if ($this->allowLimit === true && $this->limit !== null) {
            $query->limit = $this->limit;
        }

        $this->normalizeQueryValue($query, $value, $element);
        return $query;
    }

    /**
     * @inheritdoc
     */
    protected function normalizeQueryInputValue(
        $value,
        int &$sortOrder,
        ElementInterface $element = null
    ): AssociationModelInterface {
        if (!is_array($value)) {
            $value = [
                'domain' => $value,
                'status' => $this->defaultStatus
            ];
        }

        return new Domain(
            $this,
            [
                Domain::TARGET_ATTRIBUTE => ArrayHelper::getValue($value, 'domain'),
                Domain::SOURCE_ATTRIBUTE => $element ? $element->getId() : false,
                'status' => ArrayHelper::getValue($value, 'status'),
                'siteId' => $this->targetSiteId($element),
                'sortOrder' => $sortOrder++
            ]
        );
    }

    /**
     * @param DomainsQuery $query
     * @param string $value
     * @param ElementInterface|null $element
     */
    protected function normalizeQuery(DomainsQuery $query, string $value = null, ElementInterface $element = null)
    {
        if ($value !== '' && $element !== null && $element->getId() !== null) {
            $query->elementId($element->getId());
        } else {
            $query->elementId(false);
            $query->domain(false);
        }
    }


    /**
     * @param DomainsQuery $value
     * @inheritdoc
     */
    public function getSearchKeywords($value, ElementInterface $element): string
    {
        $domains = [];

        foreach ($value->all() as $domain) {
            array_push($domains, $domain->domain);
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

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew)
    {
        DomainsPlugin::getInstance()->getField()->save($this);
        parent::afterSave($isNew);
    }

    /**
     * @inheritdoc
     */
    public function afterDelete()
    {
        DomainsPlugin::getInstance()->getField()->delete($this);
        parent::afterDelete();
    }

    /**
     * @inheritdoc
     */
    public function afterElementSave(ElementInterface $element, bool $isNew)
    {
        DomainsPlugin::getInstance()->getAssociations()->save(
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
