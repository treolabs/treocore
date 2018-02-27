<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Core\Utils;

use \Espo\Core\Utils\Auth as EspoAuth;

/**
 * Class Auth
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class Auth extends EspoAuth
{

    /**
     * Disable auth
     *
     * @throws \Espo\Core\Exceptions\Error
     */
    public function useNoAuth()
    {
        if (!empty($this->getConfig()->get('database')['name'])) {
            parent::useNoAuth();
        }
    }
}