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
use craft\helpers\StringHelper;
use flipbox\domains\db\DomainsQuery;
use flipbox\domains\Domains as DomainsPlugin;
use flipbox\domains\models\Domain;
use flipbox\spark\helpers\ArrayHelper;
use yii\base\Exception;

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
     * @inheritdoc
     */
    public static function hasContentColumn(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();

        if ($this->unique) {
            $rules[] = 'validateDomains';
        }

        return $rules;
    }

    public function validateDomains($element)
    {
        /** @var Element $element */

        /** @var DomainsQuery $value */
        $value = $element->getFieldValue($this->handle);

        // If we have a cached result, let's validate them
        if (($cachedResult = $value->getCachedResult()) !== null) {
            $domains = [];
            foreach ($cachedResult as $model) {
                if (!$model->validate(['domain', 'status'])) {
                    if ($model->hasErrors('domain')) {
                        $element->addError(
                            $this->handle,
                            $model->getFirstError('domain')
                        );
                    }

                    if ($model->hasErrors('status')) {
                        $element->addError(
                            $this->handle,
                            $model->getFirstError('status')
                        );
                    }
                }

                $domains[$model->domain] = $model->domain;
            }

            if ($this->unique === true) {
                $domainQuery = (new DomainsQuery($this))
                    ->select(['elementId', 'domain'])
                    ->andWhere(['domain' => $domains]);

                // Ignore this element
                if ($existingElementId = $element->getId()) {
                    $domainQuery->andWhere([
                        '!=',
                        'elementId',
                        $existingElementId
                    ]);
                }

                if ($domain = $domainQuery->one()) {
                    $element->addError(
                        $this->handle,
                        Craft::t(
                            'domains',
                            "Domain '{domain}' is already in use by element {elementId}.",
                            [
                                'domain' => $domain->domain,
                                'elementId' => $domain->getElementId()
                            ]
                        )
                    );
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        // All good
        if ($value instanceof DomainsQuery) {
            return $value;
        }

        /** @var Element|null $element */
        $query = (new DomainsQuery($this))
            ->siteId($this->targetSiteId($element));

        // $value will be an array of domains
        if (is_array($value)) {
            $this->modifyQueryInputValue($query, $value, $element);
        } elseif ($value === '') {
            $this->modifyQueryEmptyValue($query, $value, $element);
        } else {
            $this->modifyQuery($query, $value, $element);
        }

        if ($this->allowLimit && $this->limit) {
            $query->limit($this->limit);
        }

        return $query;
    }

    /**
     * @param $value
     * @param ElementInterface|null $element
     * @return DomainsQuery
     */
    private function modifyQueryInputValue(DomainsQuery $query, array $value, ElementInterface $element = null)
    {
        $models = [];
        foreach ($value as $val) {
            if (!is_array($val)) {
                $val = [
                    'domain' => $value,
                    'status' => $this->defaultStatus
                ];
            }

            $models[] = new Domain(
                $this,
                [
                    'domain' => ArrayHelper::getValue($val, 'domain'),
                    'status' => ArrayHelper::getValue($val, 'status'),
                    'element' => $element
                ]
            );
        }
        $query->setCachedResult($models);
    }

    /**
     * @param DomainsQuery $query
     * @param string $value
     * @param ElementInterface|null $element
     * @return DomainsQuery
     */
    private function modifyQueryEmptyValue(DomainsQuery $query, string $value, ElementInterface $element = null)
    {
        $query->setCachedResult([]);
    }

    /**
     * @param DomainsQuery $query
     * @param string $value
     * @param ElementInterface|null $element
     * @return DomainsQuery
     */
    private function modifyQuery(DomainsQuery $query, string $value = null, ElementInterface $element = null)
    {
        if ($value !== '' && $element && $element->id) {
            $query->elementId($element->id);
        } else {
            $query->elementId(false);
            $query->domain(false);
        }
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
            $fieldService = DomainsPlugin::getInstance()->getField();
            $alias = $fieldService->getTableAlias($this);
            $name = $fieldService->getTableName($this);
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
        /** @var Element $element */

        /** @var DomainsQuery $value */
        $value = $element->getFieldValue($this->handle);

        // If we have a cached result, let's save them
        if (($cachedResult = $value->getCachedResult()) !== null) {
            $currentDomains = (new DomainsQuery($this))
                ->siteId($element->siteId)
                ->elementId($element->getId())
                ->indexBy('domain')
                ->all();

            foreach ($cachedResult as $model) {
                $model->setElementId($element->getId());
                $model->siteId = $element->siteId;

                if (!DomainsPlugin::getInstance()->getRelationship()->associate($model)) {
                    throw new Exception("Unable to associate domain");
                }

                ArrayHelper::remove($currentDomains, $model->domain);
            }

            foreach ($currentDomains as $domain) {
                DomainsPlugin::getInstance()->getRelationship()->dissociate($domain);
            }
        }

        parent::afterElementSave($element, $isNew);
    }

    /**
     * @inheritdoc
     *
     * @param DomainsQuery $value
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        $input = '<input type="hidden" name="' . $this->handle . '" value="">';

        $tableHtml = $this->getTableHtml($value, $element, false);

        if ($tableHtml) {
            $input .= $tableHtml;
        }

        return $input;
    }

    /**
     * Returns the field's input HTML.
     *
     * @param mixed $value
     * @param ElementInterface|null $element
     * @param bool $static
     *
     * @return string
     */
    private function getTableHtml($value, ElementInterface $element = null, bool $static): string
    {
        $columns = [
            'domain' => [
                'heading' => 'Domain',
                'handle' => 'domain',
                'type' => 'singleline'
            ],
            'status' => [
                'heading' => 'Status',
                'handle' => 'status',
                'type' => 'select',
                'options' => $this->getStatuses()
            ]
        ];

        if (!empty($columns)) {
            // Translate the column headings
            foreach ($columns as &$column) {
                if (!empty($column['heading'])) {
                    $column['heading'] = Craft::t('site', $column['heading']);
                }
            }
            unset($column);

            $id = Craft::$app->getView()->formatInputId($this->handle);

            return Craft::$app->getView()->renderTemplate(
                'domains/_components/fieldtypes/Domains/input',
                [
                    'id' => $id,
                    'name' => $this->handle,
                    'cols' => $columns,
                    'rows' => $value->all(),
                    'static' => $static
                ]
            );
        }

        return null;
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
     * @param DomainsQuery|null $value
     *
     * @inheritdoc
     */
    public function getSearchKeywords($value, ElementInterface $element): string
    {
        if (!$value) {
            return '';
        }

        $domains = [];

        foreach ($value->all() as $domain) {
            array_push($domains, $domain->domain);
        }

        return StringHelper::toString($domains, ' ');
    }
}
