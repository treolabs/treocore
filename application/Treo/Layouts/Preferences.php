<?php
/**
 * This file is part of EspoCRM and/or TreoCore.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoCore is EspoCRM-based Open Source application.
 * Copyright (C) 2017-2019 TreoLabs GmbH
 * Website: https://treolabs.com
 *
 * TreoCore as well as EspoCRM is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * TreoCore as well as EspoCRM is distributed in the hope that it will be useful,
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
 * and "TreoCore" word.
 */

declare(strict_types=1);

namespace Treo\Layouts;

use Espo\Entities\User;

/**
 * Preferences layout
 *
 * @author r.ratsun@zinitsolutions.com
 */
class Preferences extends AbstractLayout
{
    /**
     * @var array
     */
    protected $locale
        = [
            [
                [
                    'name' => 'dateFormat'
                ],
                [
                    'name' => 'timeZone'
                ],
            ],
            [
                [
                    'name' => 'timeFormat'
                ],
                [
                    'name' => 'weekStart'
                ],
            ],
            [
                [
                    'name' => 'decimalMark'
                ],
                [
                    'name' => 'thousandSeparator'
                ],
            ],
            [
                [
                    'name' => 'language'
                ],
                [
                ],
            ],
        ];

    protected $notification
        = [

            [
                [
                    'name' => 'receiveNewSystemVersionNotifications'
                ],
                [
                    'name' => 'receiveNewModuleVersionNotifications'
                ],
            ],
            [
                [
                    'name' => 'receiveInstallDeleteModuleNotifications'
                ],
                false
            ]
        ];

    /**
     * Layout detail
     *
     * @param array $data
     *
     * @return array
     */
    public function layoutDetail(array $data): array
    {
        if (isset($data[0]['rows'])) {
            $data[0]['rows'] = $this->locale;
        }

        foreach ($data as $panelKey => $panel) {
            if ($panel['name'] == 'notifications') {
                // check is admin user
                if ($this->getUser()->isAdmin()) {
                    // add notifications
                    $data[$panelKey]['rows'] = array_merge($data[$panelKey]['rows'], $this->notification);
                } else {
                    // remove panel from layout
                    array_splice($data, $panelKey, 1);
                }
            }
        }

        return $data;
    }

    /**
     * Layout detailPortal
     *
     * @param array $data
     *
     * @return array
     */
    public function layoutDetailPortal(array $data): array
    {
        if (isset($data[0]['rows'])) {
            $data[0]['rows'] = $this->locale;
        }

        return $data;
    }

    /**
     * Get user
     *
     * @return User
     */
    protected function getUser(): User
    {
        return $this->getContainer()->get('user');
    }
}
