<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Services;

use Espo\Core\Services\Base;

/**
 * AdminNotifications service
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class AdminNotifications extends Base
{

    /**
     * New version checker
     *
     * @param array $data
     *
     * @return bool
     */
    public function newVersionChecker($data): bool
    {
        return true;
    }
}
