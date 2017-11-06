<?php

/**
 * @copyright  Copyright (c) Flipbox Digital Limited
 * @license    https://flipboxfactory.com/software/domains/license
 * @link       https://www.flipboxfactory.com/software/domains/
 */

namespace flipbox\domains\actions;

use flipbox\domains\Domains as DomainsPlugin;
use flipbox\domains\models\Domain;

/**
 * @author Flipbox Factory <hello@flipboxfactory.com>
 * @since  1.0.0
 */
abstract class Associate extends Action
{
    use traits\Save;

    /**
     * @param int $elementId
     * @param string $domain
     *
     * @return Domain
     */
    public function run(int $elementId, string $domain)
    {
        return $this->runInternal(
            $this->resolve($elementId, $domain)
        );
    }

    /**
     * @param int $elementId
     * @param string $domain
     *
     * @return Domain
     */
    protected function resolve(int $elementId, string $domain)
    {
        if (!$model = DomainsPlugin::getInstance()->getRelationship()->find(
            $this->getField(),
            $domain,
            $elementId
        )) {
            $model = new Domain(
                $this->getField(),
                [
                    'elementId' => $elementId,
                    'domain' => $domain
                ]
            );
        }

        return $model;
    }
}
