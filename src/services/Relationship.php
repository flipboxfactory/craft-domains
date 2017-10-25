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
use flipbox\domains\db\DomainsQuery;
use flipbox\domains\Domains as DomainsPlugin;
use flipbox\domains\events\RelationshipEvent;
use flipbox\domains\fields\Domains;
use flipbox\domains\models\Domain;
use yii\base\Component;
use yii\base\Exception;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Relationship extends Component
{
    /**
     * Before Add Volunteer
     */
    const EVENT_BEFORE_ASSOCIATE = 'beforeAssociate';

    /**
     * After Add Volunteer
     */
    const EVENT_AFTER_ASSOCIATE = 'afterAssociate';

    /**
     * Before Add Participant
     */
    const EVENT_BEFORE_DISSOCIATE = 'beforeDissociate';

    /**
     * After Add Participant
     */
    const EVENT_AFTER_DISSOCIATE = 'afterDissociate';

    /**
     * @param Domains $field
     * @param Domain $domain
     * @return bool
     */
    public function save(
        Domains $field,
        Domain $domain
    ) {
        return $this->associate(
            $field,
            $domain->domain,
            $domain->getElementId(),
            $domain->siteId
        );
    }

    /**
     * @param Domains $field
     * @param Domain $domain
     * @return bool
     */
    public function delete(
        Domains $field,
        Domain $domain
    ) {
        return $this->dissociate(
            $field,
            $domain->domain,
            $domain->getElementId(),
            $domain->siteId
        );
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
            throw new Exception("Unable to find relationship");
        }

        return $model;
    }
    /**
     * @param Domains $field
     * @param string $domain
     * @param int $elementId
     * @param int|null $siteId
     * @return bool
     */
    public function associate(
        Domains $field,
        string $domain,
        int $elementId,
        int $siteId = null
    ) {
        // The 'before' event
        $event = new RelationshipEvent([
            'field' => $field,
            'domain' => $domain,
            'elementId' => $elementId,
            'siteId' => $siteId
        ]);

        // Trigger event
        $this->trigger(static::EVENT_BEFORE_ASSOCIATE, $event);

        // Green light?
        if (!$event->isValid) {
            return false;
        }

        // Table name
        $table = DomainsPlugin::getInstance()->getField()->getTableName($field);

        $columns = [
            'domain' => $domain,
            'elementId' => $elementId,
            'siteId' => $this->resolveSiteId($siteId)
        ];

        $existingRelationshipId = (new Query())
            ->select('id')
            ->from($table)
            ->where(['and', $columns])
            ->column();

        if ($existingRelationshipId) {
            $rows = Craft::$app->getDb()->createCommand()
                ->update($table, $columns, ['id' => $existingRelationshipId])
                ->execute();
        } else {
            $rows = Craft::$app->getDb()->createCommand()
                ->insert($table, $columns)
                ->execute();
        }

        if (!(bool)$rows) {
            return false;
        }

        // Trigger event
        $this->trigger(static::EVENT_AFTER_ASSOCIATE, $event);

        return true;
    }

    /**
     * @param Domains $field
     * @param string $domain ,
     * @param int $elementId
     * @param int|null $siteId
     * @return bool
     */
    public function dissociate(
        Domains $field,
        string $domain,
        int $elementId,
        int $siteId = null
    ) {

        // The 'before' event
        $event = new RelationshipEvent([
            'field' => $field,
            'domain' => $domain,
            'elementId' => $elementId,
            'siteId' => $siteId
        ]);

        // Trigger event
        $this->trigger(static::EVENT_BEFORE_DISSOCIATE, $event);

        // Green light?
        if (!$event->isValid) {
            return false;
        }

        // Table name
        $table = DomainsPlugin::getInstance()->getField()->getTableName($field);

        Craft::$app->getDb()->createCommand()
            ->delete($table, [
                'domain' => $domain,
                'elementId' => $elementId,
                'siteId' => $this->resolveSiteId($siteId)
            ])->execute();

        // Trigger event
        $this->trigger(static::EVENT_AFTER_DISSOCIATE, $event);

        return true;
    }

    /**
     * @param null $siteId
     * @return int
     */
    protected function resolveSiteId($siteId = null): int
    {
        if ($siteId) {
            return $siteId;
        }

        return Craft::$app->getSites()->currentSite->id;
    }
}
