<?php


use Doctrine\ORM\EntityManager;
use Give2Peer\Give2PeerBundle\Entity\UserManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

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
    protected function fromYaml($pystring) {
        return Yaml::parse($pystring, true, true);
    }
}
