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
use flipbox\ember\records\traits\ElementAttribute;
use flipbox\ember\traits\SiteRules;

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
    use SiteRules,
        ElementAttribute,
        traits\FieldAttribute;

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
    protected $getterPriorityAttributes = ['fieldId', 'elementId', 'siteId'];

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
            $this->elementRules(),
            $this->fieldRules(),
            [
                [
                    [
                        self::TARGET_ATTRIBUTE,
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
                    self::TARGET_ATTRIBUTE,
                    'unique',
                    'targetAttribute' => [
                        'elementId',
                        'siteId',
                        self::TARGET_ATTRIBUTE
                    ]
                ],
                [
                    [
                        self::TARGET_ATTRIBUTE,
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
}
