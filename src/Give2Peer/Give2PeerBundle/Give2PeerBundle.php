<?php

namespace Give2Peer\Give2PeerBundle;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class Give2PeerBundle extends Bundle
{
    /**
     * Returns the bundle's container extension.
     * We want the alias 'give2peer' and the users will too, so we override to
     * skip that check. If that causes problems for you, wow. Just wow.
     * That means Give2Peer has grown so much that... wow.
     * Anyhow, if that's trouble, we'll just make do with the ugly give2_peer,
     * or, hell, it'll probably be time to rename the whole bundle anyways.
     *
     * @return ExtensionInterface|null The container extension
     *
     * @throws \LogicException
     */
    public function getContainerExtension()
    {
        if (null === $this->extension) {
            $extension = $this->createContainerExtension();

            if (null !== $extension) {
                if (!$extension instanceof ExtensionInterface) {
                    throw new \LogicException(sprintf('Extension %s must implement Symfony\Component\DependencyInjection\Extension\ExtensionInterface.', get_class($extension)));
                }

                $this->extension = $extension;
            } else {
                $this->extension = false;
                throw new \LogicException("Wait. What happened to our Extension ?");
            }
        }

        if ($this->extension) {
            return $this->extension;
        }
        
        return null;
    }

}
