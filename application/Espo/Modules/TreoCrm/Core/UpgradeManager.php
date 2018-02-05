<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Core;

use Espo\Modules\TreoCrm\Core\Container;
use Espo\Core\UpgradeManager as EspoUpgradeManager;
use Espo\Modules\TreoCrm\Core\Upgrades\ActionManager;

/**
 * Class of UpgradeManager
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class UpgradeManager extends EspoUpgradeManager
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * Construct
     */
    public function __construct($container)
    {
        $this->container = $container;

        $this->actionManager = new ActionManager($this->name, $container, $this->params);
    }

    /**
     * Get Container
     *
     * @return Container
     */
    protected function getContainer()
    {
        return $this->container;
    }
}
