<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\validators;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use flipbox\domains\db\DomainsQuery;
use flipbox\domains\fields\Domains;
use flipbox\domains\models\Domain;
use yii\base\Exception;
use yii\base\Model;
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
     * @param Model $model
     * @param string $attribute
     * @throws Exception
     */
    public function validateAttribute($model, $attribute)
    {
        if (!$model instanceof ElementInterface) {
            throw new Exception(sprintf(
                "Model must be an instance of '%s'.",
                (string)ElementInterface::class
            ));
        }

        /** @var ElementInterface $model */
        $this->validateElementAttribute($model, $attribute);
    }

    /**
     * @param ElementInterface $element
     * @param string $attribute
     * @throws Exception
     */
    protected function validateElementAttribute(ElementInterface $element, string $attribute)
    {
        /** @var Element $element */
        $value = $element->getFieldValue($attribute);

        if (!$value instanceof DomainsQuery) {
            throw new Exception(sprintf(
                "Field value must be an instance of '%s'.",
                (string)DomainsQuery::class
            ));
        }

        $this->validateQuery($value, $element, $attribute);
    }

    /**
     * @param DomainsQuery $query
     * @param ElementInterface $element
     * @param string $attribute
     */
    protected function validateQuery(DomainsQuery $query, ElementInterface $element, string $attribute)
    {
        if (null !== ($cachedResult = $query->getCachedResult())) {
            $domains = [];
            foreach ($cachedResult as $model) {
                $this->validateDomainModel($model, $element, $attribute);
                $domains[$model->domain] = $model->domain;
            }

            if ($this->field->unique === true) {
                $this->validateUniqueDomain(array_keys($domains), $element, $attribute);
            }
        }
    }

    /**
     * @param Domain $domain
     * @param ElementInterface $element
     * @param string $attribute
     */
    protected function validateDomainModel(Domain $domain, ElementInterface $element, string $attribute)
    {
        /** @var Element $element */
        if (!$domain->validate(['domain', 'status'])) {
            if ($domain->hasErrors('domain')) {
                $this->addError(
                    $element,
                    $attribute,
                    $domain->getFirstError('domain')
                );
            }

            if ($domain->hasErrors('status')) {
                $this->addError(
                    $element,
                    $attribute,
                    $domain->getFirstError('status')
                );
            }
        }
    }

    protected function validateUniqueDomain(array $domains, ElementInterface $element, string $attribute)
    {
        /** @var Element $element */
        $domainQuery = (new DomainsQuery($this->field))
            ->select(['elementId', 'domain'])
            ->andWhere(['domain' => $domains]);

        // Ignore this element
        if (null !== ($existingElementId = $element->getId())) {
            $domainQuery->andWhere([
                '!=',
                'elementId',
                $existingElementId
            ]);
        }

        if (null !== ($domain = $domainQuery->one())) {
            /** @var Domain $domain */
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
