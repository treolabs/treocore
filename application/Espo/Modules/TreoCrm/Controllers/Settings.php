<?php
declare(strict_types=1);

namespace Espo\Modules\TreoCrm\Controllers;

use Espo\Controllers\Settings as ParentSettings;
use Slim\Http\Request;

/**
 * Settings controller
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Settings extends ParentSettings
{
    /**
     * Patch action
     *
     * @param array   $params
     * @param array   $data
     * @param Request $request
     *
     * @return array
     * @throws \Espo\Core\Exceptions\BadRequest
     * @throws \Espo\Core\Exceptions\Error
     * @throws \Espo\Core\Exceptions\Forbidden
     */
    public function actionPatch($params, $data, $request)
    {
        $result = parent::actionPatch($params, $data, $request);
        // triggered event
        $eventData = ['params' => $params, 'data' => $data, 'request' => $request];
        $this->getContainer()->get('eventManager')->triggered('SettingsController', 'afterUpdate', $eventData);


        return $result;
    }
}
