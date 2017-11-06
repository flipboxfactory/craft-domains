<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\models;

use Craft;
use craft\base\ElementInterface;
use flipbox\domains\Domains as DomainsPlugin;
use flipbox\domains\fields\Domains;
use flipbox\domains\validators\DomainValidator;
use flipbox\spark\helpers\ModelHelper;
use flipbox\spark\models\Model;
use flipbox\spark\traits\ElementAttribute;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since  1.0.0
 */
class Domain extends Model
{
    use ElementAttribute;

    /**
     * @var string
     */
    public $domain;

    /**
     * @var int|null
     */
    public $siteId;

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
    public function __construct(Domains $field, array $config = [])
    {
        $this->field = $field;
        parent::__construct($config);
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
     * @return Domains
     */
    public function getField(): Domains
    {
        return $this->field;
    }

    /**
     * @return array
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            $this->elementAttributes(),
            [
                'domain',
            ]
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
            [
                'domain' => Craft::t('domains', 'Domain'),
            ]
        );
    }
}
