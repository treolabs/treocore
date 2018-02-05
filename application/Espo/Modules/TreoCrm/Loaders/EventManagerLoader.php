<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Loaders;

use Espo\Core\Loaders\Base;
use Espo\Modules\TreoCrm\Core\Utils\EventManager;

/**
 * EventManager loader
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class EventManagerLoader extends Base
{

    /**
     * Load EventManager
     *
     * @return EventManager
     */
    public function load()
    {
        return (new EventManager())->setContainer($this->getContainer());
    }
}
