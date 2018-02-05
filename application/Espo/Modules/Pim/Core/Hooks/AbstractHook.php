<?php
declare(strict_types = 1);

namespace Espo\Modules\Pim\Core\Hooks;

use Espo\Core\Hooks\Base as BaseHook;
use Espo\ORM\Entity;
use Espo\Core\Templates\Services\Base;

/**
 * AbstractPriceHook hook
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
abstract class AbstractHook extends BaseHook
{

    /**
     * Construct
     */
    public function __construct(...$args)
    {
        // call parent
        parent::__construct(...$args);

        // add dependecies
        $this->addDependency('serviceFactory');
    }

    /**
     * Create service
     *
     * @param string $serviceName
     *
     * @return mixed
     */
    protected function createService(string $serviceName)
    {
        return $this->getInjection('serviceFactory')->create($serviceName);
    }

    /**
     * Entity field is unique?
     *
     * @param Entity $entity
     * @param string $field
     *
     * @return bool
     */
    protected function isUnique(Entity $entity, string $field): bool
    {
        // prepare result
        $result = true;

        // find product
        $fundedEntity = $this->getEntityManager()
            ->getRepository($entity->getEntityName())
            ->where([$field => $entity->get($field)])
            ->findOne();

        if (!empty($fundedEntity) && $fundedEntity->get('id') != $entity->get('id')) {
            $result = false;
        }

        return $result;
    }
}
