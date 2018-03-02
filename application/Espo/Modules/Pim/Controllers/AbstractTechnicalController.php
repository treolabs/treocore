<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM ist Open Source Product Information Managegement (PIM) application,
 * based on EspoCRM.
 * Copyright (C) 2017-2018 Zinit Solutions GmbH
 * Website: http://www.treopim.com
 *
 * TreoPIM as well es EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TreoPIM as well as EspoCRM is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with EspoCRM. If not, see http://www.gnu.org/licenses/.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word
 * and "TreoPIM" word.
 */

declare(strict_types=1);

namespace Espo\Modules\Pim\Controllers;

use Espo\Core\Controllers\Base;
use Slim\Http\Request;

/**
 * AbstractTechController controller
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
abstract class AbstractTechnicalController extends Base
{

    /**
     * Action update
     *
     * @param array   $params
     * @param array   $data
     * @param Request $request
     *
     * @return bool
     */
    public function actionPatch($params, $data, Request $request): bool
    {
        return $this->actionUpdate($params, $data, $request);
    }


    /**
     * Check create action
     *
     * @param         $data
     * @param Request $request
     *
     * @return bool
     */
    protected function isValidCreateAction($data, Request $request): bool
    {
        // prepare result
        $result = true;

        // check Request
        if (!$request->isPost() || !is_array($data)) {
            $result = false;
        }

        return $result;
    }

    /**
     * Check read action
     *
     * @param         $params
     * @param Request $request
     *
     * @return bool
     */
    protected function isValidReadAction($params, Request $request): bool
    {
        // prepare result
        $result = true;

        // check Request
        if (!is_array($params) || empty($params['id']) || !$request->isGet()) {
            $result = false;
        }

        return $result;
    }

    /**
     * Check update action
     *
     * @param         $params
     * @param         $data
     * @param Request $request
     *
     * @return bool
     */
    protected function isValidUpdateAction($params, $data, Request $request): bool
    {
        // prepare result
        $result = true;

        // check Request
        $isParams = is_array($params) && !empty($params['id']);

        if ((!$request->isPut() && !$request->isPatch()) || !$isParams || !is_array($data)) {
            $result = false;
        }

        return $result;
    }

    /**
     * Check delete action
     *
     * @param         $params
     * @param Request $request
     *
     * @return bool
     */
    protected function isValidDeleteAction($params, Request $request): bool
    {
        // prepare result
        $result = true;

        // check Request
        $isParams = is_array($params) && !empty($params['id']);

        if (!$request->isDelete() || !$isParams) {
            $result = false;
        }

        return $result;
    }
}
