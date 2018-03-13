<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\services;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use flipbox\craft\sourceTarget\db\FieldAssociationQueryInterface;
use flipbox\craft\sourceTarget\fields\FieldAssociationInterface;
use flipbox\craft\sourceTarget\models\AssociationModelInterface;
use flipbox\craft\sourceTarget\models\AssociationModelWithFieldInterface;
use flipbox\craft\sourceTarget\services\FieldAssociations;
use flipbox\domains\db\DomainsQuery;
use flipbox\domains\models\Domain;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Associations extends FieldAssociations
{
    /**
     * @inheritdoc
     * @return FieldAssociationQueryInterface|DomainsQuery
     */
    public function getQuery(FieldAssociationInterface $field, $criteria = []): FieldAssociationQueryInterface
    {
        return new DomainsQuery($field, $criteria);
    }

    /**
     * @param FieldAssociationQueryInterface $query
     * @param ElementInterface|Element $source
     * @return bool
     * @throws \Exception
     */
    public function save(
        FieldAssociationQueryInterface $query,
        ElementInterface $source
    ): bool {
        if (null === ($targets = $this->getTargetsFromQuery($query))) {
            return true;
        }

        $newOrder = [];
        $currentTargets = $this->getQuery($query->getField())
            ->siteId($source->siteId)
            ->elementId($source->getId())
            ->indexBy('domain')
            ->all();

        if (false === $this->saveInternal(
                $targets,
                $currentTargets,
                $newOrder
            )) {
            return false;
        }

        $model = new Domain($query->getField(), [
            'elementId' => $source->getId(),
            'siteId' => $source->siteId
        ]);

        return $this->reOrderIfDifferent(
            $model,
            $newOrder
        );
    }

    /**
     * @param AssociationModelWithFieldInterface|Domain $model
     * @return FieldAssociationQueryInterface
     */
    protected function associationQuery(
        AssociationModelWithFieldInterface $model
    ): FieldAssociationQueryInterface {
        return $this->getQuery($model->getField())
            ->where([
                $model->getSourceName() => $model->getSourceValue(),
                'siteId' => $model->getSiteId()
            ])
            ->orderBy(['sortOrder' => SORT_ASC]);
    }


    /**
     * @param AssociationModelInterface|Domain $model
     * @return bool
     * @throws \yii\db\Exception
     */
    protected function insertInternal(AssociationModelInterface $model): bool
    {
        return (bool)Craft::$app->getDb()->createCommand()->insert(
            $model->getTableName(),
            [
                'domain' => $model->getTargetValue(),
                'elementId' => $model->getSourceValue(),
                'status' => $model->status,
                'sortOrder' => $model->getSortOrder(),
                'siteId' => $model->getSiteId()
            ]
        )->execute();
    }

    /**
     * @param AssociationModelInterface|Domain $model
     * @return bool
     * @throws \yii\db\Exception
     */
    protected function updateInternal(AssociationModelInterface $model): bool
    {
        return (bool)Craft::$app->getDb()->createCommand()->update(
            $model->getTableName(),
            [
                'status' => $model->status
            ],
            [
                'domain' => $model->getTargetValue(),
                'elementId' => $model->getSourceValue(),
                'siteId' => $model->getSiteId()
            ]
        )->execute();
    }


    /**
     * @param AssociationModelInterface|Domain $old
     * @param AssociationModelInterface|Domain $new
     * @return bool
     */
    protected function hasChanged(AssociationModelInterface $old, AssociationModelInterface $new): bool
    {
        return $old->status != $new->status;
    }
}
