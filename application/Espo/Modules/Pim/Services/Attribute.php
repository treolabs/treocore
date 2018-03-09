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

namespace Espo\Modules\Pim\Services;

/**
 * Attribute service
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Attribute extends AbstractService
{

    /**
     * Get filters
     *
     * @return array
     */
    public function getFiltersData(): array
    {
        // prepare result
        $result = [];

        // get multilang fields
        $multilangFields = $this->getMultilangFields();

        // get all attributes
        $attributes = $this->getEntityManager()->getRepository('Attribute')->find();

        if (count($attributes) > 0) {
            // prepare no family data
            $noFamilyData = [
                'id'   => 'all',
                'name' => $this->getTranslate('All', 'filterLabels', 'Attribute'),
                'rows' => []
            ];

            foreach ($attributes as $attribute) {
                if (!in_array($attribute->get('type'), $multilangFields)) {
                    // get families
                    $families = $attribute->get('productFamilyAttributes');

                    // push items to families
                    if (count($families) > 0) {
                        foreach ($families as $family) {
                            // prepare productFamily
                            $productFamily = $family->get('productFamily');

                            if (!empty($productFamily) && !$productFamily->get('deleted')) {
                                $result[$family->get('productFamilyId')]['id']     = $family->get('productFamilyId');
                                $result[$family->get('productFamilyId')]['name']   = $family->get('productFamilyName');
                                $result[$family->get('productFamilyId')]['rows'][] = [
                                    'attributeId' => $attribute->get('id'),
                                    'name'        => $attribute->get('name'),
                                    'type'        => $attribute->get('type')
                                ];
                            }
                        }
                    }

                    // push to all
                    $noFamilyData['rows'][$attribute->get('id')] = [
                        'attributeId' => $attribute->get('id'),
                        'name'        => $attribute->get('name'),
                        'type'        => $attribute->get('type')
                    ];
                }
            }
            $noFamilyData['rows'] = array_values($noFamilyData['rows']);

            // prepare result
            $result = array_values($result);

            // push no family to the end of result
            if (!empty($noFamilyData['rows'])) {
                $result[] = $noFamilyData;
            }
        }

        return $result;
    }

    /**
     * Get multilang fields
     *
     * @return array
     */
    protected function getMultilangFields(): array
    {
        // get config
        $config = $this->getConfig()->get('modules');

        return (!empty($config['multilangFields'])) ? array_keys($config['multilangFields']) : [];
    }
}
