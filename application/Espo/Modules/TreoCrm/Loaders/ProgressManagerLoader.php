<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Loaders;

use Espo\Core\Loaders\Base;
use Espo\Modules\TreoCrm\Core\Utils\ProgressManager;

/**
 * ProgressManager Loader
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class ProgressManagerLoader extends Base
{

    /**
     * Load ProgressManager
     *
     * @return ProgressManager
     */
    public function load()
    {
        return (new ProgressManager())->setContainer($this->getContainer());
    }
}
