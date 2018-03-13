<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\db;

use craft\db\QueryAbortedException;
use flipbox\craft\sourceTarget\db\FieldAssociationsQuery;
use flipbox\craft\sourceTarget\models\AssociationModelInterface;
use flipbox\domains\models\Domain;

/**
 * @method Domain[] getCachedResult()
 */
class DomainsQuery extends FieldAssociationsQuery
{
    use traits\Attributes;

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
        if (($this->elementId !== null && empty($this->elementId)) ||
            ($this->domain !== null && empty($this->domain))
        ) {
            throw new QueryAbortedException();
        }

        $this->applyConditions();

        return parent::prepare($builder);
    }

    /**
     * @inheritdoc
     * @return Domain
     */
    protected function createObject($row): AssociationModelInterface
    {
        return new Domain($this->field, $row);
    }
}
