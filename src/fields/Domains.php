<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\base\FieldInterface;
use craft\elements\db\ElementQueryInterface;
use flipbox\domains\db\DomainsQuery;
use flipbox\domains\Domains as DomainsPlugin;
use flipbox\domains\validators\DomainsValidator;
use flipbox\ember\validators\MinMaxValidator;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Domains extends Field implements FieldInterface
{
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
     * @inheritdoc
     */
    public function modifyElementsQuery(ElementQueryInterface $query, $value)
    {
        return DomainsPlugin::getInstance()->getFields()->modifyElementsQuery($this, $query, $value);
    }

    /**
     * @inheritdoc
     * @return DomainsQuery
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        return DomainsPlugin::getInstance()->getFields()->normalizeValue($this, $value, $element);
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
     */
    public function getSettingsHtml()
    {
        return DomainsPlugin::getInstance()->getFields()->getSettingsHtml($this);
    }

    /**
     * @param DomainsQuery $value
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        return DomainsPlugin::getInstance()->getFields()->getInputHtml($this, $value, false);
    }


    /*******************************************
     * EVENTS
     *******************************************/

    /**
     * @inheritdoc
     */
    public function afterElementSave(ElementInterface $element, bool $isNew)
    {
        DomainsPlugin::getInstance()->getAssociations()->save(
            $element->{$this->handle},
            false
        );

        parent::afterElementSave($element, $isNew);
    }
}
