<?php

// namespace Give2PeerFeatures;

use Doctrine\ORM\EntityManager;
use Give2Peer\Give2PeerBundle\Entity\ItemRepository;
use Give2Peer\Give2PeerBundle\Entity\ReportRepository;
use Give2Peer\Give2PeerBundle\Entity\UserManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * A base context class for our feature context classes.
 *
 * /!\
 * We only have one child class of this (FeatureContext) for the moment.
 */
abstract class BaseContext extends WebTestCase
{

    /**
     * Get entity manager.
     *
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        return $this->get('doctrine')->getManager();
    }

    /**
     * Get user manager.
     *
     * @return UserManager
     */
    protected function getUserManager()
    {
        return $this->get('fos_user.user_manager');
    }

    /**
     * Returns Container instance.
     *
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        return static::$kernel->getContainer();
    }

    /**
     * Gets a parameter.
     *
     * @param string $name The parameter name
     *
     * @return mixed The parameter value
     *
     * @throws InvalidArgumentException if the parameter is not defined
     */
    protected function getParameter($name)
    {
        return $this->getContainer()->getParameter($name);
    }

    /**
     * Get service by id.
     *
     * @param string $id
     *
     * @return object
     */
    protected function get($id)
    {
        return $this->getContainer()->get($id);
    }

    /**
     * Returns fixed step argument (with \\" replaced back to ").
     *
     * @param string $argument
     *
     * @return string
     */
    protected function fixStepArgument($argument)
    {
        return str_replace('\\"', '"', $argument);
    }

    /**
     * Useful variable transformer, that we choose to use manually.
     * If we can somehow specify what transformer a pystring should be submitted
     * to directly in the gherkin without clogging it or loosing the intuitivity
     * of it, this can become in the future an annotated Transformer.
     *
     * @param $pystring
     * @return array
     */
    protected function fromYaml($pystring)
    {
        return Yaml::parse($pystring, true, true);
    }

    /**
     * Recursively removes the $directory and all its contents.
     * If $directory does not exist, silently ignore when not $strict.
     * 
     * This actually should be a global function, PHP-style, I guess...
     *
     * @param string $directory
     * @param bool $strict
     * @throws Exception
     */
    protected static function removeDirectory($directory, $strict = true)
    {
        if (is_dir($directory)) {
            foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $path) {
                $path->isDir() && !$path->isLink() ? rmdir($path->getPathname()) : unlink($path->getPathname());
            }
            rmdir($directory);
        } else {
            if ($strict) {
                throw new Exception("Directory '$directory' does not exist.");
            }
        }
    }
    
    
    // CUSTOM TO GIVE2PEER /////////////////////////////////////////////////////

    /**
     * Get the item repository.
     *
     * @return ItemRepository
     */
    protected function getItemRepository()
    {
        return $this->getEntityManager()->getRepository("Give2PeerBundle:Item");
    }

    /**
     * Get the report repository.
     *
     * @return ReportRepository
     */
    protected function getReportRepository()
    {
        return $this->getEntityManager()->getRepository("Give2PeerBundle:Report");
    }

}