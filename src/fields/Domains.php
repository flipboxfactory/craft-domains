<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\craft\domains\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\FieldInterface;
use craft\helpers\ArrayHelper;
use flipbox\craft\ember\validators\MinMaxValidator;
use flipbox\craft\domains\queries\DomainsQuery;
use flipbox\craft\domains\records\Domain;
use flipbox\craft\domains\validators\DomainsValidator;
use yii\base\Exception;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Domains extends Field implements FieldInterface
{
    use NormalizeValueTrait,
        ModifyElementsQueryTrait;

    /**
     * @var bool
     */
    public $unique = true;

    /**
     * @var int|null
     */
    public $min;

    /**
     * @var int|null
     */
    public $max;

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

    /**
     * @inheritdoc
     */
    public function getElementValidationRules(): array
    {
        return [
            [
                DomainsValidator::class,
                'field' => $this
            ],
            [
                MinMaxValidator::class,
                'min' => $this->min ? (int)$this->min : null,
                'max' => $this->max ? (int)$this->max : null,
                'tooFew' => Craft::t(
                    'domains',
                    '{attribute} should contain at least {min, number} {min, plural, one{domain} other{domains}}.'
                ),
                'tooMany' => Craft::t(
                    'domains',
                    '{attribute} should contain at most {max, number} {max, plural, one{domain} other{domains}}.'
                ),
                'skipOnEmpty' => false
            ]
        ];
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
     * @inheritdoc
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function getSettingsHtml()
    {

        return Craft::$app->getView()->renderTemplate(
            'domains/_components/fieldtypes/Domains/settings',
            [
                'field' => $this
            ]
        );
    }

    /**
     * @inheritdoc
     * @param DomainsQuery $value
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        return $this->renderInputHtml($value, false);
    }

    /**
     * @param DomainsQuery $query
     * @param bool $static
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    protected function renderInputHtml(DomainsQuery $query, bool $static)
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
                'class' => 'thin',
                'type' => 'select',
                'options' => $this->getStatuses()
            ]
        ];

        // Translate the column headings
        foreach ($columns as &$column) {
            $heading = (string)$column['heading'];
            if ($heading !== null) {
                $column['heading'] = Craft::t('site', $heading);
            }
        }
        unset($column);

        return Craft::$app->getView()->renderTemplate(
            'domains/_components/fieldtypes/Domains/input',
            [
                'id' => Craft::$app->getView()->formatInputId($this->handle),
                'name' => $this->handle,
                'cols' => $columns,
                'rows' => $query->all(),
                'static' => $static,
                'field' => $this
            ]
        );
    }

    /*******************************************
     * EVENTS
     *******************************************/

    /**
     * @param ElementInterface $element
     * @param bool $isNew
     * @return bool|void
     * @throws \Throwable
     */
    public function afterElementSave(ElementInterface $element, bool $isNew)
    {
        /** @var DomainsQuery $query */
        $query = $element->getFieldValue($this->handle);

        // Cached results
        if (null === ($records = $query->getCachedResult())) {
            parent::afterElementSave($element, $isNew);
            return;
        }

        $currentDomains = [];
        if ($isNew === false) {
            /** @var DomainsQuery $existingQuery */
            $existingQuery = Domain::find();
            $existingQuery->element = $query->element;
            $existingQuery->field = $query->field;
            $existingQuery->site = $query->site;
            $existingQuery->indexBy = 'domain';

            $currentDomains = $existingQuery->all();
        }

        $success = true;
        if (empty($records)) {
            foreach ($currentDomains as $currentDomain) {
                if (!$currentDomain->delete()) {
                    $success = false;
                }
            }

            if (!$success) {
                $this->addError('types', 'Unable to delete domain.');
                throw new Exception('Unable to delete domain.');
            }

            parent::afterElementSave($element, $isNew);
            return;
        }

        $newDomains = [];
        $order = 1;

        foreach ($records as $record) {
            if (null === ($domain = ArrayHelper::remove($currentDomains, $record->domain))) {
                $domain = $record;
            }
            $domain->sortOrder = $order++;
            $domain->status = $record->status;
            $newDomains[] = $domain;
        }

        // DeleteOrganization those removed
        foreach ($currentDomains as $currentDomain) {
            if (!$currentDomain->delete()) {
                $success = false;
            }
        }

        foreach ($newDomains as $domain) {
            if (!$domain->save()) {
                $success = false;
            }
        }

        if (!$success) {
            $this->addError('users', 'Unable to save domains.');
            throw new Exception('Unable to save domains.');
        }
    }
}
