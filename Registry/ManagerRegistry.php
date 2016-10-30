<?php

namespace GraphAware\Neo4jBundle\Registry;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Doctrine\Common\Persistence\AbstractManagerRegistry;

/**
 *
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 * @author Fabien Potencier <fabien@symfony.com>
 */
final class ManagerRegistry extends AbstractManagerRegistry implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    protected function getService($name)
    {
        return $this->container->get($name);
    }

    /**
     * {@inheritdoc}
     */
    protected function resetService($name)
    {
        $this->container->set($name, null);
    }

    /**
     * Resolves a registered namespace alias to the full namespace.
     *
     * This method looks for the alias in all registered entity managers.
     *
     * @param string $alias The alias
     *
     * @return string The full namespace
     *
     * @see Configuration::getEntityNamespace
     */
    public function getAliasNamespace($alias)
    {
        foreach (array_keys($this->getManagers()) as $name) {
            try {
                return $this->getManager($name)->getConfiguration()->getEntityNamespace($alias);
            } catch (\Exception $e) {
                // TODO
            }
        }

        //throw ORMException::unknownEntityNamespace($alias);
        throw new \Exception('Unknown entity namespace');
    }



}
