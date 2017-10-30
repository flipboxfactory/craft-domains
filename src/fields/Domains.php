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
use flipbox\domains\models\Domain;
use flipbox\domains\validators\UniqueValidator;
use flipbox\spark\helpers\ArrayHelper;
use yii\base\Exception;

// TODO was recommended to not use this validator

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
    public function getElementValidationRules(): array
    {
        $rules = parent::getElementValidationRules();

        if ($this->unique) {
            $rules[] = 'validateUniqueDomain';
        }

        return $rules;
    }

    public function validateUniqueDomain($element, $fieldParams)
    {
        /** @var Element $element */

        /** @var DomainsQuery $value */
        $value = $element->getFieldValue($this->handle);

        // If we have a cached result, let's validate them
        if (($cachedResult = $value->getCachedResult()) !== null) {
            $isValid = true;
            $domains = [];
            foreach ($cachedResult as $model) {
                if (!$model->validate(['domain', 'status'])) {
                    $isValid = false;
                }

                $domains[$model->domain] = $model->domain;
            }

            // TODO - CHECK IF EXISTS ANYWHERE ELSE (OTHER THAN PREVIOUSLY ASSOCIATED)
            if ($this->unique === true) {
                // Other elements occupy this domain

                // TODO - ADD WHERE NOT CURRENT ELEMENT ID (IF APPLICABLE)
                if ($elementIds = (new DomainsQuery($this))
                    ->select(['elementId'])
                    ->andWhere(['domain' => $domains])
                    ->column()
                ) {
                    /* ADD ERRORS EXAMPLE
                    $element->addError($this->handle, Craft::t('app', '"{filename}" is not allowed in this field.', [
                        'filename' => $filename
                    ]));*/
                }
            }
        }

//        if(!$isValid) {
//
//            /* ADD ERRORS EXAMPLE
//            $element->addError($this->handle, Craft::t('app', '"{filename}" is not allowed in this field.', [
//                'filename' => $filename
//            ]));*/
//
//        }
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
        $query = new DomainsQuery($this);

        // Multi-site
        $query->siteId($this->targetSiteId($element));

        // $value will be an array of domains
        if (is_array($value)) {
            $models = [];
            foreach ($value as $val) {
                // TODO remove everything before or after URL.com and set as submitted data

                if (!is_array($val)) {
                    $val = [
                        'domain' => $value,
                        'status' => $this->defaultStatus
                    ];
                }

                $models[] = new Domain([
                    'domain' => ArrayHelper::getValue($val, 'domain'),
                    'status' => ArrayHelper::getValue($val, 'status'),
                    'element' => $element
                ]);
            }
            $query->setCachedResult($models);
        } elseif ($value === '') {
            $query->setCachedResult([]);
        } else {
            if ($value !== '' && $element && $element->id) {
                $query->elementId($element->id);
            } else {
                $query->elementId(false);
                $query->domain(false);
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

    /**
     * @inheritdoc
     */
    public function afterDelete()
    {
        DomainsPlugin::getInstance()->getField()->delete($this);
        parent::afterDelete();
    }

    public function afterElementSave(ElementInterface $element, bool $isNew)
    {
        /** @var Element $element */

        /** @var DomainsQuery $value */
        $value = $element->getFieldValue($this->handle);

        // TODO update domains list
            // delete any removed
            // upsert new/existing

        // If we have a cached result, let's save them
        if (($cachedResult = $value->getCachedResult()) !== null) {
            // Domains currently used
            $domains = [];

            $currentDomains = (new DomainsQuery($this))
                ->select(['domain'])
                ->siteId($element->siteId)
                ->elementId($element->getId())
                ->indexBy('domain')
                ->column();

            foreach ($cachedResult as $model) {
                // Set properties on model
                $model->setElementId($element->getId());
                $model->siteId = $element->siteId;
                if (!DomainsPlugin::getInstance()->getRelationship()->associate(
                    $this,
                    $model->domain,
                    $model->getElementId(),
                    $model->status,
                    $model->siteId
                )) {
                    throw new Exception("Unable to associate domain");
                }

                $domains[$model->domain] = $model->domain;

                ArrayHelper::remove($currentDomains, $model->domain);
            }

            if ($currentDomains) {
                // DISSOCIATE
                foreach ($currentDomains as $domain) {
                    DomainsPlugin::getInstance()->getRelationship()->dissociate(
                        $this,
                        $domain,
                        $element->getId(),
                        $value->siteId
                    );
                }
            }
        }

        /* EXAMPLE
        Craft::$app->db->createCommand()
            ->upsert(...)
            ->execute();*/

        parent::afterElementSave($element, $isNew); // TODO: Change the autogenerated stub
    }

    /**
     * @inheritdoc
     *
     * @param DomainsQuery $value
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        $input = '<input type="hidden" name="'.$this->handle.'" value="">';

        $tableHtml = $this->getTableHtml($value, $element, false);

        if ($tableHtml) {
            $input .= $tableHtml;
        }

        return $input;
    }

    /**
     * Returns the field's input HTML.
     *
     * @param mixed                 $value
     * @param ElementInterface|null $element
     * @param bool                  $static
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
                'heading' => 'Heading Two',
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

            if ($this->isFresh($element)) {
                $defaults = [];

                if (is_array($defaults)) {
                    $value = array_values($defaults);
                }
            }

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

    public static function hasContentColumn(): bool
    {
        return false;
    }

    public function getSearchKeywords($value, ElementInterface $element): string
    {
        return '';
    }
}
