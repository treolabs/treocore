<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Loaders;

use Espo\Core\Loaders\Base;
use Espo\Modules\TreoCrm\Core\Utils\EntityManager;

/**
 * EntityManagerUtil loader
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class EntityManagerUtil extends Base
{

    /**
     * Load EntityManager util
     *
     * @return EntityManager
     */
    public function load()
    {
        return new EntityManager($this->getContainer());
    }
}
