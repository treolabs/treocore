<?php
declare(strict_types = 1);

namespace Espo\Modules\Pim\Traits;

use Espo\ORM\EntityManager;

/**
 * EntityManager trait
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
trait EntityManagerTrait
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * Set entity manager
     *
     * @param EntityManager $entityManager
     *
     * @return $this
     */
    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;

        return $this;
    }

    /**
     * Get entity manager
     *
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }
}
