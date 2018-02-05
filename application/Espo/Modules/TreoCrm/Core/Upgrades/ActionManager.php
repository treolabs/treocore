<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Core\Upgrades;

use Espo\Core\Upgrades\ActionManager as EspoActionManager;
use Espo\Core\Exceptions\Error;

/**
 * Class of ActionManager
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class ActionManager extends EspoActionManager
{
    /**
     * @var array
     */
    protected $objects;

    /**
     * Get object
     *
     * @return mixed
     * @throws Error
     */
    protected function getObject()
    {
        // prepare params
        $managerName = $this->getManagerName();
        $actionName  = $this->getAction();

        if (!isset($this->objects[$managerName][$actionName])) {
            $class = '\Espo\Modules\TreoCrm\Core\Upgrades\Actions\\'.ucfirst($managerName).'\\'.ucfirst($actionName);

            if (!class_exists($class)) {
                $class = '\Espo\Core\Upgrades\Actions\\'.ucfirst($managerName).'\\'.ucfirst($actionName);
            }

            if (!class_exists($class)) {
                throw new Error('Could not find an action ['.ucfirst($actionName).'], class ['.$class.'].');
            }

            $this->objects[$managerName][$actionName] = new $class($this->getContainer(), $this);
        }

        return $this->objects[$managerName][$actionName];
    }
}
