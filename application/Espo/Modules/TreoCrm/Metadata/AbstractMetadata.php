<?php
declare(strict_types = 1);

namespace Espo\Modules\TreoCrm\Metadata;

use Espo\Modules\TreoCrm\Core\Container;

/**
 * AbstractMetadata class
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
abstract class AbstractMetadata
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * Modify
     *
     * @param array $data
     *
     * @return array
     */
    abstract public function modify(array $data): array;

    /**
     * Set container
     *
     * @param Container $container
     *
     * @return AbstractMetadata
     */
    public function setContainer(Container $container): AbstractMetadata
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
