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

use Treo\Core\ORM\EntityManager;
use Treo\Core\QueueManager;
use Treo\Services\Composer;

/**
 * Class Daemon
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Daemon extends AbstractConsole
{
    /**
     * @var bool
     */
    public static $isHidden = true;

    /**
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function run(array $data): void
    {
        $method = $data['name'] . 'Daemon';
        if (method_exists($this, $method)) {
            $this->$method($data['id']);
        }
    }

    /**
     * @param string $id
     */
    protected function composerDaemon(string $id): void
    {
        /** @var string $log */
        $log = Composer::COMPOSER_LOG;

        while (true) {
            // delete check-up file
            if (file_exists(Composer::CHECK_UP_FILE)) {
                unlink(Composer::CHECK_UP_FILE);
            }

            if (file_exists(Cron::DAEMON_KILLER)) {
                break;
            }

            if (file_exists($log)) {
                /** @var string $userId */
                $userId = file_get_contents($log);

                // cleanup
                file_put_contents($log, '');

                // execute composer update
                exec($this->getPhp() . " composer.phar update >> $log 2>&1", $output, $exitCode);

                // set end of log file (for frontend)
                $content = file_get_contents($log);
                if ($exitCode > 0) {
                    file_put_contents($log, $content . '{{error}}');
                } else {
                    file_put_contents($log, $content . '{{success}}');
                }

                // remove file
                unlink($log);

                /** @var EntityManager $em */
                $em = $this->getContainer()->get('entityManager');

                // prepare note
                $note = $em->getEntity('Note');
                $note->set('type', 'composerUpdate');
                $note->set('parentType', 'ModuleManager');
                $note->set('data', ['status' => ($exitCode == 0) ? 0 : 1, 'output' => $content]);
                $note->set('createdById', $userId);

                // save note
                $em->saveEntity($note, ['skipCreatedBy' => true]);
            }

            sleep(1);
        }
    }

    /**
     * @param string $id
     */
    protected function qmDaemon(string $id): void
    {
        /** @var string $stream */
        $stream = explode('-', $id)[0];

        while (true) {
            if (file_exists(Cron::DAEMON_KILLER)) {
                break;
            }

            if (file_exists(sprintf(QueueManager::QUEUE_PATH, $stream))) {
                exec($this->getPhp() . " index.php qm $stream --run");
            }

            sleep(1);
        }
    }

    /**
     * @param string $id
     */
    protected function notificationDaemon(string $id): void
    {
        while (true) {
            if (file_exists(Cron::DAEMON_KILLER)) {
                break;
            }

            exec($this->getPhp() . " index.php notifications --refresh");

            sleep(5);
        }
    }

    /**
     * @return string
     */
    protected function getPhp(): string
    {
        return (new \Espo\Core\Utils\System())->getPhpBin();
    }
}