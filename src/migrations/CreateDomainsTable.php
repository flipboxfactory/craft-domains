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
            'elementId' => $this->integer()->notNull(),
            'domain' => $this->string()->notNull(),
            'status' => $this->enum('status', ['enabled', 'pending', 'disabled'])->notNull()->defaultValue('enabled'),
            'sortOrder' => $this->smallInteger()->unsigned(),
            'siteId' => $this->integer()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        $this->addPrimaryKey(
            null,
            $this->tableName,
            [
                'elementId',
                'domain',
                'siteId'
            ]
        );
        $this->createIndex(
            null,
            $this->tableName,
            'domain',
            false
        );
        $this->createIndex(
            null,
            $this->tableName,
            'status',
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
