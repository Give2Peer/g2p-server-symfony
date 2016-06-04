<?php

namespace Give2Peer\Give2PeerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        // This actually works, as % variables are replaced ! \o/
        $defaultPicturesDirectory = "%kernel.root_dir%/../web/pictures";

        $treeBuilder = new TreeBuilder();
        $treeBuilder
            ->root('give2peer')
            ->children()
                ->arrayNode('pictures')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('directory')
                            ->example("%kernel.root_dir%/../web/pictures")
                            ->info("The directory where to store the uploaded pictures.")
                            ->defaultValue($defaultPicturesDirectory)
                        ->end()
                        ->integerNode('size')
                            ->example("240")
                            ->info("The size in pixels of the side of the square thumbnail.")
                            ->defaultValue(240)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('items')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('max_per_page')
                            ->example("64")
                            ->info("The maximum number of items per page of results.")
                            ->defaultValue(64)
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
