<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\craft\domains\queries;

use craft\db\QueryAbortedException;
use craft\helpers\Db;
use flipbox\craft\ember\helpers\QueryHelper;
use flipbox\craft\ember\queries\CacheableActiveQuery;
use flipbox\craft\ember\queries\ElementAttributeTrait;
use flipbox\craft\ember\queries\FieldAttributeTrait;
use flipbox\craft\ember\queries\SiteAttributeTrait;
use flipbox\craft\domains\records\Domain;

/**
 * @method Domain[] getCachedResult()
 */
class DomainsQuery extends CacheableActiveQuery
{
    use FieldAttributeTrait,
        ElementAttributeTrait,
        SiteAttributeTrait;

    /**
     * @var string|string[]|false|null The domain(s). Prefix with "not " to exclude them.
     */
    public $domain;

    /**
     * @var string|string[]|false|null The status(es). Prefix with "not " to exclude them.
     */
    public $status;

    /**
     * @param array $config
     * @return $this
     */
    public function configure(array $config)
    {
        QueryHelper::configure(
            $this,
            $config
        );

        return $this;
    }

    /**
     * @param $value
     * @return static
     */
    public function domain($value)
    {
        $this->domain = $value;
        return $this;
    }

    /**
     * @param $value
     * @return static
     */
    public function setDomain($value)
    {
        return $this->domain($value);
    }

    /**
     * @param $value
     * @return static
     */
    public function status($value)
    {
        $this->status = $value;
        return $this;
    }

    /**
     * @param $value
     * @return static
     */
    public function setStatus($value)
    {
        return $this->status($value);
    }

    /**
     * @inheritdoc
     *
     * @throws QueryAbortedException if it can be determined that there won’t be any results
     */
    public function prepare($builder)
    {
        // Is the query already doomed?
        if (($this->field !== null && empty($this->field)) ||
            ($this->domain !== null && empty($this->domain)) ||
            ($this->element !== null && empty($this->element))
        ) {
            throw new QueryAbortedException();
        }

        $this->applyConditions();
        $this->applySiteConditions();
        $this->applyFieldConditions();
        $this->applyElementConditions();

        return parent::prepare($builder);
    }

    /**
     * Apply attribute conditions
     */
    protected function applyConditions()
    {
        $attributes = ['domain', 'status'];

        foreach ($attributes as $attribute) {
            if (null !== ($value = $this->{$attribute})) {
                $this->andWhere(Db::parseParam($attribute, $value));
            }
        }
    }

    /**
     *  Apply query specific conditions
     */
    protected function applyElementConditions()
    {
        if ($this->element !== null) {
            $this->andWhere(Db::parseParam('elementId', $this->parseElementValue($this->element)));
        }
    }

    /**
     *  Apply query specific conditions
     */
    protected function applyFieldConditions()
    {
        if ($this->field !== null) {
            $this->andWhere(Db::parseParam('fieldId', $this->parseFieldValue($this->field)));
        }
    }

    /**
     *  Apply query specific conditions
     */
    protected function applySiteConditions()
    {
        if ($this->site !== null) {
            $this->andWhere(Db::parseParam('siteId', $this->parseSiteValue($this->site)));
        }
    }
}
