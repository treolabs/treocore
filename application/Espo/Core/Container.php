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

namespace Espo\Core;

use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Modules\TreoCore\Core\Utils\Config;
use Espo\Modules\TreoCore\Core\Utils\Metadata;

/**
 * Class Container
 *
 * @author r.ratsun@zinitsolutions.com
 * @todo   treoinject
 */
class Container
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        // blocked construct
    }

    /**
     * Get object
     *
     * @param string $name
     *
     * @return mixed
     */
    public function get($name)
    {
        if (empty($this->data[$name])) {
            $this->load($name);
        }
        if (isset($this->data[$name])) {
            return $this->data[$name];
        }
        return null;
    }

    /**
     * Set user
     *
     * @param \Espo\Entities\User $user
     *
     * @return void
     */
    public function setUser(\Espo\Entities\User $user)
    {
        $this->set('user', $user);
    }

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
     * Push object
     *
     * @param string $name
     * @param mixed  $obj
     *
     * return void
     */
    protected function set($name, $obj)
    {
        $this->data[$name] = $obj;
    }

    /**
     * Load object
     *
     * @param string $name
     *
     * @return mixed
     */
    protected function load($name)
    {
        $loadMethod = 'load' . ucfirst($name);
        if (method_exists($this, $loadMethod)) {
            $this->set($name, $this->$loadMethod());
        } else {

            try {
                $className = $this->get('metadata')->get('app.loaders.' . ucfirst($name));
            } catch (\Exception $e) {

            }

            if (!isset($className) || !class_exists($className)) {
                $className = '\Espo\Custom\Core\Loaders\\' . ucfirst($name);
                if (!class_exists($className)) {
                    $className = '\Espo\Core\Loaders\\' . ucfirst($name);
                }
            }

            if (class_exists($className)) {
                $this->set($name, (new $className($this))->load());
            }
        }

        return null;
    }

    /**
     * Load file manager
     *
     * @return \Espo\Core\Utils\File\Manager
     */
    protected function loadFileManager()
    {
        return new \Espo\Core\Utils\File\Manager($this->get('config'));
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

    /**
     * Load metadata
     *
     * @return Metadata
     */
    protected function loadMetadata(): Metadata
    {
        // create metadata
        $metadata = new Metadata($this->get('fileManager'), $this->get('config')->get('useCache'));

        // set container
        $metadata->setContainer($this);

        return $metadata;
    }

    /**
     * Load Log
     *
     * @return \Espo\Core\Utils\Log
     */
    protected function loadLog()
    {
        $config = $this->get('config');

        $path = $config->get('logger.path', 'data/logs/espo.log');
        $rotation = $config->get('logger.rotation', true);

        $log = new \Espo\Core\Utils\Log('Espo');
        $levelCode = $log->getLevelCode($config->get('logger.level', 'WARNING'));

        if ($rotation) {
            $maxFileNumber = $config->get('logger.maxFileNumber', 30);

            $handler = new \Espo\Core\Utils\Log\Monolog\Handler\RotatingFileHandler($path, $maxFileNumber, $levelCode);
        } else {
            $handler = new \Espo\Core\Utils\Log\Monolog\Handler\StreamHandler($path, $levelCode);
        }
        $log->pushHandler($handler);

        $errorHandler = new \Monolog\ErrorHandler($log);
        $errorHandler->registerExceptionHandler(null, false);
        $errorHandler->registerErrorHandler(array(), false);

        return $log;
    }
}
