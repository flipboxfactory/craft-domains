<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\records;

use Craft;
use flipbox\craft\sourceTarget\records\SortableAssociation;
use flipbox\domains\db\DomainsQuery;
use flipbox\domains\Domains as DomainsPlugin;
use flipbox\domains\fields\Domains;
use flipbox\domains\validators\DomainValidator;
use flipbox\ember\helpers\ModelHelper;
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
     * {@inheritdoc}
     * @return DomainsQuery
     */
    public static function find()
    {
        return DomainsPlugin::getInstance()->getAssociations()->getQuery();
    }

    /**
     * @inheritdoc
     */
    public function associate(bool $autoReorder = true): bool
    {
        return DomainsPlugin::getInstance()->getAssociations()->associate(
            $this,
            $autoReorder
        );
    }

    /**
     * @inheritdoc
     */
    public function dissociate(bool $autoReorder = true): bool
    {
        return DomainsPlugin::getInstance()->getAssociations()->dissociate(
            $this,
            $autoReorder
        );
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
}
