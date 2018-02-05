<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Services;

/**
 * Interface of StatusActionInterface
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
interface StatusActionInterface
{

    /**
     * Get progress status action data
     *
     * @param array $data
     *
     * @return array
     */
    public function getProgressStatusActionData(array $data): array;
}
