<?php

namespace Give2Peer\Give2PeerBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages the Give2Peer bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class Give2PeerExtension extends Extension
{
    /**
     * Loads a our configuration.
     * Is this cached somehow, and is not run every time.
     * Not even in the test env, which is kind of disturbing actually.
     * The test env cache for this seems to live for about a minute.
     * Just add a print() statement and run the test-suite you'll see !
     *
     * @param array            $configs   An array of configuration values
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws \InvalidArgumentException When provided tag is not defined in this extension
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // fixme
//        array_walk_recursive(, )
        $container->setParameter(
            'give2peer.pictures.directory',
            $config['pictures']['directory']
        );

        //// THE SERVICES

        $loader = new Loader\YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.yml');
    }

    /**
     * Returns the recommended alias to use in XML.
     * This alias is also the mandatory prefix to use when using YAML.
     *
     * The default alias generation with `Container::underscore()` generates
     * `give2_peer` and it's not very pretty.
     *
     * @return string The alias
     */
    public function getAlias()
    {
        return 'give2peer';
    }

    /**
     * Set all leaf values of the $config array as parameters in the $container.
     *
     * @param array $config
     * @param ContainerBuilder $container
     */
    protected function setParameters(array $config, ContainerBuilder $container)
    {
        // fixme
    }

}
