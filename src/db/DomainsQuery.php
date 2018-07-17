<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\db;

use craft\db\QueryAbortedException;
use craft\helpers\Db;
use flipbox\craft\sortable\associations\db\SortableAssociationQuery;
use flipbox\craft\sortable\associations\db\traits\SiteAttribute;
use flipbox\domains\records\Domain;
use flipbox\ember\db\traits\ElementAttribute;
use flipbox\ember\helpers\QueryHelper;

/**
 * @method Domain[] getCachedResult()
 */
class DomainsQuery extends SortableAssociationQuery
{
    use traits\Attributes,
        traits\FieldAttribute,
        ElementAttribute,
        SiteAttribute;

    /**
     * @inheritdoc
     */
    protected function fixedOrderColumn(): string
    {
        return 'domain';
    }

    /**
     * @param array $config
     * @return $this
     */
    public function configure(array $config)
    {
        QueryHelper::configure(
            $this,
            $config
        );

        return $this;
    }

    /**
     * @inheritdoc
     *
     * @throws QueryAbortedException if it can be determined that there won’t be any results
     */
    public function prepare($builder)
    {
        // Is the query already doomed?
        if (($this->field !== null && empty($this->field)) ||
            ($this->domain !== null && empty($this->domain)) ||
            ($this->element !== null && empty($this->element))
        ) {
            throw new QueryAbortedException();
        }

        $this->applyConditions();
        $this->applySiteConditions();
        $this->applyFieldConditions();

        if ($this->element !== null) {
            $this->andWhere(Db::parseParam('elementId', $this->parseElementValue($this->element)));
        }

        return parent::prepare($builder);
    }
}
