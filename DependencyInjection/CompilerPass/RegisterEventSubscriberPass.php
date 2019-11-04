<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\DependencyInjection\CompilerPass;

use Neo4j\Neo4jBundle\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RegisterEventSubscriberPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('neo4j.event_manager')) {
            return;
        }

        $definition = $container->findDefinition('neo4j.event_manager');

        foreach ($container->findTaggedServiceIds('neo4j.event_subscriber') as $id => $tags) {
            foreach ($tags as $_) {
                $definition->addMethodCall('addEventSubscriber', [new Reference($id)]);
            }
        }

        foreach ($container->findTaggedServiceIds('neo4j.event_listener') as $id => $tags) {
            foreach ($tags as $tag) {
                if (!isset($tag['event'])) {
                    throw new InvalidArgumentException('The tag "neo4j.event_listener" must have a "event" attribute!');
                }

                $methodName = $tag['method'] ?? null;

                $definition->addMethodCall('addEventListener', [
                    $tag['event'],
                    $methodName ? [new Reference($id), $methodName] : new Reference($id),
                ]);
            }
        }
    }
}
