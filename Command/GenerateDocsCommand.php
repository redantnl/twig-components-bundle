<?php

namespace RedAnt\TwigComponentsBundle\Command;

use RedAnt\TwigComponents\NodeVisitor\ComponentNodeVisitor;
use RedAnt\TwigComponents\Registry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\StringLoaderExtension;

#[AsCommand(name: 'twig:components:generate-docs')]
class GenerateDocsCommand extends Command
{
    protected array $templates;
    protected Environment $twig;
    protected string $global;
    protected array $shortDescription;
    protected array $docBlock;

    public function __construct(Registry $componentRegistry, Environment $twig, string $globalVariable)
    {
        parent::__construct(static::$defaultName);
        $this->templates = $componentRegistry->getComponents();
        $this->twig = $twig;
        $this->global = $globalVariable;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Generate documentation for Twig components')
            ->addArgument('path', InputArgument::REQUIRED,
                'Output directory')
            ->addOption('title', 't', InputOption::VALUE_REQUIRED,
                'Title for the generated documentation', 'Twig components')
            ->addOption('generic', 'g', InputOption::VALUE_NONE,
                'Disregard twig_component.global_variable settings and only show render_component() examples');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $path = rtrim($input->getArgument('path'), "/") . '/';
        $title = $input->getOption('title');

        if (!is_dir($path)) {
            $io->error("Path $path could not be found.");
        } else {
            if (!is_dir($path . 'components')) {
                mkdir($path . 'components');
            }
        }

        $definitions = $this->getDefinitionsFromTemplates();

        if (!file_exists("$path/README.md")) {
            copy(__DIR__ . '/../README.md', "$path/README.md");
        }

        foreach ([ 'index.html.twig', '_sidebar.md.twig' ] as $template) {
            file_put_contents($path . substr($template, 0, -5),
                $this->twig->render("@RedAntTwigComponents/$template", [
                    'title'      => $title,
                    'components' => array_keys($definitions),
                ]));
        }

        foreach ($definitions as $component => $definition) {
            file_put_contents("$path/components/$component.md",
                $this->twig->render('@RedAntTwigComponents/component.md.twig', [
                    'component'         => $component,
                    'short_description' => $this->shortDescription[$component],
                    'comment'           => $this->docBlock[$component],
                    'definition'        => $definition,
                    'template'          => current($this->templates),
                    'global'            => ($input->getOption('generic')) ? false : $this->global
                ]));
        }

        return 0;
    }

    /**
     * @return array
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    protected function getDefinitionsFromTemplates(): array
    {
        $visitor = new ComponentNodeVisitor();
        $this->twig->addNodeVisitor($visitor);
        $this->twig->addExtension(new StringLoaderExtension());

        /**
         * Parse all component templates.
         */
        foreach ($this->templates as $name => $template) {
            $componentName = Registry::getDotNotatedComponentName($template);

            $componentDocBlock = null;
            $source = $this->twig->load($template)->getSourceContext();

            // Parse first doc block before component tag, if it exists
            preg_match('/^\s*{#([\s\S]+?)(?=#})[\s\S]+?{%\scomponent/s', $source->getCode(), $matches);
            if (count($matches) >= 2) {

                $componentDocBlock = trim($matches[1]);
                $trimmedLines = array_map(function ($line) {
                    return trim($line);
                }, explode("\n", $componentDocBlock));

                $this->shortDescription[$componentName] = array_shift($trimmedLines);
                $this->docBlock[$componentName] = join("\n", $trimmedLines);
            } else {
                $this->shortDescription[$componentName] = '';
                $this->docBlock[$componentName] = '';
            }

            $tokens = $this->twig->tokenize($source);
            $this->twig->parse($tokens);
        }

        $definitions = $visitor->getDefinitions();
        ksort($definitions);

        return $definitions;
    }
}