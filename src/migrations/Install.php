<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\craft\domains\migrations;

use craft\db\Migration;
use craft\records\Element;
use craft\records\Field;
use craft\records\Site;
use flipbox\craft\domains\records\Domain;

class Install extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->createTable(Domain::tableName(), [
            'fieldId' => $this->integer()->notNull(),
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
            Domain::tableName(),
            [
                'fieldId',
                'elementId',
                'domain',
                'siteId'
            ]
        );

        $this->createIndex(
            null,
            Domain::tableName(),
            'domain',
            false
        );

        $this->createIndex(
            null,
            Domain::tableName(),
            'status',
            false
        );

        $this->addForeignKey(
            null,
            Domain::tableName(),
            'fieldId',
            Field::tableName(),
            'id',
            'CASCADE',
            null
        );

        $this->addForeignKey(
            null,
            Domain::tableName(),
            'elementId',
            Element::tableName(),
            'id',
            'CASCADE',
            null
        );

        $this->addForeignKey(
            null,
            Domain::tableName(),
            'siteId',
            Site::tableName(),
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
        $this->dropTableIfExists(Domain::tableName());
    }
}
