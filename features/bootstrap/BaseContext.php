<?php


use Behat\MinkExtension\Context\MinkContext;
use Behat\Symfony2Extension\Context\KernelAwareContext;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class BaseContext extends MinkContext
{

//    /**
//     * @var KernelInterface
//     */
//    protected $kernel;
//
//    /**
//     * {@inheritdoc}
//     */
//    public function setKernel(KernelInterface $kernel)
//    {
//        $this->kernel = $kernel;
//    }

//    /**
//     * Find one resource by name.
//     *
//     * @param string $type
//     * @param string $name
//     *
//     * @return object
//     */
//    protected function findOneByName($type, $name)
//    {
//        return $this->findOneBy($type, array('name' => trim($name)));
//    }
//
//    /**
//     * Find one resource by criteria.
//     *
//     * @param string $type
//     * @param array  $criteria
//     *
//     * @return object
//     *
//     * @throws \InvalidArgumentException
//     */
//    protected function findOneBy($type, array $criteria)
//    {
//        $resource = $this
//            ->getRepository($type)
//            ->findOneBy($criteria)
//        ;
//
//        if (null === $resource) {
//            throw new \InvalidArgumentException(
//                sprintf('%s for criteria "%s" was not found.', str_replace('_', ' ', ucfirst($type)), serialize($criteria))
//            );
//        }
//
//        return $resource;
//    }
//
//    /**
//     * Get repository by resource name.
//     *
//     * @param string $resource
//     *
//     * @return RepositoryInterface
//     */
//    protected function getRepository($resource)
//    {
//        return $this->getService('sylius.repository.'.$resource);
//    }

    /**
     * Get entity manager.
     *
     * @return ObjectManager
     */
    protected function getEntityManager()
    {
        return $this->getService('doctrine')->getManager();
    }

    /**
     * Returns Container instance.
     *
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        return $this->kernel->getContainer();
    }

    /**
     * Get service by id.
     *
     * @param string $id
     *
     * @return object
     */
    protected function getService($id)
    {
        return $this->getContainer()->get($id);
    }

    /**
     * Get current user instance.
     *
     * @return null|UserInterface
     *
     * @throws \Exception
     */
    protected function getUser()
    {
        $token = $this->getSecurityContext()->getToken();

        if (null === $token) {
            throw new \Exception('No token found in security context.');
        }

        return $token->getUser();
    }

    /**
     * Get security context.
     *
     * @return SecurityContextInterface
     */
    protected function getSecurityContext()
    {
        return $this->getContainer()->get('security.context');
    }

    /**
     * Generate url.
     *
     * @param string  $route
     * @param array   $parameters
     * @param Boolean $absolute
     *
     * @return string
     */
    protected function generateUrl($route, array $parameters = array(), $absolute = false)
    {
        return $this->locatePath($this->getService('router')->generate($route, $parameters, $absolute));
    }

//    /**
//     * Presses button with specified id|name|title|alt|value.
//     */
//    protected function pressButton($button)
//    {
//        $this->getSession()->getPage()->pressButton($this->fixStepArgument($button));
//    }
//
//    /**
//     * Clicks link with specified id|title|alt|text.
//     */
//    protected function clickLink($link)
//    {
//        $this->getSession()->getPage()->clickLink($this->fixStepArgument($link));
//    }
//
//    /**
//     * Fills in form field with specified id|name|label|value.
//     */
//    protected function fillField($field, $value)
//    {
//        $this->getSession()->getPage()->fillField($this->fixStepArgument($field), $this->fixStepArgument($value));
//    }

//    /**
//     * Selects option in select field with specified id|name|label|value.
//     */
//    public function selectOption($select, $option)
//    {
//        $this->getSession()->getPage()->selectFieldOption($this->fixStepArgument($select), $this->fixStepArgument($option));
//    }

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
}
