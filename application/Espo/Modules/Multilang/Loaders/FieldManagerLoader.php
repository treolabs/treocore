<?php
declare(strict_types = 1);

namespace Espo\Modules\Multilang\Loaders;

use Espo\Core\Loaders\Base;
use Espo\Modules\Multilang\Core\Utils\FieldManager;

/**
 * FieldManager loader
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class FieldManagerLoader extends Base
{

    /**
     * Load FieldManager
     *
     * @return FieldManager
     */
    public function load()
    {
        return (new FieldManager())->setContainer($this->getContainer());
    }
}
