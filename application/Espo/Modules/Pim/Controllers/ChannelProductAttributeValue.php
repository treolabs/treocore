<?php
declare(strict_types=1);

namespace Espo\Modules\Pim\Controllers;

use Espo\Core\Exceptions;
use Slim\Http\Request;

/**
 * ChannelProductAttributeValue controller
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class ChannelProductAttributeValue extends AbstractTechnicalController
{
    /**
     * Action update
     *
     * @param array   $params
     * @param array   $data
     * @param Request $request
     *
     * @return mixed
     * @throws Exceptions\BadRequest
     * @throws Exceptions\Forbidden
     */
    public function actionUpdate($params, $data, Request $request): bool
    {
        // check Request
        if (!$this->isValidUpdateAction($params, $data, $request)) {
            throw new Exceptions\BadRequest();
        }

        // check Acl
        if (!$this->getAcl()->check('Product', 'edit')) {
            throw new Exceptions\Forbidden();
        }

        // update Data
        return $this
            ->getService('ChannelProductAttributeValue')
            ->updateValue($params['id'], $data);
    }

    /**
     * Action create
     *
     * @param array   $params
     * @param array   $data
     * @param Request $request
     *
     * @return bool
     */
    public function actionCreate($params, $data, Request $request)
    {
        // check Request
        if (!$this->isValidCreateAction($data, $request)) {
            throw new BadRequest();
        }

        // check Acl
        if (!$this->getAcl()->check('Product', 'edit')) {
            throw new Forbidden();
        }

        // Crate value
        $result = $this
            ->getService('ChannelProductAttributeValue')
            ->createValue($data);

        return empty($result) ? false : true ;
    }

    /**
     * Delete value
     *
     * @param array   $params
     * @param array   $data
     * @param Request $request
     *
     * @return bool
     */
    public function actionDelete($params, $data, Request $request): bool
    {
        // check Request
        if (!$this->isValidDeleteAction($params, $request)) {
            throw new BadRequest();
        }

        // check Acl
        if (!$this->getAcl()->check('Product', 'edit')) {
            throw new Forbidden();
        }

        return $this
            ->getService('ChannelProductAttributeValue')
            ->deleteValue($params['id']);
    }
}
