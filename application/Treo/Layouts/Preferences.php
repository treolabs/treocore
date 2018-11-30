<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2018 TreoLabs GmbH
 * Website: http://www.treopim.com
 *
 * TreoPIM as well as EspoCRM is free software: you can redistribute it and/or modify
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

namespace Treo\Layouts;

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
                    'name' => 'receiveNewModuleNotifications'
                ],
                [
                    'name' => 'receiveInstallDeleteModuleNotifications'
                ],
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
                $data[$panelKey]['rows'] = array_merge($data[$panelKey]['rows'], $this->notification);
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
}
