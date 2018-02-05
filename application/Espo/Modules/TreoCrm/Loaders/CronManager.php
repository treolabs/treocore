<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Loaders;

use Espo\Core\Loaders\Base;

/**
 * CronManager Loader
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class CronManager extends Base
{

    /**
     * Load CronManager
     *
     * @return \Espo\Modules\TreoCrm\Core\CronManager
     */
    public function load()
    {
        return (new \Espo\Modules\TreoCrm\Core\CronManager())->setContainer($this->getContainer());
    }
}
