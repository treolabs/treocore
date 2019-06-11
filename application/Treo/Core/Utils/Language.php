<?php
/**
 * ColoredFields
 * Free Extension
 * Copyright (c) TreoLabs GmbH
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Treo\Core\Utils;

use Espo\Core\Utils\Language as Base;
use Espo\Core\Utils\Util;
use Espo\Core\Exceptions\Error;

/**
 * Class Language
 *
 * @author r.ratsun <r.ratsun@treolabs.com>
 */
class Language extends Base
{
    /**
     * @inheritdoc
     */
    protected function init($reload = false)
    {
        if ($reload || !file_exists($this->getLangCacheFile()) || !$this->useCache) {
            // load espo
            $fullData = $this->unify('application/Espo/Resources/i18n');

            // load treo
            $fullData = Util::merge($fullData, $this->unify('application/Treo/Resources/i18n'));

            // load modules
            foreach ($this->getMetadata()->getModules() as $module) {
                $fullData = Util::merge($fullData, $this->unify($module->getAppPath() . 'Resources/i18n'));
            }

            // load custom
            if (!$this->noCustom) {
                $fullData = Util::merge($fullData, $this->unify('custom/Espo/Custom/Resources/i18n'));
            }

            $result = true;
            foreach ($fullData as $i18nName => $i18nData) {
                if ($i18nName != $this->defaultLanguage) {
                    $i18nData = Util::merge($fullData[$this->defaultLanguage], $i18nData);
                }

                $this->data[$i18nName] = $i18nData;

                if ($this->useCache) {
                    $i18nCacheFile = str_replace('{*}', $i18nName, $this->cacheFile);
                    $result &= $this->getFileManager()->putPhpContents($i18nCacheFile, $i18nData);
                }
            }

            if ($result == false) {
                throw new Error('Language::init() - Cannot save data to a cache');
            }
        }

        $currentLanguage = $this->getLanguage();
        if (empty($this->data[$currentLanguage])) {
            $this->data[$currentLanguage] = $this->getFileManager()->getPhpContents($this->getLangCacheFile());
        }
    }

    /**
     * @param string $path
     *
     * @return array
     */
    private function unify(string $path): array
    {
        return $this->getUnifier()->unify('i18n', $path, true);
    }
}
