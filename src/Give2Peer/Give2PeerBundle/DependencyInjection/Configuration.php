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
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('give2peer');

        // This requires DIC access...
        // $defaultPicturesDirectory = $this->get('kernel')->getRootDir() . '/../web/pictures';
        // ... but this actually works ! % variables are replaced ! \o/
        $defaultPicturesDirectory = "%kernel.root_dir%/../web/pictures";

        $rootNode
            ->children()
                ->arrayNode('pictures')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('directory')
                            ->example("%kernel.root_dir%/../web/pictures")
                            ->info("The directory where to store the uploaded pictures.")
                            ->defaultValue($defaultPicturesDirectory)
                        ->end()
                    ->end()
                ->end() // pictures
            ->end()
        ;



        return $treeBuilder;
    }
}
