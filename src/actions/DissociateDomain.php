<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\craft\domains\actions;

use flipbox\craft\ember\actions\ManageTrait;
use flipbox\craft\ember\helpers\SiteHelper;
use flipbox\craft\domains\records\Domain;
use yii\base\Action;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since  1.0.0
 */
class DissociateDomain extends Action
{
    use ManageTrait,
        ResolverTrait;

    /**
     * @var int
     */
    public $statusCodeSuccess = 201;

    /**
     * @param string $field
     * @param string $element
     * @param string $domain
     * @param int|null $siteId
     * @return mixed
     * @throws \yii\web\HttpException
     */
    public function run(
        string $field,
        string $element,
        string $domain,
        int $siteId = null
    ) {

        $field = $this->resolveField($field);
        $element = $this->resolveElement($element);

        $query = Domain::find();
        $query->setElement($element)
            ->setField($field)
            ->setDomain($domain)
            ->setSiteId(SiteHelper::ensureSiteId($siteId ?: $element->siteId));

        if (null === ($association = $query->one())) {
            return $this->handleSuccessResponse(true);
        }

        return $this->runInternal($association);
    }

    /**
     * @param Domain $record
     * @return bool
     * @throws \Throwable
     * @throws \yii\db\StaleObjectException
     */
    protected function performAction(Domain $record): bool
    {
        return $record->delete();
    }
}
