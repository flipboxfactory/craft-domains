<?php

namespace flipbox\domains\migrations;

use craft\db\Migration;

class CreateDomainsTable extends Migration
{
    // Properties
    // =========================================================================

    /**
     * @var string|null The table name
     */
    public $tableName;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable($this->tableName, [
            'id' => $this->primaryKey(),
            'elementId' => $this->integer()->notNull(),
            'domain' => $this->string()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->createIndex(
            null,
            $this->tableName,
            'domain',
            false
        );
        $this->addForeignKey(
            null,
            $this->tableName,
            'elementId',
            '{{%elements}}',
            'id',
            'CASCADE',
            null
        );
        $this->addForeignKey(
            null,
            $this->tableName,
            'siteId',
            '{{%sites}}',
            'id',
            'CASCADE',
            'CASCADE'
        );
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        return false;
    }
}
