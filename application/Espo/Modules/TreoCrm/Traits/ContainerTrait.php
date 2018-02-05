<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Traits;

use Espo\Modules\TreoCrm\Core\Container;

/**
 * Class of ContainerTrait
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
trait ContainerTrait
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * Set container
     *
     * @param Container $container
     *
     * @return $this
     */
    public function setContainer(Container $container)
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
}
