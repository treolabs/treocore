<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Controllers;

use Espo\Core\Controllers\Base;
use Slim\Http\Request;
use Espo\Core\Exceptions;

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
     * @return bool
     * @throws Exceptions\BadRequest
     */
    public function actionSetDbSettings($params, $data, Request $request): bool
    {
        // check method
        if (!$request->isPost()) {
            throw new Exceptions\BadRequest();
        }

        $post = get_object_vars($data);

        // check if input params exists
        if (!$this->isValidDbParams($post)) {
            throw new Exceptions\BadRequest();
        }

        return $this->getService('Installer')->setDbSettings($post);
    }

    /**
     * @param         $params
     * @param         $data
     * @param Request $request
     *
     * @return array
     * @throws Exceptions\BadRequest
     */
    public function actionCheckDbConnect($params, $data, Request $request): array
    {
        // check method
        if (!$request->isPost()) {
            throw new Exceptions\BadRequest();
        }

        $post = get_object_vars($data);

        // check if input params exists
        if (!$this->isValidDbParams($post)) {
            throw new Exceptions\BadRequest();
        }

        return $this->getService('Installer')->checkDbConnect($post);
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
