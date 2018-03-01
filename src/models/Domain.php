<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\models;

use Craft;
use flipbox\domains\fields\Domains;
use flipbox\domains\validators\DomainValidator;
use flipbox\ember\helpers\ModelHelper;
use flipbox\ember\models\Model;
use flipbox\ember\traits\ElementAttribute;
use flipbox\ember\traits\SiteAttribute;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since  1.0.0
 */
class Domain extends Model
{
    use ElementAttribute,
        SiteAttribute;

    /**
     * @var string
     */
    public $domain;

    /**
     * @var string|null
     */
    public $status;

    /**
     * @var int|null
     */
    public $sortOrder;

    /**
     * @var Domains
     */
    private $field;

    /**
     * @inheritdoc
     */
    public function __construct(Domains $field, $config = [])
    {
        $this->field = $field;
        parent::__construct($config);
    }

    /**
     * @return Domains
     */
    public function getField(): Domains
    {
        return $this->field;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return array_merge(
            parent::rules(),
            $this->elementRules(),
            [
                [
                    [
                        'domain',
                        'status',
                        'elementId'
                    ],
                    'required'
                ],
                [
                    [
                        'siteId',
                        'sortOrder'
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
                    'range' => array_keys($this->field->getStatuses())
                ],
                [
                    [
                        'domain',
                        'status',
                        'siteId',
                        'sortOrder'
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
            parent::attributes(),
            $this->elementAttributes(),
            $this->siteAttributes()
        );
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return array_merge(
            parent::attributeLabels(),
            $this->elementAttributeLabels(),
            $this->siteAttributeLabels(),
            [
                'domain' => Craft::t('domains', 'Domain'),
                'status' => Craft::t('domains', 'Status'),
                'sortOrder' => Craft::t('domains', 'Sort Order'),
            ]
        );
    }
}
