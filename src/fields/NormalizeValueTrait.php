<?php
/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://github.com/flipboxfactory/craft-sortable-associations/blob/master/LICENSE
 * @link       https://github.com/flipboxfactory/craft-sortable-associations
 */

namespace flipbox\craft\domains\fields;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\helpers\ArrayHelper;
use flipbox\craft\domains\queries\DomainsQuery;
use flipbox\craft\domains\records\Domain;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 *
 * @property int $id
 * @property string $defaultStatus
 */
trait NormalizeValueTrait
{
    /**
     * @inheritdoc
     */
    public function getQuery(
        ElementInterface $element = null
    ): DomainsQuery {

        $query = Domain::find()
            ->setSiteId($this->targetSiteId($element))
            ->setFieldId($this->id);

        return $query;
    }

    /**
     * @param $value
     * @param ElementInterface|null $element
     * @return DomainsQuery
     */
    public function normalizeValue(
        $value,
        ElementInterface $element = null
    ): DomainsQuery {
        if ($value instanceof DomainsQuery) {
            return $value;
        }
        $query = $this->getQuery($element);

        $this->normalizeQueryValue($query, $value, $element);

        return $query;
    }

    /**
     * @param DomainsQuery $query
     * @param ElementInterface|null $element
     */
    protected function normalizeQuery(
        DomainsQuery $query,
        ElementInterface $element = null
    ) {
        $query->element = (
            $element === null || $element->getId() === null
        ) ? false : $element->getId();
    }

    /**
     * @param DomainsQuery $query
     * @param $value
     * @param ElementInterface|null $element
     */
    protected function normalizeQueryValue(
        DomainsQuery $query,
        $value,
        ElementInterface $element = null
    ) {
        $this->normalizeQuery($query, $element);

        if (is_array($value)) {
            $this->normalizeQueryInputValues($query, $value, $element);
            return;
        }

        if ($value === '') {
            $this->normalizeQueryEmptyValue($query);
            return;
        }
    }

    /**
     * @param DomainsQuery $query
     * @param array $value
     * @param ElementInterface|null $element
     */
    protected function normalizeQueryInputValues(
        DomainsQuery $query,
        array $value,
        ElementInterface $element = null
    ) {
        $records = [];
        $sortOrder = 1;

        foreach ($value as $val) {
            $records[] = $this->normalizeQueryInputValue($val, $sortOrder, $element);
        }
        $query->setCachedResult($records);
    }

    /**
     * @inheritdoc
     */
    protected function normalizeQueryInputValue(
        $value,
        int &$sortOrder,
        ElementInterface $element = null
    ): Domain {
        if (!is_array($value)) {
            $value = [
                'domain' => $value,
                'status' => $this->defaultStatus
            ];
        }

        $domain = ArrayHelper::getValue($value, 'domain');
        $status = ArrayHelper::getValue($value, 'status');

        $record = Domain::find()
            ->setDomain($domain)
            ->setField($this)
            ->setElement($element)
            ->setSiteId($this->targetSiteId($element))
            ->one();

        if (empty($record)) {
            $record = new Domain([
                'field' => $this,
                'element' => $element,
                'domain' => $domain,
                'siteId' => $this->targetSiteId($element)
            ]);
        }

        $record->status = $status;
        $record->sortOrder = $sortOrder++;

        return $record;
    }

    /**
     * @param DomainsQuery $query
     */
    protected function normalizeQueryEmptyValue(
        DomainsQuery $query
    ) {
        $query->setCachedResult([]);
    }

    /**
     * Returns the site ID that target elements should have.
     *
     * @param ElementInterface|Element|null $element
     *
     * @return int
     */
    protected function targetSiteId(ElementInterface $element = null): int
    {
        /** @var Element $element */
        if (Craft::$app->getIsMultiSite() === true && $element !== null) {
            return $element->siteId;
        }
        return Craft::$app->getSites()->currentSite->id;
    }
}