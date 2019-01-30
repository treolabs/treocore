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

namespace Treo\Composer;

/**
 * Class PreUpdate
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class PreUpdate
{
    /**
     * Run
     */
    public function run(): void
    {
        // storing composer.lock
        $this->storeComposerLock();

        // for developmod
        $this->developMode();
    }

    /**
     * Storing composer.lock
     */
    protected function storeComposerLock(): void
    {
        if (file_exists("data/old-composer.lock")) {
            unlink("data/old-composer.lock");
        }
        if (file_exists("composer.lock")) {
            copy("composer.lock", "data/old-composer.lock");
        }
    }


    /**
     * DevelopMod
     */
    protected function developMode(): void
    {
        // prepare path
        $path = 'composer.json';

        if (file_exists($path)) {
            // prepare data
            $data = json_decode(file_get_contents($path), true);
            if ($this->isDevelopMode()) {
                $data['minimum-stability'] = 'rc';
                $data['require']['phpunit/phpunit'] = '^7';
                $data['require']['squizlabs/php_codesniffer'] = '*';
            } else {
                $data['minimum-stability'] = 'stable';
                if (isset($data['require']['phpunit/phpunit'])) {
                    unset($data['require']['phpunit/phpunit']);
                }
                if (isset($data['require']['squizlabs/php_codesniffer'])) {
                    unset($data['require']['squizlabs/php_codesniffer']);
                }
            }

            file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }

    /**
     * @return bool
     */
    protected function isDevelopMode(): bool
    {
        if (file_exists('data/config.php')) {
            $config = include 'data/config.php';

            return !empty($config['developMode']);
        }

        return false;
    }
}
