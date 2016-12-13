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
        $defaultPicturesDirectory = "%kernel.root_dir%/../web/picture";

        $treeBuilder = new TreeBuilder();
        $treeBuilder
            ->root('give2peer')
            ->children()
                ->arrayNode('items')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('max_per_page')
                            ->example("64")
                            ->info("The maximum number of items per page of results.")
                            ->defaultValue(64)
                        ->end()

                        ->arrayNode('pictures')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->scalarNode('directory')
                                    ->example("%kernel.root_dir%/../web/picture/item")
                                    ->info("The directory where to store the uploaded item pictures.")
                                    ->defaultValue($defaultPicturesDirectory . DIRECTORY_SEPARATOR . 'item')
                                ->end()
                                ->scalarNode('url_path')
                                    ->example("picture/item")
                                    ->info("The URL path where the server serves the item pictures.")
                                    ->defaultValue('picture/item')
                                ->end()
                                ->arrayNode('thumbnails')
                                    ->children()
                                        ->arrayNode('sizes')
                                            ->info("The sizes in pixels of the thumbnails to create.")
                                            ->prototype('array')
                                                ->children()
                                                    ->scalarNode('x')->end()
                                                    ->scalarNode('y')->end()
                                                ->end()
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
