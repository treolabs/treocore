<?php
declare(strict_types = 1);

namespace Espo\Modules\Pim\Core\ORM;

use Espo\Core\ORM\EntityManager as EspoEntityManager;
use Espo\ORM\DB\Query\Base;

/**
 * Class of EntityManager
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class EntityManager extends EspoEntityManager
{

    /**
     * Get query
     *
     * @return Base
     */
    public function getQuery(): Base
    {
        if (empty($this->query)) {
            $platform    = $this->params['platform'];
            $className   = '\\Espo\\Modules\\Pim\\ORM\\DB\\Query\\'.ucfirst($platform);
            $this->query = new $className($this->getPDO(), $this->entityFactory);
        }

        return $this->query;
    }
}
