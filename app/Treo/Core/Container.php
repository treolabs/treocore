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

namespace Treo\Core;

use Espo\Core\AclManager;
use Espo\Entities\User;
use Espo\Core\Utils\Log;
use Espo\Core\Utils\Log\Monolog\Handler\RotatingFileHandler;
use Espo\Core\Utils\Log\Monolog\Handler\StreamHandler;
use Monolog\ErrorHandler;
use Treo\Core\EventManager\Manager as EventManager;
use Treo\Core\ModuleManager\Manager as ModuleManager;
use Treo\Core\Utils\Config;
use Treo\Core\Utils\File\Manager as FileManager;
use Treo\Core\Utils\Metadata;

/**
 * Class Container
 *
 * @author r.ratsun@treolabs.com
 */
class Container
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * Get class
     *
     * @param string $name
     *
     * @return mixed
     */
    public function get(string $name)
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
     * Set User
     *
     * @param User $user
     */
    public function setUser(User $user): Container
    {
        $this->set('user', $user);

        return $this;
    }

    /**
     * Set class
     */
    protected function set($name, $obj)
    {
        $this->data[$name] = $obj;
    }

    /**
     * Load
     *
     * @param string $name
     *
     * @throws \ReflectionException
     */
    protected function load(string $name): void
    {
        // prepare load method
        $loadMethod = 'load' . ucfirst($name);

        if (method_exists($this, $loadMethod)) {
            $obj = $this->$loadMethod();
            $this->data[$name] = $obj;
        } else {
            try {
                $className = $this->get('metadata')->get('app.loaders.' . ucfirst($name));
            } catch (\Exception $e) {
            }

            if (!isset($className) || !class_exists($className)) {
                $className = '\Treo\Core\Loaders\\' . ucfirst($name);
            }

            if (class_exists($className)) {
                $loadClass = new $className($this);
                $this->data[$name] = $loadClass->load();
            }
        }
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
     * Load container
     *
     * @return Container
     */
    protected function loadContainer(): Container
    {
        return $this;
    }

    /**
     * Load internal ACL manager
     *
     * @return mixed
     */
    protected function loadInternalAclManager()
    {
        // get class name
        $className = $this
            ->get('metadata')
            ->get('app.serviceContainer.classNames.acl', AclManager::class);

        return new $className($this->get('container'));
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
        return new Metadata(
            $this->get('fileManager'),
            $this->get('moduleManager'),
            $this->get('eventManager'),
            $this->get('config')->get('useCache')
        );
    }

    /**
     * Load Log
     *
     * @return Log
     * @throws \Exception
     */
    protected function loadLog(): Log
    {
        $config = $this->get('config');

        $path = $config->get('logger.path', 'data/logs/espo.log');
        $rotation = $config->get('logger.rotation', true);

        $log = new Log('Espo');
        $levelCode = $log->getLevelCode($config->get('logger.level', 'WARNING'));

        if ($rotation) {
            $maxFileNumber = $config->get('logger.maxFileNumber', 30);
            $handler = new RotatingFileHandler($path, $maxFileNumber, $levelCode);
        } else {
            $handler = new StreamHandler($path, $levelCode);
        }
        $log->pushHandler($handler);

        $errorHandler = new ErrorHandler($log);
        $errorHandler->registerExceptionHandler(null, false);
        $errorHandler->registerErrorHandler(array(), false);

        return $log;
    }

    /**
     * Load file manager
     *
     * @return FileManager
     */
    protected function loadFileManager(): FileManager
    {
        return new FileManager($this->get('config'));
    }

    /**
     * Load module manager
     *
     * @return ModuleManager
     */
    protected function loadModuleManager(): ModuleManager
    {
        return new ModuleManager($this);
    }

    /**
     * Load EventManager
     *
     * @return EventManager
     */
    protected function loadEventManager(): EventManager
    {
        $eventManager = new EventManager($this);
        $eventManager->loadListeners();

        return $eventManager;
    }
}
