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

use Espo\Core\Utils\Json;
use Espo\Core\Utils\Util;
use Treo\Services\Composer;

/**
 * Class ComposerDaemon
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class ComposerDaemon extends AbstractConsole
{
    /**
     * @inheritDoc
     */
    public static function getDescription(): string
    {
        return 'Create daemon for composer.';
    }

    /**
     * @inheritDoc
     */
    public function run(array $data): void
    {
        /** @var string $runner */
        $runner = 'data/treo-composer-run.txt';

        /** @var string $log */
        $log = 'data/treo-composer.log';

        while (true) {
            // delete check-up file
            if (file_exists(Composer::CHECK_UP_FILE)) {
                unlink(Composer::CHECK_UP_FILE);
            }

            if (file_exists(Cron::DAEMON_KILLER)) {
                break;
            }

            if (file_exists($runner)) {
                // remove runner file
                unlink($runner);

                // cleanup log
                file_put_contents($log, '');

                /** @var string $php */
                $php = (new \Espo\Core\Utils\System())->getPhpBin();

                // execute composer update
                exec("$php composer.phar update >> $log 2>&1", $output, $exitCode);

                // set end of log file
                $content = file_get_contents($log);
                if ($exitCode > 0) {
                    $content .= '{{error}}';
                } else {
                    $content .= '{{success}}';
                }
                file_put_contents($log, $content);

                // push to log
                $this->log($content);
            }

            sleep(1);
        }
    }

    /**
     * @param string $content
     */
    protected function log(string $content): void
    {
        // prepare status
        $status = 1;
        if (strpos($content, '{{success}}') !== false) {
            $status = 0;
        }

        // prepare content
        $content = \trim(str_replace(['{{success}}', '{{error}}'], ['', ''], $content));

        // prepare createdById
        $createdById = 'system';
        if (!empty($this->getConfig()->get('composerUser'))) {
            $createdById = $this->getConfig()->get('composerUser');
        }

        // get em
        $em = $this->getContainer()->get('entityManager');

        // prepare note
        $note = $em->getEntity('Note');
        $note->set('type', 'composerUpdate');
        $note->set('parentType', 'ModuleManager');
        $note->set('data', ['status' => $status, 'output' => $content]);
        $note->set('createdById', $createdById);

        // save note
        $em->saveEntity($note, ['skipCreatedBy' => true]);

        // unset user
        $this->getConfig()->set('composerUser', null);

        // unblock composer UI
        $this->getConfig()->set('isUpdating', false);

        // save config
        $this->getConfig()->save();
    }
}