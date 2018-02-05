<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Listeners;

use Espo\Modules\TreoCrm\Core\Container;

/**
 * AbstractListener class
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
abstract class AbstractListener
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
     * @return AbstractListener
     */
    public function setContainer(Container $container): AbstractListener
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
