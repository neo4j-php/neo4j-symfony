<?php

namespace GraphAware\Neo4jBundle\Collector\Twig;

use GraphAware\Neo4j\Client\Formatter\Type\Node;

/**
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class Neo4jResultExtension extends \Twig_Extension
{
    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter('neo4jResult', [$this, 'getType']),
        ];
    }

    /**
     * @param string $message http message
     */
    public function getType($object)
    {
        return $this->doGetType($object, true);

    }

    public function getName()
    {
        return 'neo4j.result';
    }

    /**
     * @param $object
     * @param $recursive
     *
     * @return string
     */
    private function doGetType($object, $recursive):string
    {
        if ($object instanceof Node) {
            return sprintf('%s: %s', $object->identity(), implode(', ', $object->labels()));
        } elseif (is_array($object) && $recursive) {
            if (empty($object)) {
                return 'Empty array';
            }
            $ret = [];
            foreach ($object as $o) {
                $ret[] = $this->doGetType($o, false);
            }

            return sprintf('[%s]', implode(',', $ret));
        }

        return is_object($object) ? get_class($object) : gettype($object);
    }
}
