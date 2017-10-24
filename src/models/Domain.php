<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\models;

use Craft;
use craft\base\ElementInterface;
use flipbox\spark\helpers\ModelHelper;
use flipbox\spark\models\ModelWithId;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Domain extends ModelWithId
{
    /**
     * @var string
     */
    public $domain;

    /**
     * @var ElementInterface|null
     */
    protected $element;

    /**
     * @var int|null
     */
    protected $elementId;

    /**
     * @var int|null
     */
    public $siteId;

    /**
     * @return array
     */
    protected function userRules(): array
    {
        return [
            [
                [
                    'domain',
                    'elementId'
                ],
                'required'
            ],
            [
                [
                    'siteId',
                    'elementId'
                ],
                'number',
                'integerOnly' => true
            ],
            [
                [
                    'domain',
                    'siteId',
                    'elementId',
                    'element'
                ],
                'safe',
                'on' => [
                    ModelHelper::SCENARIO_DEFAULT
                ]
            ]
        ];
    }

    /**
     * @return ElementInterface|null
     */
    public function getElement()
    {
        if (is_null($this->element)) {
            if (!empty($this->elementId)) {
                if ($element = Craft::$app->getElements()->getElementById($this->elementId)) {
                    $this->setElement($element);
                } else {
                    $this->elementId = null;
                    $this->element = false;
                }
            } else {
                $this->element = false;
            }
        } else {
            if ($this->elementId &&
                (($this->element === false) || ($this->elementId !== $this->element->getId()))
            ) {
                $this->element = null;
                return $this->getElement();
            }
        }

        return $this->element instanceof ElementInterface ? $this->element : null;
    }

    /**
     * Associate an element to the element
     *
     * @param $element
     * @return $this
     */
    public function setElement($element)
    {
        if (!$this->element = $this->findElement($element)) {
            $this->elementId = null;
            return $this;
        }

        $this->elementId = $this->element->getId();

        return $this;
    }

    /**
     * Associate an element to the element
     *
     * @param int $elementId
     * @return $this
     */
    public function setElementId(int $elementId)
    {
        if ($elementId !== $elementId) {
            $this->elementId = $elementId;
            $this->element = null;
        }
        return $this;
    }

    /**
     * @return int|null
     */
    public function getElementId()
    {
        return $this->elementId;
    }

    /**
     * @param string|int|ElementInterface $identifier
     * @return ElementInterface|null
     */
    private function findElement($identifier)
    {
        // Element
        if ($identifier instanceof ElementInterface) {
            return $identifier;

            // Id
        } elseif (is_numeric($identifier)) {
            return Craft::$app->getElements()->getElementById($identifier);

            // uri
        } elseif (!is_null($identifier)) {
            return Craft::$app->getElements()->getElementByUri($identifier);
        }

        return null;
    }

    /**
     * @return array
     */
    public function attributes()
    {
        return array_merge(
            parent::attributes(),
            [
                'domain',
                'elementId'
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
            [
                'domain' => Craft::t('domains', 'Domain'),
                'elementId' => Craft::t('domains', 'Element Id')
            ]
        );
    }
}
