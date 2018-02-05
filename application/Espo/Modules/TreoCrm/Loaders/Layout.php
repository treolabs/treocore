<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Loaders;

use Espo\Core\Loaders\Base;
use Espo\Modules\TreoCrm\Core\Utils\Layout as LayoutUtil;

/**
 * Layout loader
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Layout extends Base
{

    /**
     * Load Layout
     *
     * @return Layout
     */
    public function load()
    {
        return (new LayoutUtil())->setContainer($this->getContainer());
    }
}
