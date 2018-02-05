<?php
declare(strict_types = 1);

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
     * Update action
     *
     * @param array $params
     * @param array $data
     * @param Request $request
     *
     * @return array
     */
    public function actionUpdate($params, $data, $request)
    {
        // triggered event
        $eventData = ['params' => $params, 'data' => $data, 'request' => $request];
        $this->getContainer()->get('eventManager')->triggered('SettingsController', 'beforeUpdate', $eventData);

        return parent::actionUpdate($params, $data, $request);
    }

    /**
     * Patch action
     *
     * @param array $params
     * @param array $data
     * @param Request $request
     *
     * @return array
     */
    public function actionPatch($params, $data, $request)
    {
        // triggered event
        $eventData = ['params' => $params, 'data' => $data, 'request' => $request];
        $this->getContainer()->get('eventManager')->triggered('SettingsController', 'beforeUpdate', $eventData);

        return parent::actionPatch($params, $data, $request);
    }
}
