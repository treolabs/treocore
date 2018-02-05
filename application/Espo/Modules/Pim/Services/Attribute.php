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

        // get all attributes
        $attributes = $this->getEntityManager()->getRepository('Attribute')->find();

        if (count($attributes) > 0) {
            // prepare no family data
            $noFamilyData = [
                'id'   => 'no_family',
                'name' => 'No family',
                'rows' => []
            ];

            foreach ($attributes as $attribute) {
                $families = $attribute->get('productFamilyAttributes');
                if (count($families) > 0) {
                    foreach ($families as $family) {
                        $result[$family->get('productFamilyId')]['id']     = $family->get('productFamilyId');
                        $result[$family->get('productFamilyId')]['name']   = $family->get('productFamilyName');
                        $result[$family->get('productFamilyId')]['rows'][] = [
                            'attributeId' => $attribute->get('id'),
                            'name'        => $attribute->get('name'),
                            'type'        => $attribute->get('type')
                        ];
                    }
                } else {
                    $noFamilyData['rows'][] = [
                        'attributeId' => $attribute->get('id'),
                        'name'        => $attribute->get('name'),
                        'type'        => $attribute->get('type')
                    ];
                }
            }
            // prepare result
            $result = array_values($result);

            // push no family to the end of result
            if (!empty($noFamilyData['rows'])) {
                $result[] = $noFamilyData;
            }
        }

        return $result;
    }
}
