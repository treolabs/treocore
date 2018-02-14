<?php
declare(strict_types = 1);

namespace Espo\Modules\Pim\Loaders;

use Espo\Core\Loaders\EntityManager as EspoEntityManagerLoader;
use Espo\Modules\Pim\Core\ORM\EntityManager;

/**
 * EntityManager Loader
 *
 * @author r.ratsun <r.ratsun@zinitsolutions.com>
 */
class EntityManagerLoader extends EspoEntityManagerLoader
{

    /**
     * Load
     *
     * @return EntityManager
     */
    public function load(): EntityManager
    {
        // get config
        $config = $this->getContainer()->get('config');

        // prepare params
        $params = [
            'host'                       => $config->get('database.host'),
            'port'                       => $config->get('database.port'),
            'dbname'                     => $config->get('database.dbname'),
            'user'                       => $config->get('database.user'),
            'charset'                    => $config->get('database.charset', 'utf8'),
            'password'                   => $config->get('database.password'),
            'metadata'                   => $this->getContainer()->get('ormMetadata')->getData(),
            'repositoryFactoryClassName' => '\\Espo\\Core\\ORM\\RepositoryFactory',
            'driver'                     => $config->get('database.driver'),
            'platform'                   => $config->get('database.platform'),
            'sslCA'                      => $config->get('database.sslCA'),
            'sslCert'                    => $config->get('database.sslCert'),
            'sslKey'                     => $config->get('database.sslKey'),
            'sslCAPath'                  => $config->get('database.sslCAPath'),
            'sslCipher'                  => $config->get('database.sslCipher')
        ];

        $entityManager = new EntityManager($params);
        $entityManager->setEspoMetadata($this->getContainer()->get('metadata'));
        $entityManager->setHookManager($this->getContainer()->get('hookManager'));
        $entityManager->setContainer($this->getContainer());

        return $entityManager;
    }
}
