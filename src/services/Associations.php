<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\services;

use craft\helpers\Json;
use flipbox\craft\sortable\associations\db\SortableAssociationQueryInterface;
use flipbox\craft\sortable\associations\records\SortableAssociationInterface;
use flipbox\craft\sortable\associations\services\SortableAssociations;
use flipbox\domains\db\DomainsQuery;
use flipbox\domains\records\Domain;
use flipbox\ember\validators\MinMaxValidator;
use yii\db\ActiveQuery;
use flipbox\domains\Domains as DomainsPlugin;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Associations extends SortableAssociations
{
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
     */
    protected static function tableAlias(): string
    {
        return Domain::tableAlias();
    }

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

    /**
     * @param SortableAssociationQueryInterface $query
     * @return \flipbox\domains\fields\Domains|null
     */
    protected function resolveFieldFromQuery(
        SortableAssociationQueryInterface $query
    ) {
        if (null === ($fieldId = $this->resolveStringAttribute($query, 'fieldId'))) {
            return null;
        }

        return DomainsPlugin::getInstance()->getFields()->findById($fieldId);
    }

    /**
     * @inheritdoc
     * @param bool $validate
     * @throws \Exception
     */
    public function save(
        SortableAssociationQueryInterface $query,
        bool $validate = true
    ): bool {
        if ($validate === true && null !== ($field = $this->resolveFieldFromQuery($query))) {
            $error = '';

            (new MinMaxValidator([
                'min' => $field->min,
                'max' => $field->max
            ]))->validate($query, $error);

            if (!empty($error)) {
                DomainsPlugin::error(sprintf(
                    "Domains failed to save due to the following validation errors: '%s'",
                    Json::encode($error)
                ));
                return false;
            }
        }

        return parent::save($query);
    }
}
