<?php

namespace RedAnt\TwigComponentsBundle;

use RedAnt\TwigComponentsBundle\DependencyInjection\FindComponentsPass;
use RedAnt\TwigComponentsBundle\DependencyInjection\RedAntTwigComponentsExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class RedAntTwigComponentsBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new RedAntTwigComponentsExtension();
        }

        return $this->extension;
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new FindComponentsPass());
    }
}
