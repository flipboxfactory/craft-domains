<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\db;

use craft\db\QueryAbortedException;
use flipbox\domains\Domains as DomainsPlugin;
use flipbox\domains\fields\Domains;
use flipbox\domains\models\Domain;
use flipbox\ember\db\CacheableQuery;
use flipbox\ember\db\traits\AuditAttributes;
use flipbox\ember\db\traits\FixedOrderBy;
use flipbox\ember\db\traits\PopulateObject;
use yii\base\ArrayableTrait;

class DomainsQuery extends CacheableQuery
{
    use ArrayableTrait, PopulateObject, traits\Attributes, AuditAttributes, FixedOrderBy;

    /**
     * @var bool Whether results should be returned in the order specified by [[domain]].
     */
    public $fixedOrder = false;

    /**
     * @inheritdoc
     */
    public $orderBy = 'sortOrder';

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
    protected function fixedOrderColumn(): string
    {
        return 'domain';
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
     * @inheritdoc
     */
    public function one($db = null)
    {
        $row = parent::one($db);

        if ($row === false) {
            return false;
        }

        return $this->createObject($row);
    }

    /**
     * @param $row
     *
     * @return Domain
     */
    protected function createObject($row): Domain
    {
        return new Domain($this->getField(), $row);
    }
}
