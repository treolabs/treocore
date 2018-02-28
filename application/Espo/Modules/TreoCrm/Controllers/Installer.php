<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Controllers;

use Espo\Core\Controllers\Base;
use Slim\Http\Request;
use Espo\Core\Exceptions;
use Espo\Modules\TreoCrm\Services\Installer as InstallerService;

/**
 * Class Installer
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class Installer extends Base
{
    /**
     * @param         $params
     * @param         $data
     * @param Request $request
     *
     * @return array
     * @throws Exceptions\BadRequest
     * @throws Exceptions\Forbidden
     */
    public function actionSetLanguage($params, $data, Request $request): array
    {
        // check method
        if (!$request->isPost()) {
            throw new Exceptions\BadRequest();
        }

        /** @var InstallerService $installer */
        $installer = $this->getService('Installer');

        // check if is install
        if ($installer->isInstall()) {
            throw new Exceptions\Forbidden();
        }

        $post = get_object_vars($data);

        // check if input params exists
        if (!isset($post['language'])) {
            throw new Exceptions\BadRequest();
        }

        return $installer->setLanguage($post['language']);
    }

    /**
     * @param         $params
     * @param         $data
     * @param Request $request
     *
     * @return array
     * @throws Exceptions\BadRequest
     * @throws Exceptions\Forbidden
     */
    public function actionSetDbSettings($params, $data, Request $request): array
    {
        // check method
        if (!$request->isPost()) {
            throw new Exceptions\BadRequest();
        }

        /** @var InstallerService $installer */
        $installer = $this->getService('Installer');

        // check if is install
        if ($installer->isInstall()) {
            throw new Exceptions\Forbidden();
        }

        $post = get_object_vars($data);

        // check if input params exists
        if (!$this->isValidDbParams($post)) {
            throw new Exceptions\BadRequest();
        }

        return $installer->setDbSettings($post);
    }

    public function actionCreateAdmin($params, $data, Request $request): array
    {
        // check method
        if (!$request->isPost()) {
            throw new Exceptions\BadRequest();
        }

        /** @var InstallerService $installer */
        $installer = $this->getService('Installer');

        // check if is install
        if ($installer->isInstall()) {
            throw new Exceptions\Forbidden();
        }

        $post = get_object_vars($data);

        // check if input params exists
        if (empty($post['userName']) || empty($post['password']) || empty($post['confirmPassword'])) {
            throw new Exceptions\BadRequest();
        }

        return $installer->createAdmin($post);
    }

    /**
     * @param         $params
     * @param         $data
     * @param Request $request
     *
     * @return array
     * @throws Exceptions\BadRequest
     * @throws Exceptions\Forbidden
     */
    public function actionCheckDbConnect($params, $data, Request $request): array
    {
        // check method
        if (!$request->isPost()) {
            throw new Exceptions\BadRequest();
        }

        /** @var InstallerService $installer */
        $installer = $this->getService('Installer');

        // check if is install
        if ($installer->isInstall()) {
            throw new Exceptions\Forbidden();
        }

        $post = get_object_vars($data);

        // check if input params exists
        if (!$this->isValidDbParams($post)) {
            throw new Exceptions\BadRequest();
        }

        return $installer->checkDbConnect($post);
    }

    /**
     * Check if valid db params
     *
     * @param array $post
     *
     * @return bool
     */
    protected function isValidDbParams(array $post): bool
    {
        return isset($post['host']) && isset($post['dbname']) && isset($post['user']);
    }
}
