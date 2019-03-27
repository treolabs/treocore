<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2019 TreoLabs GmbH
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

namespace Treo\Console;

/**
 * Class KillProcess
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class KillProcess extends AbstractConsole
{
    /**
     * @inheritdoc
     */
    public static function getDescription(): string
    {
        return 'Process killer.';
    }

    /**
     * @inheritdoc
     */
    public function run(array $data): void
    {
        switch ($data['id']) {
            case "treo-self-upgrade":
                file_put_contents('data/kill-treo-self-upgrade.txt', '1');
                self::show("Process 'treo-self-upgrade' killed successfully", self::SUCCESS, true);

                break;
            case "treo-module-update":
                file_put_contents('data/kill-treo-module-update.txt', '1');
                self::show("Process 'treo-module-update' killed successfully", self::SUCCESS, true);

                break;
            case "treo-qm":
                file_put_contents('data/kill-treo-qm.txt', '1');
                self::show("Process 'treo-qm' killed successfully", self::SUCCESS, true);

                break;
            case "treo-notification":
                file_put_contents('data/kill-treo-notification.txt', '1');
                self::show("Process 'treo-notification' killed successfully", self::SUCCESS, true);

                break;
        }

        self::show('No such process!', self::ERROR, true);
    }
}
