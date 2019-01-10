<?php
/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://github.com/flipboxfactory/craft-sortable-associations/blob/master/LICENSE
 * @link       https://github.com/flipboxfactory/craft-sortable-associations
 */

namespace flipbox\craft\domains\fields;

use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use craft\helpers\Db;
use flipbox\craft\domains\records\Domain;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 *
 * @property int $id
 */
trait ModifyElementsQueryTrait
{
    /**
     * @inheritdoc
     */
    public function modifyElementsQuery(
        ElementQueryInterface $query,
        $value
    ) {
        if ($value === null || !$query instanceof ElementQuery) {
            return null;
        }
        if ($value === false) {
            return false;
        }
        if (is_string($value)) {
            $this->modifyElementsQueryForStringValue($query, $value);
            return null;
        }
        $this->modifyElementsQueryForTargetValue($query, $value);
        return null;
    }

    /**
     * @param ElementQuery $query
     * @param string $value
     */
    protected function modifyElementsQueryForStringValue(
        ElementQuery $query,
        string $value
    ) {
        if ($value === 'not :empty:') {
            $value = ':notempty:';
        }
        if ($value === ':notempty:' || $value === ':empty:') {
            $this->modifyElementsQueryForEmptyValue($query, $value);
            return;
        }
        $this->modifyElementsQueryForTargetValue($query, $value);
    }

    /**
     * @param ElementQuery $query
     * @param $value
     */
    protected function modifyElementsQueryForTargetValue(
        ElementQuery $query,
        $value
    ) {
        $alias = Domain::tableAlias();
        $name = Domain::tableName();

        $joinTable = "{$name} {$alias}";
        $query->query->innerJoin($joinTable, "[[{$alias}.elementId]] = [[subquery.elementsId]]");
        $query->subQuery->innerJoin($joinTable, "[[{$alias}.elementId]] = [[elements.id]]");
        $query->subQuery->andWhere(
            Db::parseParam($alias . '.fieldId', $this->id)
        );
        $query->subQuery->andWhere(
            Db::parseParam($alias . '.domain', $value)
        );
        $query->query->distinct(true);
    }

    /**
     * @param ElementQuery $query
     * @param string $value
     */
    protected function modifyElementsQueryForEmptyValue(
        ElementQuery $query,
        string $value
    ) {
        /** @var string $operator */
        $operator = ($value === ':notempty:' ? '!=' : '=');

        $query->subQuery->andWhere(
            $this->emptyValueSubSelect(
                Domain::tableAlias(),
                Domain::tableName(),
                $operator
            )
        );
    }

    /**
     * @param string $alias
     * @param string $name
     * @param string $operator
     * @return string
     */
    protected function emptyValueSubSelect(
        string $alias,
        string $name,
        string $operator
    ): string {
        return "(select count([[{$alias}.elementId]]) from " .
            $name .
            " {{{$alias}}} where [[{$alias}.elementId" .
            "]] = [[elements.id]] and [[{$alias}.fieldId]] = {$this->id}) {$operator} 0";
    }
}
