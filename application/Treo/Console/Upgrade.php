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
        return "Force upgrading of Treo System. Don't forget to run `composer update`.";
    }

    /**
     * Run action
     *
     * @param array $data
     */
    public function run(array $data): void
    {
        // get versions
        $versions = array_column($this->getService()->getVersions(), 'link', 'version');

        // prepare version to
        $to = (!empty($data['versionTo'])) ? $data['versionTo'] : end(array_keys($versions));

        if (!isset($versions[$to])) {
            self::show('No such version for upgrade.', self::ERROR, true);
        }

        // upgrade treocore
        $upgradeManager = new UpgradeManager($this->getContainer());
        $upgradeManager->install(['id' => $this->getService()->downloadPackage($versions[$to])]);

        // call migration
        $this
            ->getContainer()
            ->get('migration')
            ->run(Migration::CORE_NAME, $this->getConfig()->get('version'), $to);

        // render
        self::show('Treo system upgraded successfully.', self::SUCCESS);
    }

    /**
     * @return TreoUpgrade
     */
    protected function getService(): TreoUpgrade
    {
        return $this->getContainer()->get('serviceFactory')->create('TreoUpgrade');
    }
}
