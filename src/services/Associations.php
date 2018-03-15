<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\services;

use craft\base\Element;
use craft\base\ElementInterface;
use flipbox\craft\sourceTarget\db\SortableAssociationQueryInterface;
use flipbox\craft\sourceTarget\records\SortableAssociationInterface;
use flipbox\craft\sourceTarget\services\SortableAssociations;
use flipbox\domains\db\DomainsQuery;
use flipbox\domains\fields\Domains as DomainsField;
use flipbox\domains\records\Domain;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Associations extends SortableAssociations
{
    /**
     * @inheritdoc
     * @return DomainsQuery
     */
    public function getQuery($config = []): SortableAssociationQueryInterface
    {
        return new DomainsQuery(Domain::class, $config);
    }

    /**
     * @inheritdoc
     */
    protected static function tableName(): string
    {
        return Domain::tableName();
    }

    /**
     * @inheritdoc
     */
    public static function source(): string
    {
        return Domain::SOURCE_ATTRIBUTE;
    }

    /**
     * @inheritdoc
     */
    public static function target(): string
    {
        return Domain::TARGET_ATTRIBUTE;
    }

    /**
     * @param SortableAssociationInterface|Domain $record
     * @return SortableAssociationQueryInterface|DomainsQuery
     */
    protected function associationQuery(
        SortableAssociationInterface $record
    ): SortableAssociationQueryInterface {
        return $this->newAssociationQuery(
            $record->{static::source()},
            $record->fieldId,
            $record->siteId
        );
    }

    /**
     * @param $source
     * @param int $fieldId
     * @param int $siteId
     * @return SortableAssociationQueryInterface
     */
    private function newAssociationQuery(
        $source,
        int $fieldId,
        int $siteId
    ): SortableAssociationQueryInterface {
        return $this->getQuery()
            ->where([
                static::source() => $source,
                'fieldId' => $fieldId,
                'siteId' => $siteId
            ])
            ->orderBy(['sortOrder' => SORT_ASC]);
    }

    /**
     * @param DomainsField $field
     * @param DomainsQuery $query
     * @param ElementInterface|Element $source
     * @return bool
     * @throws \Exception
     */
    public function save(
        DomainsField $field,
        DomainsQuery $query,
        ElementInterface $source
    ): bool {
        if (null === ($targets = $query->getCachedResult())) {
            return true;
        }
        $currentTargets = $this->newAssociationQuery(
            $source->getId() ?: false,
            $field->id,
            $source->siteId
        )
            ->indexBy('domain')
            ->all();

        if (false === $this->saveInternal(
            $targets,
            $currentTargets
        )) {
            return false;
        }

        return true;
    }
}
