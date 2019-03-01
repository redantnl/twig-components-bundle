<?php

namespace RedAnt\TwigComponentsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->root('twig_components');

        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('global_variable')->defaultValue('component')->cannotBeEmpty()->end()
            ->end();

        return $treeBuilder;
    }
}
