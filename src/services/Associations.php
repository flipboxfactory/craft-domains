<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\services;

use flipbox\craft\sourceTarget\db\SortableAssociationQueryInterface;
use flipbox\craft\sourceTarget\records\SortableAssociationInterface;
use flipbox\craft\sourceTarget\services\SortableAssociations;
use flipbox\domains\db\DomainsQuery;
use flipbox\domains\records\Domain;
use yii\db\ActiveQuery;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Associations extends SortableAssociations
{
    /**
     * @inheritdoc
     */
    const TABLE_ALIAS = Domain::TABLE_ALIAS;

    /**
     * @inheritdoc
     */
    const SOURCE_ATTRIBUTE = Domain::SOURCE_ATTRIBUTE;

    /**
     * @inheritdoc
     */
    const TARGET_ATTRIBUTE = Domain::TARGET_ATTRIBUTE;

    /**
     * @inheritdoc
     * @return DomainsQuery
     */
    public function getQuery($config = []): SortableAssociationQueryInterface
    {
        return new DomainsQuery(Domain::class, $config);
    }

    /**
     * @param SortableAssociationInterface|Domain $record
     * @return SortableAssociationQueryInterface|DomainsQuery
     */
    protected function associationQuery(
        SortableAssociationInterface $record
    ): SortableAssociationQueryInterface {
        return $this->query(
            $record->{static::SOURCE_ATTRIBUTE},
            $record->fieldId,
            $record->siteId
        );
    }

    /**
     * @param SortableAssociationQueryInterface|DomainsQuery $query
     * @return array
     */
    protected function existingAssociations(
        SortableAssociationQueryInterface $query
    ): array {
        $source = $this->resolveStringAttribute($query, static::SOURCE_ATTRIBUTE);
        $field = $this->resolveStringAttribute($query, 'fieldId');
        $site = $this->resolveStringAttribute($query, 'siteId');

        if ($source === null || $field === null || $site === null) {
            return [];
        }

        return $this->associations($source, $field, $site);
    }

    /**
     * @param $source
     * @param int $fieldId
     * @param int $siteId
     * @return SortableAssociationQueryInterface|ActiveQuery
     */
    private function query(
        $source,
        int $fieldId,
        int $siteId
    ): SortableAssociationQueryInterface {
        return $this->getQuery()
            ->where([
                static::SOURCE_ATTRIBUTE => $source,
                'fieldId' => $fieldId,
                'siteId' => $siteId
            ])
            ->orderBy(['sortOrder' => SORT_ASC]);
    }

    /**
     * @param $source
     * @param int $fieldId
     * @param int $siteId
     * @return array
     */
    private function associations(
        $source,
        int $fieldId,
        int $siteId
    ): array {
        return $this->query($source, $fieldId, $siteId)
            ->indexBy(static::TARGET_ATTRIBUTE)
            ->all();
    }
}
