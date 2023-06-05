<?php

declare(strict_types=1);

namespace Neo4j\Neo4jBundle\Collector\Twig;

use Laudis\Neo4j\Types\Node;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Neo4jResultExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     *
     * @return array<TwigFilter>
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('neo4jResult', [$this, 'getType']),
        ];
    }

    /**
     * @param mixed $object
     */
    public function getType($object): string
    {
        return $this->doGetType($object, true);
    }

    public function getName(): string
    {
        return 'neo4j.result';
    }

    /**
     * @param mixed $object
     */
    private function doGetType($object, bool $recursive): string
    {
        if ($object instanceof Node) {
            return sprintf('%s: %s', $object->getId(), $object->getLabels()->join(', '));
        }

        if (is_array($object) && $recursive) {
            if (empty($object)) {
                return 'Empty array';
            }
            $ret = [];
            foreach ($object as $o) {
                $ret[] = $this->doGetType($o, false);
            }

            return sprintf('[%s]', implode(', ', $ret));
        }

        return get_debug_type($object);
    }
}
