<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\db\traits;

use craft\base\ElementInterface;
use flipbox\domains\fields\Domains;
use flipbox\domains\models\Domain;

trait PopulateModel
{
    /**
     * @return Domains
     */
    abstract public function getField(): Domains;

    /**
     * @return string|callable
     */
    abstract protected function getIndexBy();

    /**
     * @inheritdoc
     *
     * @return ElementInterface[]|array The resulting elements.
     */
    public function populate($rows)
    {
        $indexBy = $this->getIndexBy();

        if ($indexBy === null) {
            return $this->createModels($rows);
        }
        $result = [];
        foreach ($rows as $row) {
            if (is_string($indexBy)) {
                $key = $row[$indexBy];
            } else {
                $key = call_user_func($indexBy, $row);
            }
            $result[$key] = $this->createModel($row);
        }
        return $result;
    }

    /**
     * @param $rows
     *
     * @return mixed
     */
    protected function createModels($rows)
    {
        $models = [];

        foreach ($rows as $key => $row) {
            $models[$key] = $this->createModel($row);
        }

        return $models;
    }

    /**
     * @param $row
     *
     * @return Domain
     */
    protected function createModel($row): Domain
    {
        return new Domain($this->getField(), $row);
    }
}
