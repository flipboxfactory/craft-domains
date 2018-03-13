<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\services;

use Craft;
use craft\db\Migration;
use flipbox\craft\sourceTarget\services\Field as BaseField;
use flipbox\domains\db\DomainsQuery;
use flipbox\domains\fields\Domains as DomainsField;
use flipbox\domains\migrations\CreateDomainsTable;
use yii\base\Exception;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since  1.0.0
 */
class Field extends BaseField
{
    /**
     *  The table prefix
     */
    const TABLE_PREFIX = 'domains_';

    /**
     * @inheritdoc
     */
    protected function createRelationsMigrationTable(string $tableName): Migration
    {
        return new CreateDomainsTable([
            'tableName' => $tableName
        ]);
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
                'class' => 'thin',
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
