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

namespace Treo\Listeners;

use Treo\Core\EventManager\Event;
use Treo\Services\Composer;

/**
 * Installer listener
 *
 * @author r.ratsun@treolabs.com
 */
class Installer extends AbstractListener
{

    /**
     * @param Event $event
     */
    public function afterInstallSystem(Event $event)
    {
        // generate Treo ID
        $this->generateTreoId();

        // refresh
        $this->refreshStore();

        // create files in data dir
        $this->createDataFiles();
    }

    /**
     * Generate Treo ID
     */
    protected function generateTreoId(): void
    {
        // generate id
        $treoId = \Treo\Services\Installer::generateTreoId();

        // set to config
        $this->getConfig()->set('treoId', $treoId);
        $this->getConfig()->save();

        $data = json_decode(file_get_contents(Composer::$composer), true);
        $data['repositories'][] = [
            'type' => 'composer',
            'url'  => 'https://packagist.treopim.com/packages.json?id=' . $treoId
        ];

        // create repositories file
        file_put_contents(Composer::$composer, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Refresh TreoStore
     */
    protected function refreshStore(): void
    {
        $this->getContainer()->get('serviceFactory')->create('TreoStore')->refresh();
    }

    /**
     * Create needed files in data directory
     */
    protected function createDataFiles(): void
    {
        file_put_contents('data/notReadCount.json', '{}');
        file_put_contents('data/popupNotifications.json', '{}');
    }
}
