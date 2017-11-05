<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\db\traits;

use craft\base\ElementInterface;
use craft\helpers\Db;
use Craft;
use craft\models\Site;
use yii\base\Exception;
use yii\db\Expression;

trait Attributes
{
    /**
     * @var string|string[]|false|null The domain(s). Prefix domains with "not " to exclude them.
     */
    public $domain;

    /**
     * @var int|int[]|false|null The element ID(s). Prefix IDs with "not " to exclude them.
     */
    public $elementId;

    /**
     * @var int|null The site ID that the domains should be returned in.
     */
    public $siteId;

    /**
     * @var int|null Sort order
     */
    public $sortOrder;

    /**
     * Adds an additional WHERE condition to the existing one.
     * The new condition and the existing one will be joined using the `AND` operator.
     * @param string|array|Expression $condition the new WHERE condition. Please refer to [[where()]]
     * on how to specify this parameter.
     * @param array $params the parameters (name => value) to be bound to the query.
     * @return $this the query object itself
     * @see where()
     * @see orWhere()
     */
    abstract public function andWhere($condition, $params = []);

    /**
     * @inheritdoc
     * @throws Exception if $value is an invalid site handle
     * return static
     */
    public function element($value)
    {
        if ($value instanceof ElementInterface) {
            $this->elementId = $value->id;
        } else {
            $element = Craft::$app->getElements()->getElementById($value);

            if (!$element) {
                throw new Exception('Invalid element: '.$value);
            }

            $this->elementId = $element->getId();
        }

        return $this;
    }

    /**
     * @inheritdoc
     * return static
     */
    public function elementId($value)
    {
        $this->elementId = $value;

        return $this;
    }

    /**
     * @inheritdoc
     * return static
     */
    public function domain($value)
    {
        $this->domain = $value;

        return $this;
    }

    /**
     * @inheritdoc
     * @throws Exception if $value is an invalid site handle
     */
    public function site($value)
    {
        if ($value instanceof Site) {
            $this->siteId = $value->id;
        } else {
            $site = Craft::$app->getSites()->getSiteByHandle($value);

            if (!$site) {
                throw new Exception('Invalid site handle: '.$value);
            }

            $this->siteId = $site->id;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function siteId(int $value = null)
    {
        $this->siteId = $value;

        return $this;
    }

    /**
     *
     */
    protected function applyConditions()
    {
        if ($this->domain) {
            $this->andWhere(Db::parseParam('domain', $this->domain));
        }

        if ($this->elementId) {
            $this->andWhere(Db::parseParam('elementId', $this->elementId));
        }

        if ($this->siteId !== null) {
            $this->andWhere(Db::parseParam('siteId', $this->siteId));
        } else {
            $this->siteId = Craft::$app->getSites()->currentSite->id;
        }
    }
}
