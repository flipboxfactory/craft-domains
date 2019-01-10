<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\craft\domains\migrations;

use craft\db\Migration;
use craft\records\Field;
use flipbox\craft\domains\fields\Domains;

class m190109_121422_namespace extends Migration
{
    /**
     * The old class name
     */
    const OLD_CLASS = "flipbox\\domains\\fields\\Domains";

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $query = Field::find();
        $query->andWhere(['type' => self::OLD_CLASS]);

        $success = true;

        /** @var Field $record */
        foreach ($query->all() as $record) {
            $record->type = Domains::class;

            if (!$record->save(true, ['type'])) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $query = Field::find();
        $query->andWhere(['type' => Domains::class]);

        $success = true;

        /** @var Field $record */
        foreach ($query->all() as $record) {
            $record->type = static::OLD_CLASS;

            if (!$record->save(true, ['type'])) {
                $success = false;
            }
        }

        return $success;
    }
}
