<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Services;

/**
 * Interface of InterfaceJobCreatorService
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
interface InterfaceJobCreatorService
{

    /**
     * Get scheduled Jobs
     *
     * @return array
     */
    public function getScheduledJobs(): array;
}
