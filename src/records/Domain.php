<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\records;

use Craft;
use flipbox\craft\sortable\associations\records\SortableAssociation;
use flipbox\craft\sortable\associations\services\SortableAssociations;
use flipbox\domains\db\DomainsQuery;
use flipbox\domains\Domains as DomainsPlugin;
use flipbox\domains\fields\Domains;
use flipbox\domains\validators\DomainValidator;
use flipbox\ember\helpers\ModelHelper;
use flipbox\ember\traits\SiteRules;
use flipbox\ember\validators\LimitValidator;
use yii\base\InvalidArgumentException;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since  1.0.0
 *
 * @property int $fieldId
 * @property string $domain
 * @property int $elementId
 */
class Domain extends SortableAssociation
{
    use SiteRules;

    /**
     * @inheritdoc
     */
    const TABLE_ALIAS = 'domains';

    /**
     * @inheritdoc
     */
    const TARGET_ATTRIBUTE = 'domain';

    /**
     * @inheritdoc
     */
    const SOURCE_ATTRIBUTE = 'elementId';

    /**
     * @inheritdoc
     */
    protected function associationService(): SortableAssociations
    {
        return DomainsPlugin::getInstance()->getAssociations();
    }

    /**
     * {@inheritdoc}
     * @return DomainsQuery
     */
    public static function find()
    {
        return DomainsPlugin::getInstance()->getAssociations()->getQuery();
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return array_merge(
            parent::rules(),
            $this->siteRules(),
            [
                [
                    [
                        'status',
                        'fieldId',
                    ],
                    'required'
                ],
                [
                    [
                        'fieldId'
                    ],
                    'number',
                    'integerOnly' => true
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
                        'fieldId'
                    ],
                    LimitValidator::class,
                    'query' => function (Domain $model) {
                        return $model::find()
                            ->field($model->fieldId)
                            ->element($model->elementId)
                            ->site($model->siteId)
                            ->andWhere([
                                '!=',
                                static::TARGET_ATTRIBUTE,
                                $model->{static::TARGET_ATTRIBUTE}
                            ]);
                    },
                    'limit' => function (Domain $model) {
                        return $this->getFieldLimit($model->fieldId);
                    },
                    'message' => "Limit exceeded."
                ],
                [
                    [
                        'fieldId',
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
     * @param int $fieldId
     * @return Domains
     */
    protected function resolveField(int $fieldId): Domains
    {
        $field = Craft::$app->getFields()->getFieldById($fieldId);

        if ($field === null || !$field instanceof Domains) {
            throw new InvalidArgumentException(sprintf(
                "Field must be an instance of '%s', '%s' given.",
                Domains::class,
                $field ? get_class($field) : 'FIELD NOT FOUND'
            ));
        }

        return $field;
    }

    /**
     * @param int $fieldId
     * @return int
     */
    protected function getFieldLimit(int $fieldId): int
    {
        return (int) $this->resolveField($fieldId)->limit;
    }
}
