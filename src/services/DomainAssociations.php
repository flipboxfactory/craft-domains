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
use craft\db\Query;
use flipbox\domains\db\DomainsQuery;
use flipbox\domains\Domains as DomainsPlugin;
use flipbox\domains\events\DomainAssociationEvent;
use flipbox\domains\fields\Domains as DomainsField;
use flipbox\domains\models\Domain;
use flipbox\ember\helpers\ArrayHelper;
use yii\base\Component;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class DomainAssociations extends Component
{
    /**
     * @event DomainAssociationEvent The event that is triggered before a domain association.
     *
     * You may set [[DomainAssociationEvent::isValid]] to `false` to prevent the associate action.
     */
    const EVENT_BEFORE_ASSOCIATE = 'beforeAssociate';

    /**
     * @event DomainAssociationEvent The event that is triggered after a domain association.
     */
    const EVENT_AFTER_ASSOCIATE = 'afterAssociate';

    /**
     * @event DomainAssociationEvent The event that is triggered before a domain dissociation.
     *
     * You may set [[DomainAssociationEvent::isValid]] to `false` to prevent the dissociate action.
     */
    const EVENT_BEFORE_DISSOCIATE = 'beforeDissociate';

    /**
     * @event DomainAssociationEvent The event that is triggered after a domain dissociation.
     */
    const EVENT_AFTER_DISSOCIATE = 'afterDissociate';

    /**
     * @param DomainsField $field
     * @param DomainsQuery $query
     * @param ElementInterface $element
     * @return bool
     * @throws \Exception
     */
    public function save(
        DomainsField $field,
        DomainsQuery $query,
        ElementInterface $element
    ) {
        /** @var Element $element */
        if (null === ($models = $query->getCachedResult())) {
            return true;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $currentModels = $this->getCurrentDomainAssociations($field, $element);

            $newOrder = [];
            if (!$this->associateAll($element, $models, $currentModels, $newOrder)) {
                $transaction->rollBack();
                return false;
            }

            if (!$this->dissociateAll($element, $currentModels)) {
                $transaction->rollBack();
                return false;
            }

            if (!$this->reOrderIfChanged(
                DomainsPlugin::getInstance()->getField()->getTableName($field),
                $newOrder,
                $element
            )) {
                $transaction->rollBack();
                return false;
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        $transaction->commit();
        return true;
    }

    /*******************************************
     * ASSOCIATE
     *******************************************/

    /**
     * @param Domain $model
     * @return bool
     * @throws \Exception
     */
    public function associate(Domain $model): bool
    {

        if ($model->sortOrder === null) {
            $model->sortOrder = $this->nextSortOrder($model);
        }

        if ($this->associationExists($model)) {
            return $this->applySortOrder($model);
        }

        $event = new DomainAssociationEvent([
            'domain' => $model
        ]);

        $this->trigger(
            static::EVENT_BEFORE_ASSOCIATE,
            $event
        );

        // Green light?
        if (!$event->isValid) {
            DomainsPlugin::info(
                "Event aborted association.",
                __METHOD__
            );

            return false;
        }

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            if (!$this->insertRecord(
                DomainsPlugin::getInstance()->getField()->getTableName($model->getField()),
                $model->getElementId(),
                $model->domain,
                $model->status,
                $model->sortOrder,
                $model->getSiteId()
            )) {
                $transaction->rollBack();
                return false;
            }

            $this->trigger(
                static::EVENT_AFTER_ASSOCIATE,
                $event
            );
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        $transaction->commit();
        return true;
    }

    /*******************************************
     * DISSOCIATE
     *******************************************/

    /**
     * @param Domain $model
     * @param bool $autoReorder
     * @return bool
     * @throws \Exception
     */
    public function dissociate(
        Domain $model,
        bool $autoReorder = true
    ) {
        if (!$this->associationExists($model)) {
            return true;
        }

        $event = new DomainAssociationEvent([
            'domain' => $model
        ]);

        // Trigger event
        $this->trigger(
            static::EVENT_BEFORE_DISSOCIATE,
            $event
        );

        // Green light?
        if (!$event->isValid) {
            DomainsPlugin::info(
                "Event aborted dissociation.",
                __METHOD__
            );
            return false;
        }

        $tableName = DomainsPlugin::getInstance()->getField()->getTableName($model->getField());

        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            $this->deleteRecord(
                $tableName,
                $model->getElementId(),
                $model->domain,
                $model->getSiteId()
            );

            $this->trigger(
                static::EVENT_AFTER_DISSOCIATE,
                $event
            );

            // Reorder?
            if ($autoReorder === true &&
                !$this->autoReorder($tableName, $model->getElementId(), $model->getSiteId())
            ) {
                $transaction->rollBack();
                return false;
            }
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        $transaction->commit();
        return true;
    }

    /*******************************************
     * ORDER
     *******************************************/

    /**
     * @param string $tableName
     * @param int $elementId
     * @param int $siteId
     * @param array $sortOrder
     * @return bool
     * @throws \yii\db\Exception
     */
    public function updateOrder(
        string $tableName,
        int $elementId,
        int $siteId,
        array $sortOrder
    ): bool {
        $db = Craft::$app->getDb();

        foreach ($sortOrder as $domain => $order) {
            $db->createCommand()
                ->update(
                    $tableName,
                    ['sortOrder' => $order],
                    [
                        'domain' => $domain,
                        'elementId' => $elementId,
                        'siteId' => $siteId
                    ]
                )
                ->execute();
        }

        return true;
    }

    /*******************************************
     * INSERT / DELETE
     *******************************************/

    /**
     * @param string $tableName
     * @param int $elementId
     * @param string $domain
     * @param string $status
     * @param int $sortOrder
     * @param int $siteId
     * @return bool
     * @throws \yii\db\Exception
     */
    private function insertRecord(
        string $tableName,
        int $elementId,
        string $domain,
        string $status,
        int $sortOrder,
        int $siteId
    ): bool {
        if (!$successful = (bool)Craft::$app->getDb()->createCommand()->insert(
            $tableName,
            [
                'elementId' => $elementId,
                'domain' => $domain,
                'status' => $status,
                'sortOrder' => $sortOrder,
                'siteId' => $siteId
            ]
        )->execute()) {
            DomainsPlugin::trace(
                sprintf(
                    "Failed to associate domain '%s' to element '%s' for site '%s'.",
                    (string)$domain,
                    (string)$elementId,
                    (string)$siteId
                ),
                __METHOD__
            );
            return false;
        }

        DomainsPlugin::trace(
            sprintf(
                "Successfully associated domain '%s' to element '%s' for site '%s'.",
                (string)$domain,
                (string)$elementId,
                (string)$siteId
            ),
            __METHOD__
        );

        return true;
    }

    /**
     * @param string $tableName
     * @param int $elementId
     * @param string $domain
     * @param int $siteId
     * @return bool
     * @throws \yii\db\Exception
     */
    private function deleteRecord(
        string $tableName,
        int $elementId,
        string $domain,
        int $siteId
    ): bool {
        if (!$successful = (bool)Craft::$app->getDb()->createCommand()->delete(
            $tableName,
            [
                'elementId' => $elementId,
                'domain' => $domain,
                'siteId' => $siteId
            ]
        )->execute()) {
            DomainsPlugin::trace(
                sprintf(
                    "Failed to dissociate domain '%s' with element '%s' for site '%s'.",
                    (string)$domain,
                    (string)$elementId,
                    (string)$siteId
                ),
                __METHOD__
            );
            return false;
        }

        DomainsPlugin::trace(
            sprintf(
                "Successfully dissociated domain '%s' with element '%s' for site '%s'",
                (string)$domain,
                (string)$elementId,
                (string)$siteId
            ),
            __METHOD__
        );

        return true;
    }


    /*******************************************
     * ASSOCIATE / DISSOCIATE MANY
     *******************************************/

    /**
     * @param ElementInterface $element
     * @param array $models
     * @param array $currentModels
     * @param array $newOrder
     * @return bool
     * @throws \Exception
     */
    private function associateAll(
        ElementInterface $element,
        array $models,
        array &$currentModels,
        array &$newOrder
    ): bool {
        /** @var Element $element */
        $ct = 1;
        foreach ($models as $model) {
            $model->setElement($element);
            $model->setSiteId($element->siteId);
            $model->sortOrder = $ct;

            $newOrder[$model->domain] = $ct++;

            if (null !== ArrayHelper::remove($currentModels, $model->domain)) {
                continue;
            }

            if (!$this->associate($model)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param ElementInterface $element
     * @param array $models
     * @return bool
     * @throws \Exception
     */
    private function dissociateAll(
        ElementInterface $element,
        array $models
    ): bool {
        /** @var Element $element */
        foreach ($models as $model) {
            $model->setElement($element);
            $model->setSiteId($element->siteId);

            if (!$this->dissociate($model)) {
                return false;
            }
        }

        return true;
    }

    /*******************************************
     * SORT ORDER
     *******************************************/

    /**
     * @param Domain $model
     * @return bool
     * @throws \Exception
     * @throws \yii\db\Exception
     */
    private function applySortOrder(
        Domain $model
    ): bool {
        $tableName = DomainsPlugin::getInstance()->getField()->getTableName($model->getField());

        $currentSortOrder = $this->currentSortOrder($tableName, $model->getElementId(), $model->getSiteId());

        if (count($currentSortOrder) < $model->sortOrder) {
            $model->sortOrder = count($currentSortOrder);
        }

        $order = ArrayHelper::insertSequential($currentSortOrder, $model->domain, $model->sortOrder);

        if ($order === false) {
            return $this->associate($model);
        }

        if ($order === true) {
            return true;
        }

        return $this->updateOrder(
            $tableName,
            $model->getElementId(),
            $model->getSiteId(),
            (array)$order
        );
    }

    /**
     * @param Domain $model
     * @return int
     */
    private function nextSortOrder(
        Domain $model
    ): int {
        $maxSortOrder = $this->baseAssociationQuery(
            DomainsPlugin::getInstance()->getField()->getTableName($model->getField()),
            $model->getElementId(),
            $model->getSiteId()
        )->max('[[sortOrder]]');

        $maxSortOrder++;

        return $maxSortOrder;
    }

    /**
     * Re-orders so they are in sequential order
     *
     * @param string $tableName
     * @param int $elementId
     * @param int $siteId
     * @return bool
     * @throws \yii\db\Exception
     */
    protected function autoReorder(string $tableName, int $elementId, int $siteId): bool
    {
        $currentSortOrder = $this->currentSortOrder($tableName, $elementId, $siteId);

        if (empty($currentSortOrder)) {
            return true;
        }

        return $this->updateOrder(
            $tableName,
            $elementId,
            $siteId,
            array_combine(
                range(1, count($currentSortOrder)),
                array_keys($currentSortOrder)
            )
        );
    }

    /**
     * @param DomainsField $field
     * @param ElementInterface $element
     * @return array
     */
    protected function getCurrentDomainAssociations(DomainsField $field, ElementInterface $element): array
    {
        /** @var Element $element */
        return (array)(new DomainsQuery($field))
            ->siteId($element->siteId)
            ->elementId($element->getId())
            ->indexBy('domain')
            ->all();
    }

    /**
     * @param string $tableName
     * @param int $elementId
     * @param int $siteId
     * @return array
     */
    private function currentSortOrder(string $tableName, int $elementId, int $siteId): array
    {
        return $this->baseAssociationQuery($tableName, $elementId, $siteId)
            ->indexBy('domain')
            ->select(['sortOrder'])
            ->column();
    }

    /**
     * @param string $tableName
     * @param int $elementId
     * @param int $siteId
     * @return Query
     */
    private function baseAssociationQuery(string $tableName, int $elementId, int $siteId): Query
    {
        return (new Query())
            ->from($tableName)
            ->where([
                'elementId' => $elementId,
                'siteId' => $siteId
            ])
            ->orderBy(['sortOrder' => SORT_ASC]);
    }

    /*******************************************
     * UTILITIES
     *******************************************/

    /**
     * @param Domain $model
     * @return bool
     */
    private function associationExists(
        Domain $model
    ): bool {
        $tableName = DomainsPlugin::getInstance()->getField()->getTableName($model->getField());

        $condition = [
            'domain' => $model->domain,
        ];

        if ($model->sortOrder !== null) {
            $condition['sortOrder'] = $model->sortOrder;
        }

        return $this->baseAssociationQuery($tableName, $model->getElementId(), $model->getSiteId())
            ->andWhere($condition)
            ->exists();
    }

    /**
     * @param string $tableName
     * @param array $newOrder
     * @param ElementInterface $element
     * @return bool
     * @throws \yii\db\Exception
     */
    private function reOrderIfChanged(string $tableName, array $newOrder, ElementInterface $element)
    {
        /** @var Element $element */
        $currentOrder = $this->currentSortOrder($tableName, $element->getId(), $element->siteId);

        if ((empty($currentOrder) && empty($newOrder)) || $currentOrder == $newOrder) {
            return true;
        }

        if (!$this->updateOrder($tableName, $element->getId(), $element->siteId, $newOrder)) {
            return false;
        }

        return true;
    }
}
