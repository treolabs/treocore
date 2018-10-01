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

namespace Treo\Core\Utils\File;

use Espo\Core\Utils;

/**
 * Class Unifier
 *
 * @author r.ratsun r.ratsun@zinitsolutions.com
 */
class Unifier extends \Espo\Core\Utils\File\Unifier
{
    /**
     * @inheritdoc
     */
    public function unify($name, $paths, $recursively = false)
    {
        $content = $this->unifySingle($paths['corePath'], $name, $recursively);

        if (!empty($paths['treoCorePath'])) {
            $coreContent = $content;
            $content = $this->unifySingle($paths['treoCorePath'], $name, $recursively);

            if ($this->useObjects) {
                $content = Utils\DataUtil::merge($content, $coreContent);
            } else {
                $content = Utils\Util::merge($content, $coreContent);
            }
        }

        if (!empty($paths['modulePath'])) {
            foreach ($this->getMetadata()->getModuleList() as $moduleName) {
                $curPath = str_replace('{*}', $moduleName, $paths['modulePath']);
                $curContent = $this->unifySingle($curPath, $name, $recursively, $moduleName);
                if ($this->useObjects) {
                    $content = Utils\DataUtil::merge($content, $curContent);
                } else {
                    $content = Utils\Util::merge($content, $curContent);
                }
            }
        }

        if (!empty($paths['customPath'])) {
            $customContent = $this->unifySingle($paths['customPath'], $name, $recursively);
            if ($this->useObjects) {
                $content = Utils\DataUtil::merge($content, $customContent);
            } else {
                $content = Utils\Util::merge($content, $customContent);
            }
        }

        return $content;
    }
}
