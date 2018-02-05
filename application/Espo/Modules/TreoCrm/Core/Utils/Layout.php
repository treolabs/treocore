<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Core\Utils;

use Espo\Core\Utils\Layout as EspoLayout;
use Espo\Core\Utils\File\Manager as FileManager;
use Espo\Core\Utils\Metadata;
use Espo\Core\Utils\Util;
use Espo\Core\Utils\Json;
use Espo\Entities\User;
use Espo\Modules\TreoCrm\Layouts\AbstractLayout;
use Espo\Modules\TreoCrm\Core\Container;

/**
 * Class of Layout
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class Layout extends EspoLayout
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * Construct
     */
    public function __construct()
    {
        // blocked parent construct
    }

    /**
     * Get Layout context
     *
     * @param string $scope
     * @param string $name
     *
     * @return json
     */
    public function get($scope, $name)
    {
        // prepare params
        $data  = [];
        $scope = $this->sanitizeInput($scope);
        $name  = $this->sanitizeInput($name);

        // cache
        if (isset($this->changedData[$scope][$name])) {
            return Json::encode($this->changedData[$scope][$name]);
        }

        // from custom data
        $fileFullPath = Util::concatPath($this->getLayoutPath($scope, true), $name.'.json');
        if (file_exists($fileFullPath)) {
            $fileData = $this->getFileManager()->getContents($fileFullPath);

            // prepare data
            $data = array_merge_recursive($data, Json::decode($fileData, true));
        }

        // from modules data
        if (empty($data)) {
            foreach ($this->getMetadata()->getModuleList() as $module) {
                // prepare file path
                $filePath     = Util::concatPath(str_replace('{*}', $module, $this->paths['modulePath']), $scope);
                $fileFullPath = Util::concatPath($filePath, $name.'.json');
                if (file_exists($fileFullPath)) {
                    // get file data
                    $fileData = $this->getFileManager()->getContents($fileFullPath);

                    // prepare data
                    $data = array_merge_recursive($data, Json::decode($fileData, true));
                }
            }
        }

        // from core data
        if (empty($data)) {
            // prepare file path
            $filePath     = Util::concatPath($this->paths['corePath'], $scope);
            $fileFullPath = Util::concatPath($filePath, $name.'.json');
            if (file_exists($fileFullPath)) {
                // get file data
                $fileData = $this->getFileManager()->getContents($fileFullPath);

                // prepare data
                $data = Json::decode($fileData, true);
            }
        }

        // default
        if (empty($data)) {
            // prepare file path
            $fileFullPath = Util::concatPath(Util::concatPath($this->params['defaultsPath'], 'layouts'), $name.'.json');

            if (file_exists($fileFullPath)) {
                // get file data
                $fileData = $this->getFileManager()->getContents($fileFullPath);

                // prepare data
                $data = Json::decode($fileData, true);
            }
        }

        // modify data
        foreach ($this->getMetadata()->getModuleList() as $module) {
            $className = sprintf('Espo\Modules\%s\Layouts\%s', $module, $scope);
            if (class_exists($className)) {
                // create class
                $layout = new $className();

                // set container
                if ($layout instanceof AbstractLayout) {
                    $layout->setContainer($this->getContainer());
                }

                // call method
                $method = 'layout'.ucfirst($name);
                if (method_exists($layout, $method)) {
                    $data = $layout->{$method}($data);
                }
            }
        }

        return Json::encode($data);
    }

    /**
     * Set container
     *
     * @param Container $container
     *
     * @return Layout
     */
    public function setContainer(Container $container): Layout
    {
        $this->container = $container;

        return $this;
    }

    /**
     * Get container
     *
     * @return Container
     */
    protected function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Get file manager
     *
     * @return FileManager
     */
    protected function getFileManager(): FileManager
    {
        return $this->getContainer()->get('fileManager');
    }

    /**
     * Get metadata
     *
     * @return Metadata
     */
    protected function getMetadata(): Metadata
    {
        return $this->getContainer()->get('metadata');
    }

    /**
     * Get user
     *
     * @return User
     */
    protected function getUser(): User
    {
        return $this->getContainer()->get('user');
    }
}
