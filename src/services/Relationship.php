<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\services;

use Craft;
use craft\base\ElementInterface;
use craft\db\Query;
use craft\events\ModelEvent;
use flipbox\domains\db\DomainsQuery;
use flipbox\domains\Domains as DomainsPlugin;
use flipbox\domains\fields\Domains;
use flipbox\domains\models\Domain;
use flipbox\spark\helpers\RecordHelper;
use flipbox\spark\services\traits\ModelDelete;
use flipbox\spark\services\traits\ModelSave;
use yii\base\Component;
use yii\base\Exception;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Relationship extends Component
{
    /**
     * @param Domains $field
     * @param string $domain
     * @param int $elementId
     * @param int|null $siteId
     * @return bool
     */
    public function exists(
        Domains $field,
        string $domain,
        int $elementId,
        int $siteId = null
    ): bool {
        return (new DomainsQuery($field))
            ->elementId($elementId)
            ->domain($domain)
            ->siteId($siteId)
            ->count() > 0;
    }

    /**
     * @param Domains $field
     * @param string $domain
     * @param int $elementId
     * @param int|null $siteId
     * @return Domain|null
     */
    public function find(
        Domains $field,
        string $domain,
        int $elementId,
        int $siteId = null
    ) {
        return (new DomainsQuery($field))
            ->elementId($elementId)
            ->domain($domain)
            ->siteId($siteId)
            ->one();
    }

    /**
     * @param Domains $field
     * @param string $domain
     * @param int $elementId
     * @param int|null $siteId
     * @return Domain
     * @throws Exception
     */
    public function get(
        Domains $field,
        string $domain,
        int $elementId,
        int $siteId = null
    ) {

        if (!$model = $this->find($field, $domain, $elementId, $siteId)) {
            throw new Exception(
                Craft::t(
                    'domains',
                    "Unable to find domain '{domain}'",
                    [
                        'domain' => $domain
                    ]
                )
            );
        }

        return $model;
    }

    /**
     * @param Domain $model
     * @param bool $runValidation
     * @param null $attributes
     * @return bool
     * @throws \Exception
     */
    public function associate(Domain $model, bool $runValidation = true, $attributes = null)
    {
        $existingDomain = $this->find(
            $model->getField(),
            $model->domain,
            $model->getElementId(),
            $model->siteId
        );

        // Nothing to update
        if ($existingDomain &&
                $existingDomain->status === $model->status &&
                $existingDomain->sortOrder === $model->sortOrder
        ) {
            return true;
        }

        // Validate
        if ($runValidation && !$model->validate($attributes)) {
            Craft::info('Domain not saved due to validation error.', __METHOD__);
            return false;
        }

        $isNew = (bool)$existingDomain;

        // Create event
        $event = new ModelEvent([
            'isNew' => $isNew
        ]);

        // Db transaction
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // The 'before' event
            if (!$model->beforeSave($event)) {
                $transaction->rollBack();

                return false;
            }

            // Look for an existing domain (that we'll update)
            if ($isNew) {
                $success = $this->updateDomain($model);
            } else {
                $success = $this->insertDomain($model);
            }

            // Insert record
            if (!$success) {
                // Transfer errors to model
                $model->addError(
                    Craft::t('domains', 'Unable to save domain.')
                );

                $transaction->rollBack();

                return false;
            }

            // The 'after' event
            if (!$model->afterSave($event)) {
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

    /**
     * @param Domain $model
     * @return bool
     * @throws \Exception
     */
    public function dissociate(Domain $model)
    {
        // The event to trigger
        $event = new ModelEvent();

        // Db transaction
        $transaction = Craft::$app->getDb()->beginTransaction();

        try {
            // The 'before' event
            if (!$model->beforeDelete($event)) {
                $transaction->rollBack();

                return false;
            }

            // Delete command
            Craft::$app->getDb()->createCommand()
                ->delete(
                    DomainsPlugin::getInstance()->getField()->getTableName($model->getField()),
                    [
                        'domain' => $model->domain,
                        'elementId' => $model->getElementId(),
                        'siteId' => $model->siteId
                    ]
                )->execute();

            // The 'after' event
            if (!$model->afterDelete($event)) {
                // Roll back db transaction
                $transaction->rollBack();

                return false;
            }
        } catch (\Exception $e) {
            // Roll back all db actions (fail)
            $transaction->rollback();

            throw $e;
        }

        $transaction->commit();

        return true;
    }

    /**
     * @param Domain $model
     * @return bool
     */
    private function updateDomain(Domain $model): bool
    {
        return (bool) Craft::$app->getDb()->createCommand()
            ->update(
                DomainsPlugin::getInstance()->getField()->getTableName($model->getField()),
                $this->upsertColumns($model),
                [
                    'elementId' => $model->getElementId(),
                    'domain' => $model->domain,
                    'siteId' => $model->siteId
                ]
            )
            ->execute();
    }

    /**
     * @param Domain $domain
     * @return bool
     */
    private function insertDomain(Domain $domain): bool
    {
        return (bool) Craft::$app->getDb()->createCommand()
            ->insert(
                DomainsPlugin::getInstance()->getField()->getTableName($domain->getField()),
                $this->upsertColumns($domain)
            )
            ->execute();
    }

    /**
     * @param Domain $model
     * @return array
     */
    private function upsertColumns(Domain $model): array
    {
        return [
            'domain' => $model->domain,
            'elementId' => $model->getElementId(),
            'siteId' => $this->resolveSiteId($model->siteId),
            'sortOrder' => $model->sortOrder,
            'status' => $model->status
        ];
    }

    /**
     * @param null $siteId
     * @return int
     */
    private function resolveSiteId($siteId = null): int
    {
        if ($siteId) {
            return $siteId;
        }

        return Craft::$app->getSites()->currentSite->id;
    }
}
