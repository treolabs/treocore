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

use Espo\Core\HookManager as Base;
use Treo\Core\Utils\Util;

/**
 * Class HookManager
 *
 * @author r.ratsun <r.ratsun@gmail.com>
 */
class HookManager extends Base
{

    protected $moduleId;

    /**
     * Load hook data
     *
     * @param string $hookDir
     * @param array  $hookData
     *
     * @return array
     */
    public function getModuleHookData(string $hookDir, string $id, array $hookData = [])
    {
        if (file_exists($hookDir)) {
            $fileList = $this->getFileManager()->getFileList($hookDir, 1, '\.php$', true);

            foreach ($fileList as $scopeName => $hookFiles) {
                $normalizedScopeName = Util::normilizeScopeName($scopeName);
                foreach ($hookFiles as $hookFile) {
                    // prepare class name
                    $className = "\\$id\\Hooks\\$scopeName\\" . str_replace(".php", "", $hookFile);

                    if (!class_exists($className)) {
                        continue 1;
                    }

                    // get hook methods
                    $hookMethods = array_diff(get_class_methods($className), $this->ignoredMethodList);

                    foreach ($hookMethods as $hookType) {
                        if (isset($hookData[$normalizedScopeName][$hookType])) {
                            $entityHookData = $hookData[$normalizedScopeName][$hookType];
                        } else {
                            $entityHookData = [];
                        }
                        if (!$this->hookExists($className, $entityHookData)) {
                            $hookData[$normalizedScopeName][$hookType][] = array(
                                'className' => $className,
                                'order'     => $className::$order
                            );
                        }
                    }
                }
            }
        }

        return $hookData;
    }


    /**
     * @inheritdoc
     */
    protected function loadHooks()
    {
        if ($this->getConfig()->get('useCache') && file_exists($this->cacheFile)) {
            $this->data = $this->getFileManager()->getPhpContents($this->cacheFile);
            return;
        }

        $metadata = $this->container->get('metadata');

        $data = $this->getHookData($this->paths['customPath']);

        foreach ($metadata->getModules() as $module) {
            $module->loadHooks($data);
        }

        $data = $this->getHookData(CORE_PATH . '/Treo/Hooks', $data);

        $data = $this->getHookData(CORE_PATH . '/Espo/Hooks', $data);

        $this->data = $this->sortHooks($data);

        if ($this->getConfig()->get('useCache')) {
            $this->getFileManager()->putPhpContents($this->cacheFile, $this->data);
        }
    }
}
