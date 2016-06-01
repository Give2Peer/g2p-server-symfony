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

        // Bulk add all configuration as container parameters
        $this->setParameters($config, $container);

        // Load the services we want to register
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
     * For example, a config such as this for the alias give2peer :
     *
     * ``` yaml
     * give2peer:
     *   enabled: true
     *   cache:
     *      directory: "%kernel.root_dir%/tmp"
     *   things:
     *      - first
     *      - second
     * ```
     *
     * would yield the following :
     *
     * getParameter('give2peer.enabled') === true
     * getParameter('give2peer.cache') ---> InvalidArgumentException
     * getParameter('give2peer.cache.directory') == "/var/www/g2p/tmp"
     * getParameter('give2peer.things') == array('first', 'second')
     *
     * It will resolve `%` variables like it normally would.
     * This is simply a convenience method to add the whole array.
     *
     * @param array $config
     * @param ContainerBuilder $container
     * @param string $namespace The parameter prefix, the alias by default.
     *                          Don't use this, it's for recursion.
     */
    protected function setParameters(array $config, ContainerBuilder $container,
                                     $namespace = null)
    {
        $namespace = (null === $namespace) ? $this->getAlias() : $namespace;

        // Is the config array associative or empty ?
        if (array_keys($config) !== range(0, count($config) - 1)) {
            foreach ($config as $k => $v) {
                $current = $namespace . '.' . $k;
                if (is_array($v)) {
                    // Another array, let's use recursion
                    $this->setParameters($v, $container, $current);
                } else {
                    // It's a leaf, let's add it.
                    $container->setParameter($current, $v);
                }
            }
        } else {
            // It is a sequential array, let's consider it as a leaf.
            $container->setParameter($namespace, $config);
        }
    }

}
