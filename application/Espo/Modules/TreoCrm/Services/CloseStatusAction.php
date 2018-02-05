<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Services;

use Espo\Core\Services\Base;

/**
 * CloseStatusAction service
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class CloseStatusAction extends Base implements StatusActionInterface
{

    /**
     * Get progress status action data
     *
     * @param array $data
     *
     * @return array
     */
    public function getProgressStatusActionData(array $data): array
    {
        return [];
    }

    /**
     * Close action
     *
     * @param string $id
     *
     * @return bool
     */
    public function close(string $id): bool
    {
        // prepare result
        $result = false;

        if (!empty($id)) {
            // prepare sql
            $sql = "UPDATE progress_manager SET `deleted`=1 WHERE id='%s'";
            $sql = sprintf($sql, $id);

            $sth = $this
                ->getEntityManager()
                ->getPDO()
                ->prepare($sql);
            $sth->execute();

            // prepare result
            $result = true;
        }

        return $result;
    }
}
