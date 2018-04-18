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

namespace Espo\Modules\TreoCore\Core\Utils;

use Espo\Modules\TreoCore\Traits\ContainerTrait;
use Espo\Modules\TreoCore\Core\Utils\Metadata;

/**
 * ConsoleManager
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class ConsoleManager
{

    use ContainerTrait;

    /**
     * Path to console config
     *
     * @var string
     */
    protected $configPath = 'application/Espo/Modules/%s/Configs/Console.php';

    /**
     * Run console command
     *
     * @param array $argv
     */
    public function run(array $argv)
    {
        // load routes
        $routes = $this->loadRoutes();

        echo '<pre>';
        print_r($routes);
        die();
    }

    /**
     * Load routes
     *
     * @return array
     */
    protected function loadRoutes(): array
    {
        // prepare result
        $result = [];

        foreach ($this->getMetadata()->getModuleList() as $module) {
            $path = sprintf($this->configPath, $module);
            if (file_exists($path)) {
                $result = array_merge($result, include $path);
            }
        }

        return $result;
    }

    /**
     * Get metadata
     *
     * @return Metadata
     */
    protected function getMetadata(): Metadata
    {
        return $this->getContainer()->get('metadata');
    }
}
