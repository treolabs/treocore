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

namespace Treo\Console;

/**
 * Cron console
 *
 * @author r.ratsun@treolabs.com
 */
class Cron extends AbstractConsole
{
    const DAEMON_KILLER = 'data/process-kill.txt';

    /**
     * Get console command description
     *
     * @return string
     */
    public static function getDescription(): string
    {
        return 'Run CRON.';
    }

    /**
     * Run action
     *
     * @param array $data
     */
    public function run(array $data): void
    {
        if (empty($this->getConfig()->get('isInstalled'))) {
            exit(1);
        }

        /** @var string $id */
        $id = $data['id'];

        // kill daemon killer
        if (file_exists(self::DAEMON_KILLER)) {
            unlink(self::DAEMON_KILLER);
        }

        // get active processes
        exec('ps ax | grep index.php', $processes);
        $processes = implode(' | ', $processes);

        /** @var string $php */
        $php = (new \Espo\Core\Utils\System())->getPhpBin();

        // open composer daemon
        if (empty(strpos($processes, "index.php composer daemon $id --up"))) {
            exec("$php index.php composer daemon $id --up >/dev/null 2>&1 &");
        }

        // run cron jobs
        $this->runCronManager();
    }

    /**
     * Run cron manager
     */
    protected function runCronManager(): void
    {
        $auth = new \Treo\Core\Utils\Auth($this->getContainer());
        $auth->useNoAuth();

        $this->getContainer()->get('cronManager')->run();
    }
}
