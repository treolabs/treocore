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

namespace Espo\Modules\TreoCore\Core;

use Espo\Core\Container as EspoContainer;
use Espo\Modules\TreoCore\Core\Utils\Config;
use Espo\Core\Utils\File\Manager as FileManager;

/**
 * Container class
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Container extends EspoContainer
{

    /**
     * Reload object
     *
     * @param string $name
     *
     * @return Container
     */
    public function reload(string $name): Container
    {
        // unset
        if (isset($this->data[$name])) {
            unset($this->data[$name]);
        }

        // load
        $this->load($name);

        return $this;
    }

    /**
     * Load metadata
     *
     * @return Utils\Metadata
     */
    protected function loadMetadata(): Utils\Metadata
    {
        // create metadata
        $metadata = new Utils\Metadata($this->get('fileManager'), $this->get('config')->get('useCache'));

        // set container
        $metadata->setContainer($this);

        return $metadata;
    }

    /**
     * Load config
     *
     * @return Config
     */
    protected function loadConfig()
    {
        return new Config(new FileManager());
    }
}