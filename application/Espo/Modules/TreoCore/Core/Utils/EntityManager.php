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

declare(strict_types = 1);

namespace Espo\Modules\TreoCore\Core\Utils;

use Espo\Core\Utils\EntityManager as EspoEntityManager;
use Espo\Modules\TreoCore\Core\Container;
use Espo\Core\Utils\Metadata\Helper as MetadataHelper;
use Espo\Core\Exceptions;

/**
 * Class of EntityManager
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class EntityManager extends EspoEntityManager
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * @var object
     */
    protected $metadata;

    /**
     * @var object
     */
    protected $language;

    /**
     * @var object
     */
    protected $fileManager;

    /**
     * @var object
     */
    protected $config;

    /**
     * @var object
     */
    protected $metadataHelper;

    /**
     * @var array
     */
    protected $reservedWordList = [
        '__halt_compiler',
        'abstract',
        'and',
        'array',
        'as',
        'break',
        'callable',
        'case',
        'catch',
        'class',
        'clone',
        'const',
        'continue',
        'declare',
        'default',
        'die',
        'do',
        'echo',
        'else',
        'elseif',
        'empty',
        'enddeclare',
        'endfor',
        'endforeach',
        'endif',
        'endswitch',
        'endwhile',
        'eval',
        'exit',
        'extends',
        'final',
        'for',
        'foreach',
        'function',
        'global',
        'goto',
        'if',
        'implements',
        'include',
        'include_once',
        'instanceof',
        'insteadof',
        'interface',
        'isset',
        'list',
        'namespace',
        'new',
        'or',
        'print',
        'private',
        'protected',
        'public',
        'require',
        'require_once',
        'return',
        'static',
        'switch',
        'throw',
        'trait',
        'try',
        'unset',
        'use',
        'var',
        'while',
        'xor',
        'common'
    ];

    /**
     *
     * Construct
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        // prepare properties
        $this->container      = $container;
        $this->metadata       = $this->container->get('metadata');
        $this->language       = $this->container->get('language');
        $this->fileManager    = $this->container->get('fileManager');
        $this->config         = $this->container->get('config');
        $this->metadataHelper = new MetadataHelper($this->metadata);
    }

    /**
     * Create action
     *
     * @param string $name
     * @param string $type
     * @param array $params
     *
     * @return boolean
     * @throws Exceptions\Conflict
     * @throws Exceptions\Error
     */
    public function create($name, $type, $params = [])
    {
        // triggered event
        $this->triggeredEvent('beforeUpdate', ['name' => trim(ucfirst($name)), 'data' => $params]);

        // get result
        $result = parent::create($name, $type, $params);

        return $result;
    }

    /**
     * Update action
     *
     * @param string $name
     * @param array $data
     *
     * @return boolean
     * @throws Exceptions\Error
     */
    public function update($name, $data)
    {
        // triggered event
        $this->triggeredEvent('beforeUpdate', ['name' => $name, 'data' => $data]);

        // get result
        $result = parent::update($name, $data);

        // rebuild DB
        $this->container->get('dataManager')->rebuild();

        // triggered event
        $this->triggeredEvent('afterUpdate', ['name' => $name, 'data' => $data]);

        return $result;
    }

    /**
     * Triggered event
     *
     * @param string $action
     * @param array $data
     *
     * @return void
     */
    protected function triggeredEvent(string $action, array $data = [])
    {
        $this->container->get('eventManager')->triggered('EntityManager', $action, $data);
    }

    /**
     * @return object
     */
    protected function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @return object
     */
    protected function getEntityManager()
    {
        return $this->container->get('entityManager');
    }

    /**
     * @return object
     */
    protected function getLanguage()
    {
        return $this->language;
    }


    /**
     * @return object
     */
    protected function getBaseLanguage()
    {
        return $this->container->get('baseLanguage');
    }

    /**
     * @return object
     */
    protected function getFileManager()
    {
        return $this->fileManager;
    }

    /**
     * @return object
     */
    protected function getConfig()
    {
        return $this->config;
    }

    /**
     * @return object
     */
    protected function getMetadataHelper()
    {
        return $this->metadataHelper;
    }

    /**
     * @return object
     */
    protected function getServiceFactory()
    {
        return $this->container->get('serviceFactory');
    }
}
