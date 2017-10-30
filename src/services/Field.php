<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\services;

use Craft;
use craft\helpers\MigrationHelper;
use craft\helpers\StringHelper;
use flipbox\domains\fields\Domains;
use flipbox\domains\migrations\CreateDomainsTable;
use yii\base\Component;
use yii\base\Exception;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Field extends Component
{

    /**
     * @param Domains $field
     * @return bool
     */
    public function delete(Domains $field): bool
    {
        Craft::$app->getDb()->createCommand()
            ->dropTable(
                $this->getTableName($field)
            )
            ->execute();

        return true;
    }

    /**
     * Saves a field's settings.
     *
     * @param Domains $field
     *
     * @return bool Whether the settings saved successfully.
     * @throws \Exception if reasons
     */
    public function saveSettings(Domains $field): bool
    {
        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            // Create the content table first since the block type fields will need it
            $oldTable = $this->getTableName($field, true);
            $newTable = $this->getTableName($field);

            if ($newTable === false) {
                throw new Exception('There was a problem getting the new table name.');
            }

            // Do we need to create/rename the content table?
            if (!Craft::$app->getDb()->tableExists($newTable)) {
                if ($oldTable !== false && Craft::$app->getDb()->tableExists($oldTable)) {
                    MigrationHelper::renameTable($oldTable, $newTable);
                } else {
                    if(!$this->createTable($newTable)) {
                        $transaction->rollBack();
                        return false;
                    }
                }
            }

            $transaction->commit();

            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();

            throw $e;
        }
    }

    /**
     * Returns the content table name for a given field.
     *
     * @param Domains $field The field.
     * @param bool $useOldHandle Whether the method should use the field’s old handle when determining the table
     *                                  name (e.g. to get the existing table name, rather than the new one).
     *
     * @return string|false The table name, or `false` if $useOldHandle was set to `true` and there was no old handle.
     */
    public function getTableName(Domains $field, bool $useOldHandle = false)
    {
        return '{{%' . $this->getTableAlias($field, $useOldHandle) . '}}';
    }

    /**
     * Returns the content table alias for a given field.
     *
     * @param Domains $field The field.
     * @param bool $useOldHandle Whether the method should use the field’s old handle when determining the table
     *                                  alias (e.g. to get the existing table alias, rather than the new one).
     *
     * @return string|false The table alias, or `false` if $useOldHandle was set to `true` and there was no old handle.
     */
    public function getTableAlias(Domains $field, bool $useOldHandle = false)
    {
        $name = '';

        if ($useOldHandle) {
            if (!$field->oldHandle) {
                return false;
            }

            $handle = $field->oldHandle;
        } else {
            $handle = $field->handle;
        }

        return 'domains_' . StringHelper::toLowerCase($handle) . $name;
    }

    /**
     * Creates the domains table for a field.
     *
     * @param string $tableName
     *
     * @return false|null
     */
    private function createTable(string $tableName)
    {
        $migration = new CreateDomainsTable([
            'tableName' => $tableName
        ]);

        ob_start();
        $result = $migration->up();
        ob_end_clean();

        return $result;
    }
}
