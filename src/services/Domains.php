<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\services;

use flipbox\domains\db\DomainsQuery;
use flipbox\domains\fields\Domains as DomainsField;
use flipbox\ember\exceptions\NotFoundException;
use flipbox\ember\helpers\RecordHelper;
use flipbox\ember\services\traits\queries\BaseAccessor;
use yii\base\Component;
use yii\db\QueryInterface;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since 1.0.0
 */
class Domains extends Component
{
    use BaseAccessor;

    /**
     * @param DomainsField $field
     * @inheritdoc
     */
    public function getQuery(DomainsField $field, $config = []): QueryInterface
    {
        return new DomainsQuery($field, $config);
    }

    /*******************************************
     * FIND / GET
     *******************************************/

    /**
     * @param DomainsField $field
     * @return array[]
     */
    public function findAll(DomainsField $field)
    {
        return $this->findAllByCondition($field, null);
    }

    /**
     * @param DomainsField $field
     * @param $identifier
     * @return mixed|null
     */
    public function find(DomainsField $field, $identifier)
    {
        return $this->findByCondition($field, $identifier);
    }

    /**
     * @param DomainsField $field
     * @param $identifier
     * @return mixed
     * @throws NotFoundException
     */
    public function get(DomainsField $field, $identifier)
    {
        if (null === ($object = $this->find($field, $identifier))) {
            $this->notFoundException();
        }

        return $object;
    }


    /*******************************************
     * ONE CONDITION
     *******************************************/

    /**
     * @param DomainsField $field
     * @param $condition
     * @return mixed|null
     */
    public function findByCondition(DomainsField $field, $condition)
    {
        return $this->findByCriteria(
            $field, RecordHelper::conditionToCriteria($condition)
        );
    }

    /**
     * @param DomainsField $field
     * @param $condition
     * @return mixed
     * @throws NotFoundException
     */
    public function getByCondition(DomainsField $field, $condition)
    {
        if (null === ($object = $this->findByCondition($field, $condition))) {
            $this->notFoundException();
        }

        return $object;
    }


    /*******************************************
     * ONE CRITERIA
     *******************************************/

    /**
     * @param DomainsField $field
     * @param $criteria
     * @return mixed|null
     */
    public function findByCriteria(DomainsField $field, $criteria)
    {
        $object = $this->findByQuery(
            $this->getQuery($field, $criteria)
        );

        return $object;
    }

    /**
     * @param DomainsField $field
     * @param $criteria
     * @return mixed
     * @throws NotFoundException
     */
    public function getByCriteria(DomainsField $field, $criteria)
    {
        if (null === ($record = $this->findByCriteria($field, $criteria))) {
            $this->notFoundException();
        }

        return $record;
    }


    /*******************************************
     * ALL CONDITION
     *******************************************/

    /**
     * @param DomainsField $field
     * @param array $condition
     * @return array
     */
    public function findAllByCondition(DomainsField $field, $condition = []): array
    {
        return $this->findAllByCriteria(
            $field, RecordHelper::conditionToCriteria($condition)
        );
    }

    /**
     * @param DomainsField $field
     * @param array $condition
     * @return array
     * @throws NotFoundException
     */
    public function getAllByCondition(DomainsField $field, $condition = []): array
    {
        $records = $this->findAllByCondition($field, $condition);
        if (empty($records)) {
            $this->notFoundException();
        }

        return $records;
    }

    /*******************************************
     * ALL CRITERIA
     *******************************************/

    /**
     * @param DomainsField $field
     * @param array $criteria
     * @return array
     */
    public function findAllByCriteria(DomainsField $field, $criteria = []): array
    {
        $records = $this->findAllByQuery(
            $this->getQuery($field, $criteria)
        );

        return $records;
    }

    /**
     * @param DomainsField $field
     * @param array $criteria
     * @return array
     * @throws NotFoundException
     */
    public function getAllByCriteria(DomainsField $field, $criteria = []): array
    {
        $records = $this->findAllByCriteria($field, $criteria);
        if (empty($records)) {
            $this->notFoundException();
        }

        return $records;
    }
}
