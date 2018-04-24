<?php
/**
 * This file is part of EspoCRM and/or TreoPIM.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2018 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoPIM is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2018 Zinit Solutions GmbH
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

namespace Espo\Modules\TreoCore\Console;

use Espo\Modules\TreoCore\Traits\ContainerTrait;

/**
 * AbtractConsole class
 *
 * @author r.ratsun@zinitsolutions.com
 */
abstract class AbstractConsole
{
    use ContainerTrait;

    /**
     * Run action
     *
     * @param array $data
     */
    abstract public function run(array $data): void;

    /**
     * Echo CLI message
     *
     * @param string $message
     * @param int    $status
     * @param bool   $stop
     */
    public static function show(string $message, int $status = 0, bool $stop = false): void
    {
        switch ($status) {
            // success
            case 1:
                echo "\033[0;32m{$message}\033[0m" . PHP_EOL;
                break;
            // error
            case 2:
                echo "\033[1;31m{$message}\033[0m" . PHP_EOL;
                break;
            // default
            default:
                echo $message . PHP_EOL;
                break;
        }

        if ($stop) {
            die();
        }
    }
}
