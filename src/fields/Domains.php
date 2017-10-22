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
use flipbox\domains\db\DomainsQuery;
use flipbox\domains\Domains as DomainsPlugin;
use flipbox\domains\validators\UniqueValidator;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Domains extends Field
{
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
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('domains', 'Domains');
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();

        if ($this->unique) {
            $rules[$this->handle] = UniqueValidator::class;
        }

        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        if ($value instanceof ElementQueryInterface) {
            return $value;
        }

        /** @var Element|null $element */
        $query = (new DomainsQuery())
            ->siteId($this->targetSiteId($element));

        // $value will be an array of element IDs if there was a validation error or we're loading a draft/version.
        if (is_array($value)) {
            $query
                ->id(array_values(array_filter($value)))
                ->fixedOrder();
        } else {
            if ($value !== '' && $element && $element->id) {
                $alias = DomainsPlugin::getInstance()->getField()->getTableAlias($this);
                $name = DomainsPlugin::getInstance()->getField()->getTableName($this);

                $query->innerJoin(
                    $name . ' ' . $alias,
                    [
                        'and',
                        '[[' . $alias . '.elementId]] = [[elements.id]]',
                        [
                            $alias . '.elementId' => $element->id
                        ],
                        [
                            'or',
                            [$alias . '.siteId' => null],
                            [$alias . '.siteId' => $element->siteId]
                        ]
                    ]
                );
            } else {
                $query->id(false);
            }
        }

        if ($this->allowLimit && $this->limit) {
            $query->limit($this->limit);
        }

        return $query;
    }

    /**
     * Returns the site ID that target elements should have.
     *
     * @param ElementInterface|null $element
     *
     * @return int
     */
    protected function targetSiteId(ElementInterface $element = null): int
    {
        /** @var Element|null $element */
        if (Craft::$app->getIsMultiSite()) {
            if ($element !== null) {
                return $element->siteId;
            }
        }

        return Craft::$app->getSites()->currentSite->id;
    }

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
            $alias = DomainsPlugin::getInstance()->getField()->getTableAlias($this);
            $name = DomainsPlugin::getInstance()->getField()->getTableName($this);
            $operator = ($value === ':notempty:' ? '!=' : '=');

            $query->subQuery->andWhere(
                "(select count([[{$alias}.id]]) from " .
                $name .
                " {{{$alias}}} where [[{$alias}.elementId]] = [[elements.id]]) {$operator} 0"
            );
        } else {
            if ($value !== null) {
                return false;
            }
        }

        return null;
    }

    // Events
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     */
    public function afterSave(bool $isNew)
    {
        DomainsPlugin::getInstance()->getField()->saveSettings($this);
        parent::afterSave($isNew);
    }
}
