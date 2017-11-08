<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\db;

use craft\db\Connection as CraftConnection;
use craft\db\FixedOrderExpression;
use craft\db\Query;
use craft\db\QueryAbortedException;
use craft\helpers\StringHelper;
use flipbox\domains\Domains as DomainsPlugin;
use flipbox\domains\fields\Domains;
use flipbox\domains\models\Domain;
use flipbox\ember\db\traits\AuditAttributes;
use flipbox\ember\db\traits\PopulateModel;
use yii\base\ArrayableTrait;
use yii\base\Exception;
use yii\db\Connection;

class DomainsQuery extends Query
{
    use ArrayableTrait, PopulateModel, traits\Attributes, AuditAttributes;

    /**
     * @var bool Whether results should be returned in the order specified by [[domain]].
     */
    public $fixedOrder = false;

    /**
     * @inheritdoc
     */
    public $orderBy = 'sortOrder';

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
                $fieldService->getTableName($this->field) . ' ' . $fieldService->getTableAlias($this->field)
            ]);
        }
    }

    /**
     * @return Domains
     */
    public function getField(): Domains
    {
        return $this->field;
    }

    /**
     * @inheritdoc
     */
    protected function getIndexBy()
    {
        return $this->indexBy;
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
        $this->applyConditions();
        $this->applyAuditAttributeConditions();
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
    private function applyOrderByParams(Connection $db)
    {
        if ($this->orderBy === null) {
            return;
        }

        // Any other empty value means we should set it
        if (empty($this->orderBy)) {
            $this->applyEmptyOrderByParams($db);
        }

        $this->orderBy($this->orderBy);
    }

    /**
     * @param Connection $db
     * @throws Exception
     * @throws QueryAbortedException
     */
    private function applyEmptyOrderByParams(Connection $db)
    {
        if ($this->fixedOrder) {
            $domains = $this->domain;
            if (!is_array($domains)) {
                $domains = is_string($domains) ? StringHelper::split($domains) : [$domains];
            }

            if (empty($domains)) {
                throw new QueryAbortedException;
            }

            // Order the elements in the exact order that the Search service returned them in
            if (!$db instanceof CraftConnection) {
                throw new Exception('The database connection doesn\'t support fixed ordering.');
            }

            $this->orderBy = [new FixedOrderExpression('domain', $domains, $db)];
        } else {
            $this->orderBy = ['dateCreated' => SORT_DESC];
        }
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
     * @param int $n The offset of the row to return. If [[offset]] is set, $offset will be added to it.
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

    /**
     * @param $row
     *
     * @return Domain
     */
    protected function createModel($row): Domain
    {
        return new Domain($this->getField(), $row);
    }
}
