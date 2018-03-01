<?php

namespace flipbox\domains\actions\traits;

use Craft;
use flipbox\domains\Domains as DomainsPlugin;
use flipbox\domains\fields\Domains;
use flipbox\domains\models\Domain;
use yii\base\Model;
use yii\web\HttpException;
use yii\web\Response;

trait Lookup
{
    /**
     * @param Model $model
     *
     * @return Model|Response
     */
    abstract public function runInternal(Model $model);

    /**
     * @return Domains
     */
    abstract protected function getField(): Domains;

    /**
     * @param int $elementId
     * @param string $domain
     *
     * @return Domain|null
     */
    protected function find(int $elementId, string $domain)
    {
        return DomainsPlugin::getInstance()->getDomains()->find(
            $this->getField(),
            [
                'domain' => $domain,
                'elementId' => $elementId
            ]
        );
    }

    /**
     * @param int $elementId
     * @param string $domain
     *
     * @return null|Model|Response
     */
    public function run(int $elementId, string $domain)
    {
        if (!$object = $this->find($elementId, $domain)) {
            return $this->handleNotFoundResponse();
        }

        return $this->runInternal($object);
    }

    /**
     * @return string
     */
    protected function messageNotFound(): string
    {
        return Craft::t('app', 'Unable to find association.');
    }

    /**
     * HTTP not found response code
     *
     * @return int
     */
    protected function statusCodeNotFound(): int
    {
        return 404;
    }

    /**
     * @return null
     * @throws HttpException
     */
    protected function handleNotFoundResponse()
    {
        throw new HttpException(
            $this->statusCodeNotFound(),
            $this->messageNotFound()
        );
    }
}
