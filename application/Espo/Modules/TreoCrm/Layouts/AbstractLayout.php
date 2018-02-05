<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Layouts;

use Espo\Modules\TreoCrm\Core\Container;

/**
 * AbstractLayout class
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
abstract class AbstractLayout
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
     * @return AbstractLayout
     */
    public function setContainer(Container $container): AbstractLayout
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
