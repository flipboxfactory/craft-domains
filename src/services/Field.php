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
use flipbox\domains\db\DomainsQuery;
use flipbox\domains\fields\Domains as DomainsField;
use flipbox\domains\migrations\CreateDomainsTable;
use yii\base\Component;
use yii\base\Exception;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since  1.0.0
 */
class Field extends Component
{
    /**
     * @param DomainsField $field
     * @return bool
     * @throws \yii\db\Exception
     */
    public function delete(DomainsField $field): bool
    {
        Craft::$app->getDb()->createCommand()
            ->dropTable(
                $this->getTableName($field)
            )
            ->execute();

        return true;
    }

    /**
     * @param DomainsField $field
     * @return bool
     * @throws Exception
     * @throws \Exception
     * @throws \Throwable
     */
    public function save(DomainsField $field): bool
    {
        // Create the content table first since the block type fields will need it
        $oldTable = $this->getTableName($field, true);
        $newTable = $this->getTableName($field);

        if (null === $newTable) {
            throw new Exception('There was a problem getting the new table name.');
        }

        if (true === Craft::$app->getDb()->tableExists($newTable)) {
            throw new Exception('The table name is already in use.');
        }

        if (false === $this->handleTableName($newTable, $oldTable)) {
            throw new Exception('There was a problem renaming the table.');
        }

        return true;
    }

    /**
     * @param string $newName
     * @param string|null $oldName
     * @return bool
     * @throws \Exception
     * @throws \Throwable
     */
    protected function handleTableName(string $newName, string $oldName = null): bool
    {
        $transaction = Craft::$app->getDb()->beginTransaction();
        try {
            if (null === $oldName && false === $this->createTable($newName)) {
                $transaction->rollBack();
                return false;
            }

            MigrationHelper::renameTable($oldName, $newName);
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        $transaction->commit();
        return true;
    }

    /**
     * Returns the content table name for a given field.
     *
     * @param DomainsField $field The field.
     * @param bool $useOldHandle Whether the method should use the field’s old handle when determining the
     * table name (e.g. to get the existing table name, rather than the new one).
     *
     * @return string|null The table name, or `false` if $useOldHandle was set to `true` and there was no old handle.
     */
    public function getTableName(DomainsField $field, bool $useOldHandle = false)
    {
        return '{{%' . $this->getTableAlias($field, $useOldHandle) . '}}';
    }

    /**
     * Returns the content table alias for a given field.
     *
     * @param DomainsField $field The field.
     * @param bool $useOldHandle Whether the method should use the field’s old handle when determining the
     * table alias (e.g. to get the existing table alias, rather than the new one).
     *
     * @return string|null The table alias, or `false` if $useOldHandle was set to `true` and there was no old handle.
     */
    public function getTableAlias(DomainsField $field, bool $useOldHandle = false)
    {
        $name = '';

        if ($useOldHandle === true) {
            if ($field->oldHandle === null) {
                return null;
            }

            $handle = $field->oldHandle;
        } else {
            $handle = $field->handle;
        }

        return 'domains_' . StringHelper::toLowerCase($handle) . $name;
    }

    /**
     * @param string $tableName
     * @return bool
     * @throws \Throwable
     */
    private function createTable(string $tableName): bool
    {
        $migration = new CreateDomainsTable([
            'tableName' => $tableName
        ]);

        ob_start();
        $result = $migration->up();
        ob_end_clean();

        return false !== $result;
    }

    /**
     * @param DomainsField $field
     * @param DomainsQuery $query
     * @param bool $static
     * @return null|string
     * @throws Exception
     * @throws \Twig_Error_Loader
     */
    public function getTableHtml(DomainsField $field, DomainsQuery $query, bool $static)
    {
        $columns = [
            'domain' => [
                'heading' => 'Domain',
                'handle' => 'domain',
                'type' => 'singleline'
            ],
            'status' => [
                'heading' => 'Status',
                'handle' => 'status',
                'type' => 'select',
                'options' => $field->getStatuses()
            ]
        ];

        // Translate the column headings
        foreach ($columns as &$column) {
            $heading = (string)$column['heading'];
            if ($heading !== null) {
                $column['heading'] = Craft::t('site', $heading);
            }
        }
        unset($column);

        return Craft::$app->getView()->renderTemplate(
            'domains/_components/fieldtypes/Domains/input',
            [
                'id' => Craft::$app->getView()->formatInputId($field->handle),
                'name' => $field->handle,
                'cols' => $columns,
                'rows' => $query->all(),
                'static' => $static
            ]
        );
    }
}
