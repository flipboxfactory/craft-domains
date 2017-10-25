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
     * @var array|null The columns that should be shown in the table
     */
    public $columns = [
        'col1' => [
            'heading' => 'Domain',
            'handle' => 'domain',
            'type' => 'singleline'
        ]
    ];

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
        $value = $element->getFieldValue($this->handle);

        // TODO remove everything before or after URL.com and set as submitted data

        // TODO validate all submitted domains will be unique after save

        /* ADD ERRORS EXAMPLE
        $element->addError($this->handle, Craft::t('app', '"{filename}" is not allowed in this field.', [
            'filename' => $filename
        ]));*/
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

        // Multisite
        $query->siteId($this->targetSiteId($element));

        // $value will be an array of domains
        if (is_array($value)) {
            $models = [];
            foreach ($value as $val) {
                $models[] = new Domain([
                    'domain' => $val,
                    'element' => $element
                ]);
            }
            $query->setCachedResult($models);
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

    public function afterElementSave(ElementInterface $element, bool $isNew)
    {
        // Get domains
        /** @var DomainsQuery $value */
        $value = $element->getFieldValue($this->handle);

        // TODO update domains list
            // delete any removed
            // upsert new/existing

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

        var_dump($value->all());

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
        $columns = $this->columns;

        if (!empty($columns)) {
            // Translate the column headings
            foreach ($columns as &$column) {
                if (!empty($column['heading'])) {
                    $column['heading'] = Craft::t('site', $column['heading']);
                }
            }
            unset($column);

            if ($this->isFresh($element)) {
                $defaults = $this->defaults;

                if (is_array($defaults)) {
                    $value = array_values($defaults);
                }
            }

            $id = Craft::$app->getView()->formatInputId($this->handle);

            return Craft::$app->getView()->renderTemplate(
                '_includes/forms/editableTable',
                [
                    'id' => $id,
                    'name' => $this->handle,
                    'cols' => $columns,
                    'rows' => $value,
                    'static' => $static
                ]
            );
        }

        return null;
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
