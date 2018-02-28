<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Core\Utils;

use Espo\Core\Utils\Auth as EspoAuth;
use Espo\Core\Exceptions\Error;

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
     * @throws Error
     */
    public function useNoAuth()
    {
        if ($this->getContainer()->get('serviceFactory')->create('Installer')->isInstall()) {
            parent::useNoAuth();
        }
    }
}