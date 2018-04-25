<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\services;

use Craft;
use craft\base\ElementInterface;
use craft\base\FieldInterface;
use craft\helpers\ArrayHelper;
use flipbox\craft\sortable\associations\db\SortableAssociationQueryInterface;
use flipbox\craft\sortable\associations\records\SortableAssociationInterface;
use flipbox\craft\sortable\associations\services\SortableFields;
use flipbox\domains\db\DomainsQuery;
use flipbox\domains\Domains as DomainsPlugin;
use flipbox\domains\fields\Domains as DomainsField;
use flipbox\domains\records\Domain;
use yii\base\Exception;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since  1.0.0
 */
class Fields extends SortableFields
{
    /**
     * @inheritdoc
     */
    const SOURCE_ATTRIBUTE = Domain::SOURCE_ATTRIBUTE;

    /**
     * @inheritdoc
     */
    const TARGET_ATTRIBUTE = Domain::TARGET_ATTRIBUTE;

    /**
     * @inheritdoc
     */
    protected static function tableAlias(): string
    {
        return Domain::tableAlias();
    }

    /**
     * @param FieldInterface $field
     * @throws Exception
     */
    private function ensureField(FieldInterface $field)
    {
        if (!$field instanceof DomainsField) {
            throw new Exception(sprintf(
                "The field must be an instance of '%s', '%s' given.",
                (string)DomainsField::class,
                (string)get_class($field)
            ));
        }
    }

    /**
     * @inheritdoc
     */
    public function getQuery(
        FieldInterface $field,
        ElementInterface $element = null
    ): SortableAssociationQueryInterface {
        /** @var DomainsField $field */
        $this->ensureField($field);

        $query = DomainsPlugin::getInstance()->getAssociations()->getQuery();

        $query->siteId = $this->targetSiteId($element);
        $query->fieldId = $field->id;

        return $query;
    }

    /*******************************************
     * NORMALIZE VALUE
     *******************************************/

    /**
     * @inheritdoc
     */
    protected function normalizeQueryInputValue(
        FieldInterface $field,
        $value,
        int &$sortOrder,
        ElementInterface $element = null
    ): SortableAssociationInterface {
        /** @var DomainsField $field */
        $this->ensureField($field);

        if (!is_array($value)) {
            $value = [
                'domain' => $value,
                'status' => $field->defaultStatus
            ];
        }

        return new Domain(
            [
                'fieldId' => $field->id,
                'domain' => ArrayHelper::getValue($value, 'domain'),
                'elementId' => $element ? $element->getId() : false,
                'status' => ArrayHelper::getValue($value, 'status'),
                'siteId' => $this->targetSiteId($element),
                'sortOrder' => $sortOrder++
            ]
        );
    }

    /**
     * @param DomainsField $field
     * @return null|string
     * @throws Exception
     * @throws \Twig_Error_Loader
     */
    public function getSettingsHtml(
        DomainsField $field
    ) {
        return Craft::$app->getView()->renderTemplate(
            'domains/_components/fieldtypes/Domains/settings',
            [
                'field' => $field
            ]
        );
    }

    /**
     * @param DomainsField $field
     * @param DomainsQuery $query
     * @param bool $static
     * @return null|string
     * @throws Exception
     * @throws \Twig_Error_Loader
     */
    public function getInputHtml(DomainsField $field, DomainsQuery $query, bool $static)
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
                'static' => $static,
                'field' => $field
            ]
        );
    }
}
