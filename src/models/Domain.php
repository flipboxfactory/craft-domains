<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\models;

use Craft;
use flipbox\craft\sourceTarget\models\AbstractAssociationModelWithField;
use flipbox\domains\fields\Domains;
use flipbox\domains\validators\DomainValidator;
use flipbox\ember\helpers\ModelHelper;
use flipbox\ember\traits\AuditAttributes;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since  1.0.0
 *
 * @property Domains $field
 */
class Domain extends AbstractAssociationModelWithField
{
    use AuditAttributes;

    /**
     * @inheritdoc
     */
    const TARGET_ATTRIBUTE = 'domain';

    /**
     * @inheritdoc
     */
    const SOURCE_ATTRIBUTE = 'elementId';

    /**
     * @var int
     */
    public $elementId;

    /**
     * @var string
     */
    public $domain;

    /**
     * @var string|null
     */
    public $status;

    /**
     * @return array
     */
    public function rules(): array
    {
        return array_merge(
            parent::rules(),
            [
                [
                    [
                        'status'
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
                    'range' => array_keys($this->field->getStatuses())
                ],
                [
                    [
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
     * @return array
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes()
        );
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(
            parent::attributeLabels(),
            [
                'domain' => Craft::t('domains', 'Domain'),
                'status' => Craft::t('domains', 'Status')
            ]
        );
    }
}
