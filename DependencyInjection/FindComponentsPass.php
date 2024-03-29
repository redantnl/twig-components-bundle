<?php

namespace RedAnt\TwigComponentsBundle\DependencyInjection;

use RedAnt\TwigComponents\Registry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Finder\Finder;
use Twig\Error\LoaderError;
use Twig\Loader\FilesystemLoader;
use Twig\Source;

class FindComponentsPass implements CompilerPassInterface
{
    const COMPONENTS_PATH = '/components';

    /**
     * @param ContainerBuilder $container
     *
     * @throws LoaderError
     * @throws \Exception
     */
    public function process(ContainerBuilder $container): void
    {
        // First check if the Twig Component Registry is defined and enabled
        if (!$container->has(Registry::class)) {
            return;
        }

        $componentRegistry = $container->findDefinition(Registry::class);

        /** @var FilesystemLoader $twigLoader */
        $twigNamespaces = $container->get('twig.loader')->getNamespaces();
        foreach ($twigNamespaces as $twigNamespace) {
            if ('!' === $twigNamespace[0]) {
                continue;
            }
            foreach (array_unique($container->get('twig.loader')->getPaths($twigNamespace)) as $path) {
                $this->addComponentsInPath($twigNamespace, $path, $componentRegistry);
            }
        }
    }

    /**
     * @param string     $namespace
     * @param string     $path
     * @param Definition $componentRegistry
     *
     * @throws LoaderError
     */
    protected function addComponentsInPath(string $namespace, string $path, Definition $componentRegistry): void
    {
        $namespace = ($namespace === '__main__') ? '' : "@$namespace/";

        if (is_dir($path . self::COMPONENTS_PATH)) {
            /** @var \SplFileInfo $file */
            foreach ((new Finder())->files()->name('*.twig')->in($path . self::COMPONENTS_PATH) as $file) {
                $relativePath = substr($file->getPath(), stripos($file->getPath(), self::COMPONENTS_PATH) + 1);
                $baseName = $file->getBasename('.html.twig');
                $templateReference = "$namespace$relativePath/" . $file->getFilename();
                $componentName = Registry::getDotNotatedComponentName($templateReference, self::COMPONENTS_PATH);

                $this->ensureComponentIsDefinedInFile($baseName, $file, $templateReference);

                $componentRegistry->addMethodCall('addComponent', [ $componentName, $templateReference ]);
            }
        }
    }

    /**
     * Ensure a template file defines a component with the same name.
     *
     * @param string       $componentName
     * @param \SplFileInfo $file
     * @param string       $templateReference
     *
     * @throws LoaderError
     */
    private function ensureComponentIsDefinedInFile($componentName, $file, $templateReference): void
    {
        $contents = file_get_contents($file->getRealPath());

        if (preg_match("/{%\s+component\s+$componentName\s+{/", $contents) <= 0) {
            throw new LoaderError(
                sprintf('Template "%s" does not contain a definition for component "%s"',
                    $templateReference, $componentName),
                1, new Source($contents, $templateReference, $file));
        }
    }
}
