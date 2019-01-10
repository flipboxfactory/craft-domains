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
class AssociateDomain extends Action
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
     * @param string $status
     * @param int|null $siteId
     * @param int|null $sortOrder
     * @return Domain
     * @throws \yii\web\HttpException
     */
    public function run(
        string $field,
        string $element,
        string $domain,
        string $status,
        int $siteId = null,
        int $sortOrder = null
    ) {
        // Resolve
        $field = $this->resolveField($field);
        $element = $this->resolveElement($element);

        $siteId = SiteHelper::ensureSiteId($siteId ?: $element->siteId);

        // Find existing?
        if (!empty($domain)) {
            $record = Domain::findOne([
                'element' => $element,
                'field' => $field,
                'domain' => $domain,
                'siteId' => $siteId,
            ]);
        }

        if (empty($record)) {
            $record = new Domain();
            $record->setField($field)
                ->setElement($element)
                ->setSiteId(SiteHelper::ensureSiteId($siteId ?: $element->siteId));
        }

        $record->domain = $domain;
        $record->status = $status;
        $record->sortOrder = $sortOrder;

        return $this->runInternal($domain);
    }

    /**
     * @inheritdoc
     * @param Domain $record
     * @throws \Exception
     */
    protected function performAction(Domain $record): bool
    {
        return $record->save();
    }
}
