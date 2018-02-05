<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Core\Utils;

use Espo\Modules\TreoCrm\Listeners\AbstractListener;
use Espo\Modules\TreoCrm\Traits\ContainerTrait;

/**
 * Class of EventManager
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class EventManager
{

    use ContainerTrait;

    /**
     * Triggered an event
     *
     * @param string $target
     * @param string $action
     * @param array $data
     *
     * @return mixed
     */
    public function triggered(string $target, string $action, array $data = [])
    {
        foreach ($this->getContainer()->get('metadata')->getModuleList() as $module) {
            // prepare filename
            $className = sprintf('Espo\Modules\%s\Listeners\%s', $module, $target);
            if (class_exists($className)) {
                $listener = new $className();
                if ($listener instanceof AbstractListener) {
                    $listener->setContainer($this->getContainer());
                }
                if (method_exists($listener, $action)) {
                    $listener->{$action}($data);
                }
            }
        }
    }
}
