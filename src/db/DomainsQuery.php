<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/domains/organization/
 */

namespace flipbox\domains\db;

use Craft;
use craft\base\ElementInterface;
use craft\db\FixedOrderExpression;
use craft\db\Query;
use craft\db\QueryAbortedException;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use craft\models\Site;
use yii\base\Exception;

class DomainsQuery extends Query
{

    /**
     * @var int|int[]|false|null The record ID(s). Prefix IDs with "not " to exclude them.
     */
    public $id;

    /**
     * @var string|string[]|false|null The domain(s). Prefix domains with "not " to exclude them.
     */
    public $domain;

    /**
     * @var int|int[]|false|null The element ID(s). Prefix IDs with "not " to exclude them.
     */
    public $elementId;

    /**
     * @var string|string[]|null The element UID(s). Prefix UIDs with "not " to exclude them.
     */
    public $uid;

    /**
     * @var bool Whether results should be returned in the order specified by [[id]].
     */
    public $fixedOrder = false;

    /**
     * @var mixed When the resulting elements must have been created.
     */
    public $dateCreated;

    /**
     * @var mixed When the resulting elements must have been last updated.
     */
    public $dateUpdated;

    /**
     * @var int|null The site ID that the elements should be returned in.
     */
    public $siteId;

    /**
     * @inheritdoc
     */
    public $orderBy = '';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->select === null) {
            // Use ** as a placeholder for "all the default columns"
            $this->select = ['**'];
        }
    }

    /**
     * @inheritdoc
     */
    public function id($value)
    {
        $this->id = $value;

        return $this;
    }

    /**
     * @inheritdoc
     * @throws Exception if $value is an invalid site handle
     */
    public function element($value)
    {
        if ($value instanceof ElementInterface) {
            $this->elementId = $value->id;
        } else {
            $element = Craft::$app->getElements()->getElementById($value);

            if (!$element) {
                throw new Exception('Invalid element: ' . $value);
            }

            $this->elementId = $element->getId();
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function elementId($value)
    {
        $this->elementId = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function domain($value)
    {
        $this->domain = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function uid($value)
    {
        $this->uid = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function fixedOrder(bool $value = true)
    {
        $this->fixedOrder = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function dateCreated($value)
    {
        $this->dateCreated = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function dateUpdated($value)
    {
        $this->dateUpdated = $value;

        return $this;
    }

    /**
     * @inheritdoc
     * @throws Exception if $value is an invalid site handle
     */
    public function site($value)
    {
        if ($value instanceof Site) {
            $this->siteId = $value->id;
        } else {
            $site = Craft::$app->getSites()->getSiteByHandle($value);

            if (!$site) {
                throw new Exception('Invalid site handle: ' . $value);
            }

            $this->siteId = $site->id;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function siteId(int $value = null)
    {
        $this->siteId = $value;

        return $this;
    }

    // Query preparation/execution
    // -------------------------------------------------------------------------

    /**
     * @inheritdoc
     *
     * @throws QueryAbortedException if it can be determined that there won’t be any results
     */
    public function prepare($builder)
    {
        // Is the query already doomed?
        if ($this->id !== null && empty($this->id)) {
            throw new QueryAbortedException();
        }

        // Build the query
        // ---------------------------------------------------------------------

        if ($this->id) {
            $this->andWhere(Db::parseParam('id', $this->id));
        }

        if ($this->domain) {
            $this->andWhere(Db::parseDateParam('domain', $this->domain));
        }

        if ($this->elementId) {
            $this->andWhere(Db::parseDateParam('elementId', $this->elementId));
        }

        if ($this->siteId) {
            $this->andWhere(Db::parseDateParam('siteId', $this->siteId));
        } else {
            $this->siteId = Craft::$app->getSites()->currentSite->id;
        }

        if ($this->uid) {
            $this->andWhere(Db::parseParam('uid', $this->uid));
        }

        if ($this->dateCreated) {
            $this->andWhere(Db::parseDateParam('dateCreated', $this->dateCreated));
        }

        if ($this->dateUpdated) {
            $this->andWhere(Db::parseDateParam('dateUpdated', $this->dateUpdated));
        }

        $this->_applyOrderByParams();
    }

    /**
     * Applies the 'fixedOrder' and 'orderBy' params to the query being prepared.
     *
     * @throws Exception if the DB connection doesn't support fixed ordering
     * @throws QueryAbortedException
     */
    private function _applyOrderByParams()
    {
        if ($this->orderBy === null) {
            return;
        }

        // Any other empty value means we should set it
        if (empty($this->orderBy)) {
            if ($this->fixedOrder) {
                $ids = $this->id;
                if (!is_array($ids)) {
                    $ids = is_string($ids) ? StringHelper::split($ids) : [$ids];
                }

                if (empty($ids)) {
                    throw new QueryAbortedException;
                }

                $this->orderBy = [new FixedOrderExpression('id', $ids, $db)];
            } else {
                $this->orderBy = ['elements.dateCreated' => SORT_DESC];
            }
        }

        if (!empty($this->orderBy)) {
            // In case $this->orderBy was set directly instead of via orderBy()
            $orderBy = $this->normalizeOrderBy($this->orderBy);
            $orderByColumns = array_keys($orderBy);

            $orderColumnMap = [];

            // Prevent “1052 Column 'id' in order clause is ambiguous” MySQL error
            $orderColumnMap['id'] = 'elements.id';

            foreach ($orderColumnMap as $orderValue => $columnName) {
                // Are we ordering by this column name?
                $pos = array_search($orderValue, $orderByColumns, true);

                if ($pos !== false) {
                    // Swap it with the mapped column name
                    $orderByColumns[$pos] = $columnName;
                    $orderBy = array_combine($orderByColumns, $orderBy);
                }
            }
        }

        if (!empty($orderBy)) {
            $this->orderBy($orderBy);
        }
    }
}
