<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\validators;

use Craft;
use flipbox\domains\db\DomainsQuery;
use flipbox\domains\fields\Domains;
use yii\base\Exception;
use yii\validators\Validator;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class DomainsValidator extends Validator
{

    /**
     * @var Domains
     */
    public $field;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (!$this->field instanceof Domains) {
            throw new Exception("Field must be an instance of 'Domains'.");
        }
    }

    /**
     * @inheritdoc
     */
    public function validateAttribute($element, $attribute)
    {
        /** @var DomainsQuery $value */
        $value = $element->$attribute;

        // If we have a cached result, let's validate them
        if (($cachedResult = $value->getCachedResult()) !== null) {
            $domains = [];
            foreach ($cachedResult as $model) {
                if (!$model->validate(['domain', 'status'])) {
                    if ($model->hasErrors('domain')) {
                        $this->addError(
                            $element,
                            $attribute,
                            $model->getFirstError('domain')
                        );
                    }

                    if ($model->hasErrors('status')) {
                        $this->addError(
                            $element,
                            $attribute,
                            $model->getFirstError('status')
                        );
                    }
                }

                $domains[$model->domain] = $model->domain;
            }

            if ($this->field->unique === true) {
                $domainQuery = (new DomainsQuery($this->field))
                    ->select(['elementId', 'domain'])
                    ->andWhere(['domain' => $domains]);

                // Ignore this element
                if ($existingElementId = $element->id) {
                    $domainQuery->andWhere([
                        '!=',
                        'elementId',
                        $existingElementId
                    ]);
                }

                if ($domain = $domainQuery->one()) {
                    $this->addError(
                        $element,
                        $attribute,
                        Craft::t(
                            'domains',
                            "Domain '{domain}' is already in use by element {elementId}.",
                            [
                                'domain' => $domain->domain,
                                'elementId' => $domain->getElementId()
                            ]
                        )
                    );
                }
            }
        }
    }
}
