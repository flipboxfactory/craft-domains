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
use flipbox\domains\Domains as DomainsPlugin;
use flipbox\domains\fields\Domains;
use flipbox\domains\models\Domain;
use yii\base\ArrayableTrait;
use yii\base\Exception;
use yii\db\Connection;

class DomainsQuery extends Query
{
    use ArrayableTrait;

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
     * @var Domain[]|null The cached query result
     * @see setCachedResult()
     */
    private $result;

    /**
     * @var Domain[]|null The criteria params that were set when the cached query result was set
     * @see setCachedResult()
     */
    private $resultCriteria;

    /**
     * @var Domains
     */
    private $field;

    /**
     * @inheritdoc
     */
    public function __construct(Domains $domains, $config = [])
    {
        $this->field = $domains;
        parent::__construct($config);
    }

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->select === null) {
            // Use ** as a placeholder for "all the default columns"
            $this->select = ['*'];
        }

        // Set table name
        if ($this->from === null) {
            $fieldService = DomainsPlugin::getInstance()->getField();
            $this->from([
                $fieldService->getTableAlias($this->field).' '. $fieldService->getTableName($this->field)
            ]);
        }
    }

    /**
     * @inheritdoc
     * @throws Exception if $value is an invalid site handle
     * return static
     */
    public function element($value)
    {
        if ($value instanceof ElementInterface) {
            $this->elementId = $value->id;
        } else {
            $element = Craft::$app->getElements()->getElementById($value);

            if (!$element) {
                throw new Exception('Invalid element: '.$value);
            }

            $this->elementId = $element->getId();
        }

        return $this;
    }

    /**
     * @inheritdoc
     * return static
     */
    public function elementId($value)
    {
        $this->elementId = $value;

        return $this;
    }

    /**
     * @inheritdoc
     * return static
     */
    public function domain($value)
    {
        $this->domain = $value;

        return $this;
    }

    /**
     * @inheritdoc
     * return static
     */
    public function uid($value)
    {
        $this->uid = $value;

        return $this;
    }

    /**
     * @inheritdoc
     * return static
     */
    public function fixedOrder(bool $value = true)
    {
        $this->fixedOrder = $value;

        return $this;
    }

    /**
     * @inheritdoc
     * return static
     */
    public function dateCreated($value)
    {
        $this->dateCreated = $value;

        return $this;
    }

    /**
     * @inheritdoc
     * return static
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
                throw new Exception('Invalid site handle: '.$value);
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
        if ($this->domain !== null && empty($this->domain)) {
            throw new QueryAbortedException();
        }

        // Build the query
        // ---------------------------------------------------------------------

        if ($this->domain) {
            $this->andWhere(Db::parseParam('domain', $this->domain));
        }

        if ($this->elementId) {
            $this->andWhere(Db::parseParam('elementId', $this->elementId));
        }

        if ($this->siteId) {
            $this->andWhere(Db::parseParam('siteId', $this->siteId));
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

        $this->applyOrderByParams($builder->db);

        return parent::prepare($builder);
    }

    /**
     * Applies the 'fixedOrder' and 'orderBy' params to the query being prepared.
     *
     * @param Connection|null $db The database connection used to generate the SQL statement.
     *                            If this parameter is not given, the `db` application component will be used.
     *
     * @throws Exception if the DB connection doesn't support fixed ordering
     * @throws QueryAbortedException
     */
    private function applyOrderByParams(Connection $db = null)
    {
        if ($this->orderBy === null) {
            return;
        }

        // Any other empty value means we should set it
        if (empty($this->orderBy)) {
            if ($this->fixedOrder) {
                $domains = $this->domain;
                if (!is_array($domains)) {
                    $domains = is_string($domains) ? StringHelper::split($domains) : [$domains];
                }

                if (empty($domains)) {
                    throw new QueryAbortedException;
                }

                $this->orderBy = [new FixedOrderExpression('domain', $domains, $db)];
            } else {
                $this->orderBy = ['dateCreated' => SORT_DESC];
            }
        }

        if (!empty($this->orderBy)) {
            // In case $this->orderBy was set directly instead of via orderBy()
            $orderBy = $this->normalizeOrderBy($this->orderBy);
            $orderByColumns = array_keys($orderBy);

            $orderColumnMap = [];

            // Prevent “1052 Column 'id' in order clause is ambiguous” MySQL error
            $orderColumnMap['domain'] = 'domain';

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

    /**
     * @inheritdoc
     *
     * @return ElementInterface[]|array The resulting elements.
     */
    public function populate($rows)
    {
        if (empty($rows)) {
            return [];
        }

        return $this->createModels(
            parent::populate($rows)
        );
    }

    /**
     * @param $rows
     *
     * @return mixed
     */
    private function createModels($rows)
    {
        $models = [];

        foreach ($rows as $key => $row) {
            $models[$key] = $this->createModel($row);
        }

        return $models;
    }

    /**
     * @param $row
     *
     * @return Domain
     */
    private function createModel($row): Domain
    {
        return new Domain($this->field, $row);
    }

    /**
     * @inheritdoc
     */
    public function count($q = '*', $db = null)
    {
        // Cached?
        if (($cachedResult = $this->getCachedResult()) !== null) {
            return count($cachedResult);
        }

        return parent::count($q, $db) ?: 0;
    }

    /**
     * @inheritdoc
     */
    public function all($db = null)
    {
        // Cached?
        if (($cachedResult = $this->getCachedResult()) !== null) {
            return $cachedResult;
        }

        return parent::all($db);
    }

    /**
     * @inheritdoc
     */
    public function one($db = null)
    {
        // Cached?
        if (($cachedResult = $this->getCachedResult()) !== null) {
            // Conveniently, reset() returns false on an empty array, just like one() should do for an empty result
            return reset($cachedResult);
        }

        $row = parent::one($db);

        if ($row === false) {
            return false;
        }

        return $this->createModel($row);
    }

    /**
     * Executes the query and returns a single row of result at a given offset.
     *
     * @param int             $n  The offset of the row to return. If [[offset]] is set, $offset will be added to it.
     * @param Connection|null $db The database connection used to generate the SQL statement.
     *                            If this parameter is not given, the `db` application component will be used.
     *
     * @return Domain|array|bool The element or row of the query result. False is returned if the query
     * results in nothing.
     */
    public function nth(int $n, Connection $db = null)
    {
        // Cached?
        if (($cachedResult = $this->getCachedResult()) !== null) {
            return $cachedResult[$n] ?? false;
        }

        return parent::nth($n, $db);
    }

    /**
     * Returns the resulting domains set by [[setCachedResult()]], if the criteria params haven’t changed since then.
     *
     * @return Domain[]|null The resulting domains, or null if setCachedResult() was never called or the criteria has
     * changed
     * @see setCachedResult()
     */
    public function getCachedResult()
    {
        if ($this->result === null) {
            return null;
        }

        // Make sure the criteria hasn't changed
        if ($this->resultCriteria !== $this->getCriteria()) {
            $this->result = null;

            return null;
        }

        return $this->result;
    }

    /**
     * Sets the resulting domains.
     *
     * If this is called, [[all()]] will return these domains rather than initiating a new SQL query,
     * as long as none of the parameters have changed since setCachedResult() was called.
     *
     * @param Domain[] $elements The resulting elements.
     *
     * @see getCachedResult()
     */
    public function setCachedResult(array $elements)
    {
        $this->result = $elements;
        $this->resultCriteria = $this->getCriteria();
    }

    /**
     * Returns an array of the current criteria attribute values.
     *
     * @return array
     */
    public function getCriteria(): array
    {
        return $this->toArray($this->criteriaAttributes(), [], false);
    }

    /**
     * Returns the query's criteria attributes.
     *
     * @return string[]
     */
    public function criteriaAttributes(): array
    {
        // By default, include all public, non-static properties that were defined by a sub class, and certain ones
        // in this class
        $class = new \ReflectionClass($this);
        $names = [];

        foreach ($class->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic()) {
                $dec = $property->getDeclaringClass();
                if (($dec->getName() === self::class || $dec->isSubclassOf(self::class))
                ) {
                    $names[] = $property->getName();
                }
            }
        }

        return $names;
    }
}
