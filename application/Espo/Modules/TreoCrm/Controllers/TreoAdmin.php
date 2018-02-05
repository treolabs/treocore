<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Controllers;

use Espo\Controllers\Admin;
use Espo\Core\Exceptions;
use Espo\Modules\TreoCrm\Core\UpgradeManager;

/**
 * TreoAdmin controller
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class TreoAdmin extends Admin
{

    /**
     * UploadUpgradePackage action
     *
     * @param array $params
     * @param array $data
     *
     * @return array
     * @throws Exceptions\Forbidden
     */
    public function postActionUploadUpgradePackage($params, $data)
    {
        if ($this->getConfig()->get('restrictedMode') && !$this->getUser()->get('isSuperAdmin')) {
            throw new Exceptions\Forbidden();
        }

        $upgradeManager = new UpgradeManager($this->getContainer());

        $upgradeId = $upgradeManager->upload($data);
        $manifest  = $upgradeManager->getManifest();

        return [
            'id'      => $upgradeId,
            'version' => $manifest['version'],
        ];
    }
}
