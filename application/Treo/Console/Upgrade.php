<?php
/**
 * This file is part of EspoCRM and/or TreoCore.
 *
 * EspoCRM - Open Source CRM application.
 * Copyright (C) 2014-2019 Yuri Kuznetsov, Taras Machyshyn, Oleksiy Avramenko
 * Website: http://www.espocrm.com
 *
 * TreoCore is EspoCRM-based Open Source Product Information Management application.
 * Copyright (C) 2017-2019 TreoLabs GmbH
 * Website: http://www.treopim.com
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
 * and "TreoPIM" word.
 */

declare(strict_types=1);

namespace Treo\Console;

use Espo\Core\UpgradeManager;
use Treo\Services\TreoUpgrade;
use Treo\Core\Migration\Migration;

/**
 * Class Upgrade
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Upgrade extends AbstractConsole
{
    /**
     * Get console command description
     *
     * @return string
     */
    public static function getDescription(): string
    {
        return "Upgrading of Treo Core.";
    }

    /**
     * Run action
     *
     * @param array $data
     */
    public function run(array $data): void
    {
        // validate action
        if (!in_array($data['action'], ['--download', '--force'])) {
            self::show("Invalid 'action' param", self::ERROR, true);
        }

        // get versions
        $versions = array_column($this->getService()->getVersions(), 'link', 'version');
        if (!isset($versions[$data['versionTo']])) {
            self::show('No such version for upgrade.', self::ERROR, true);
        }

        // download
        try {
            $package = $this->getService()->downloadPackage($versions[$data['versionTo']]);
        } catch (\Throwable $e) {
            self::show("Package downloading failed!", self::ERROR, true);
        }

        if ($data['action'] == '--download') {
            self::show('Upgrade package downloaded successfully.', self::SUCCESS, true);
        }

        if ($data['action'] == '--force') {
            // upgrade
            $upgradeManager = new UpgradeManager($this->getContainer());
            $upgradeManager->install(['id' => $package]);

            // update minimum stability
            $this->minimumStability();

            self::show('Treo system upgraded successfully.', self::SUCCESS, true);
        }
    }

    /**
     * @return TreoUpgrade
     */
    protected function getService(): TreoUpgrade
    {
        return $this->getContainer()->get('serviceFactory')->create('TreoUpgrade');
    }

    /**
     * Update composer minimum-stability
     */
    protected function minimumStability(): void
    {
        // prepare data
        $data = json_decode(file_get_contents('composer.json'), true);
        $data['minimum-stability'] = (!empty($this->getConfig()->get('developMode'))) ? 'rc' : 'stable';
        file_put_contents('composer.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
