<?php

namespace Espo\Modules\Pim\Controllers;

use Espo\Core\Controllers\Base;
use Espo\Core\Exceptions;
use Slim\Http\Request;

/**
 * ChannelProductAttributeValue controller
 *
 * @author y.haiduchyk <y.haiduchyk@zinitsolutions.com>
 */
class AssociationProduct extends AbstractTechnicalController
{

    /**
     * Action Get
     *
     * @param array   $params
     * @param array   $data
     * @param Request $request
     *
     * @return mixed
     * @throws Exceptions\BadRequest
     * @throws Exceptions\Forbidden
     * @throws Exceptions\NotFound
     */
    public function actionRead($params, $data, Request $request)
    {
        if (!$this->isValidReadAction($params, $request)) {
            throw new Exceptions\BadRequest();
        }

        // check Acl
        if (!$this->getAcl()->check('Product', 'read') || !$this->getAcl()->check('Association', 'read')) {
            throw new Exceptions\Forbidden();
        }

        // get data
        $result = $this->getService('AssociationProduct')->getAssociationProduct($params['id']);

        if (empty($result)) {
            throw new Exceptions\NotFound();
        }

        return $result;
    }

    /**
     * Action create
     *
     * @param array   $params
     * @param array   $data
     * @param Request $request
     *
     * @return bool
     * @throws Exceptions\BadRequest
     */
    public function actionCreate($params, $data, Request $request): bool
    {
        // check Request
        if (!$this->isValidCreateAction($data, $request)) {
            throw new Exceptions\BadRequest();
        }

        // check Acl
        if (!$this->getAcl()->check('Product', 'edit')) {
            throw new Forbidden();
        }

        // Crate value
        return $this->getService('AssociationProduct')->createAssociationProduct($data);
    }

    /**
     * @param array   $params
     * @param array   $data
     * @param Request $request
     *
     * @return bool
     * @throws Exceptions\BadRequest
     * @throws Exceptions\Forbidden
     */
    public function actionUpdate($params, $data, Request $request): bool
    {
        // check request
        if (!$this->isValidUpdateAction($params, $data, $request)) {
            throw new Exceptions\BadRequest();
        }

        // check Acl
        if (!$this->getAcl()->check('Product', 'edit')) {
            throw new Exceptions\Forbidden();
        }

        // update Data
        return $this
            ->getService('AssociationProduct')
            ->updateAssociationProduct($params['id'], $data);
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
        // check action
        if (!$this->isValidDeleteAction($params, $request)) {
            throw new BadRequest();
        }

        // check Acl
        if (!$this->getAcl()->check('Product', 'edit')) {
            throw new Forbidden();
        }

        return $this
            ->getService('AssociationProduct')
            ->deleteAssociationProduct($params['id']);
    }
}
