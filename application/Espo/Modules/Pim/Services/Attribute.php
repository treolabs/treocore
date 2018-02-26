<?php
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
