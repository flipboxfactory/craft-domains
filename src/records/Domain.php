<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\craft\domains\records;

use Craft;
use flipbox\craft\ember\helpers\ModelHelper;
use flipbox\craft\ember\records\ActiveRecord;
use flipbox\craft\ember\records\ElementAttributeTrait;
use flipbox\craft\ember\records\FieldAttributeTrait;
use flipbox\craft\ember\records\SiteAttributeTrait;
use flipbox\craft\ember\records\SortableTrait;
use flipbox\craft\domains\queries\DomainsQuery;
use flipbox\craft\domains\fields\Domains;
use flipbox\craft\domains\validators\DomainValidator;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since  1.0.0
 *
 * @property string $domain
 * @property string $status
 * @property int $sortOrder
 */
class Domain extends ActiveRecord
{
    use SiteAttributeTrait,
        ElementAttributeTrait,
        FieldAttributeTrait,
        SortableTrait;

    /**
     * @inheritdoc
     */
    const TABLE_ALIAS = 'domains';

    /**
     * @inheritdoc
     */
    protected $getterPriorityAttributes = ['fieldId', 'elementId', 'siteId'];

    /**
     * @noinspection PhpDocMissingThrowsInspection
     * @return DomainsQuery
     */
    public static function find(): DomainsQuery
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        /** @noinspection PhpUnhandledExceptionInspection */
        return Craft::createObject(DomainsQuery::class, [get_called_class()]);
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return array_merge(
            parent::rules(),
            $this->siteRules(),
            $this->elementRules(),
            $this->fieldRules(),
            [
                [
                    [
                        'domain',
                        'status',
                    ],
                    'required'
                ],
                [
                    'domain',
                    DomainValidator::class
                ],
                [
                    'status',
                    'in',
                    'range' => array_keys(Domains::getStatuses())
                ],
                [
                    [
                        'domain',
                        'status',
                    ],
                    'safe',
                    'on' => [
                        ModelHelper::SCENARIO_DEFAULT
                    ]
                ]
            ]
        );
    }

    /**
     * @inheritdoc
     */
    public function beforeSave($insert)
    {
        $this->ensureSortOrder(
            [
                'elementId' => $this->elementId,
                'fieldId' => $this->fieldId,
                'siteId' => $this->siteId
            ]
        );

        return parent::beforeSave($insert);
    }

    /**
     * @inheritdoc
     * @throws \yii\db\Exception
     */
    public function afterSave($insert, $changedAttributes)
    {
        $this->autoReOrder(
            'domain',
            [
                'elementId' => $this->elementId,
                'fieldId' => $this->fieldId,
                'siteId' => $this->siteId
            ]
        );

        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @inheritdoc
     * @throws \yii\db\Exception
     */
    public function afterDelete()
    {
        $sortOrderAttribute = 'sortOrder';
        $targetAttribute = 'domain';
        $sortOrderCondition = [
            'elementId' => $this->elementId,
            'fieldId' => $this->fieldId,
            'siteId' => $this->siteId
        ];

        // All records (sorted)
        $sortOrder = $this->sortOrderQuery($sortOrderCondition, $sortOrderAttribute)
            ->indexBy($targetAttribute)
            ->select([$sortOrderAttribute])
            ->column();

        if(count($sortOrder) > 0) {
            $this->saveNewOrder(
                array_flip(array_combine(
                    range($sortOrder, count($sortOrder)),
                    array_keys($sortOrder)
                )),
                $targetAttribute,
                $sortOrderCondition,
                $sortOrderAttribute
            );
        }

        parent::afterDelete();
    }
}
