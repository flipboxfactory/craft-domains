<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\db;

use craft\db\QueryAbortedException;
use flipbox\craft\sortable\associations\db\SortableAssociationQuery;
use flipbox\craft\sortable\associations\db\traits\SiteAttribute;
use flipbox\domains\records\Domain;

/**
 * @method Domain[] getCachedResult()
 */
class DomainsQuery extends SortableAssociationQuery
{
    use traits\Attributes,
        SiteAttribute;

    /**
     * @inheritdoc
     */
    protected function fixedOrderColumn(): string
    {
        return 'domain';
    }

    /**
     * @inheritdoc
     *
     * @throws QueryAbortedException if it can be determined that there won’t be any results
     */
    public function prepare($builder)
    {
        // Is the query already doomed?
        if (($this->fieldId !== null && empty($this->fieldId)) ||
            ($this->domain !== null && empty($this->domain))
        ) {
            throw new QueryAbortedException();
        }

        $this->applySiteConditions();
        $this->applyConditions();

        return parent::prepare($builder);
    }
}
