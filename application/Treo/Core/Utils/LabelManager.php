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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
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
namespace Treo\Core\Utils;
use Espo\Core\Utils\File\Manager;
use Treo\Traits\ContainerTrait;
/**
 * Class LabelManager
 *
 * @author r.zablodskiy@treolabs.com
 */
class LabelManager extends \Espo\Core\Utils\LabelManager
{
    use ContainerTrait;
    /**
     * @inheritdoc
     */
    public function getScopeData($language, $scope)
    {
        $data = $this->getLanguage()->get($scope);
        if (empty($data)) {
            return (object) [];
        }
        $data = $this->getEntityLabels($data, $scope);
        $data = $this->getOptionsLabels($data, $scope);
        $data = $this->getScopeNames($data, $scope);
        foreach ($data as $key => $value) {
            if (empty($value)) {
                unset($data[$key]);
            }
        }
        $finalData = array();
        foreach ($data as $category => $item) {
            if (in_array($scope . '.' . $category, $this->ignoreList)) {
                continue;
            }
            foreach ($item as $key => $categoryItem) {
                if (is_array($categoryItem)) {
                    foreach ($categoryItem as $subKey => $subItem) {
                        $finalData[$category][$category .'[.]' . $key .'[.]' . $subKey] = $subItem;
                    }
                } else {
                    $finalData[$category][$category .'[.]' . $key] = $categoryItem;
                }
            }
        }
        return $finalData;
    }
    /**
     * @inheritdoc
     */
    protected function getFileManager(): Manager
    {
        return $this->getContainer()->get('fileManager');
    }
    /**
     * @inheritdoc
     */
    protected function getMetadata(): Metadata
    {
        return $this->getContainer()->get('metadata');
    }
    /**
     * Get language
     *
     * @return Language
     */
    protected function getLanguage(): Language
    {
        return $this->getContainer()->get('language');
    }
    /**
     * Get entity labels
     *
     * @param array $data
     * @param string $scope
     *
     * @return array
     *
     * @throws \Espo\Core\Exceptions\Error
     */
    protected function getEntityLabels(array $data, string $scope): array
    {
        if ($this->getMetadata()->get(['scopes', $scope, 'entity'])) {
            if (empty($data['fields'])) {
                $data['fields'] = array();
            }
            foreach ($this->getMetadata()->get(['entityDefs', $scope, 'fields']) as $field => $item) {
                if (!array_key_exists($field, $data['fields'])) {
                    $data['fields'][$field] = $this->getLanguage()->get('Global.fields.' . $field);
                    if (is_null($data['fields'][$field])) {
                        $data['fields'][$field] = '';
                    }
                }
            }
            if (empty($data['links'])) {
                $data['links'] = array();
            }
            foreach ($this->getMetadata()->get(['entityDefs', $scope, 'links']) as $link => $item) {
                if (!array_key_exists($link, $data['links'])) {
                    $data['links'][$link] = $this->getLanguage()->get('Global.links.' . $link);
                    if (is_null($data['links'][$link])) {
                        $data['links'][$link] = '';
                    }
                }
            }
            if (empty($data['labels'])) {
                $data['labels'] = array();
            }
            if (!array_key_exists('Create ' . $scope, $data['labels'])) {
                $data['labels']['Create ' . $scope] = '';
            }
        }
        return $data;
    }
    /**
     * Get entity fields options labels
     *
     * @param array $data
     * @param string $scope
     *
     * @return array
     */
    protected function getOptionsLabels(array $data, string $scope): array
    {
        foreach ($this->getMetadata()->get(['entityDefs', $scope, 'fields'], []) as $field => $item) {
            if (!$this->getMetadata()->get(['entityDefs', $scope, 'fields', $field, 'options'])) {
                continue;
            }
            $optionsData = array();
            $optionList = $this->getMetadata()->get(['entityDefs', $scope, 'fields', $field, 'options'], []);
            if (!array_key_exists('options', $data)) {
                $data['options'] = array();
            }
            if (!array_key_exists($field, $data['options'])) {
                $data['options'][$field] = array();
            }
            foreach ($optionList as $option) {
                if (empty($option)) {
                    continue;
                }
                $optionsData[$option] = $option;
                if (array_key_exists($option, $data['options'][$field])) {
                    if (!empty($data['options'][$field][$option])) {
                        $optionsData[$option] = $data['options'][$field][$option];
                    }
                }
            }
            $data['options'][$field] = $optionsData;
        }
        return $data;
    }
    /**
     * Get scope names
     *
     * @param array $data
     * @param string $scope
     *
     * @return array
     */
    protected function getScopeNames(array $data, string $scope): array
    {
        if ($scope === 'Global') {
            if (empty($data['scopeNames'])) {
                $data['scopeNames'] = array();
            }
            if (empty($data['scopeNamesPlural'])) {
                $data['scopeNamesPlural'] = array();
            }
            foreach ($this->getMetadata()->get(['scopes']) as $scopeKey => $item) {
                if (!empty($item['entity'])) {
                    if (empty($data['scopeNamesPlural'][$scopeKey])) {
                        $data['scopeNamesPlural'][$scopeKey] = '';
                    }
                }
                if (empty($data['scopeNames'][$scopeKey])) {
                    $data['scopeNames'][$scopeKey] = '';
                }
            }
        }
        return $data;
    }
}
