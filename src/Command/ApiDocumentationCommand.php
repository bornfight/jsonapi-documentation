<?php

namespace Bornfight\JsonApiDocumentation\Command;

use Bornfight\JsonApiDocumentation\Documentation\CustomDocumentationInterface;
use Bornfight\JsonApiDocumentation\Documentation\EntityDetails;
use Bornfight\JsonApiDocumentation\Documentation\EntityDetailsService;
use Bornfight\JsonApiDocumentation\Documentation\JsonApiClassParser;
use Bornfight\JsonApiDocumentation\Documentation\RouteFactory;
use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\LanguageInflectorFactory;
use Exception;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class ApiDocumentationCommand extends Command
{
    protected static $defaultName = 'jsonapi:documentation:generate';
    /**
     * @var string
     */
    private $projectDir;
    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var RouteFactory
     */
    private $routeFactory;
    /**
     * @var Inflector
     */
    private $inflector;
    /**
     * @var array<string,EntityDetails>
     */
    private $entityDetails;
    /**
     * @var JsonApiClassParser
     */
    private $jsonApiClassParser;
    /**
     * @var CustomDocumentationInterface[]|iterable
     */
    private $customHandlers;

    public function __construct(iterable $customHandlers, string $projectDir, Filesystem $filesystem, RouteFactory $routeFactory, EntityDetailsService $entityDetailsService, JsonApiClassParser $jsonApiClassParser, LanguageInflectorFactory $languageInflectorFactory)
    {
        parent::__construct();
        $this->projectDir = $projectDir;
        $this->filesystem = $filesystem;
        $this->routeFactory = $routeFactory;
        $this->inflector = $languageInflectorFactory->build();
        $this->entityDetails = $entityDetailsService->getEntityDetails();
        $this->jsonApiClassParser = $jsonApiClassParser;
        $this->customHandlers = $customHandlers;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(): void
    {
        $this
            ->setDescription('The command to generate JsonApi documentation.')
            ->setHelp('The command to generate JsonApi documentation.');
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     *
     * @throws Exception
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $symfonyStyle = new SymfonyStyle($input, $output);

        try {
            $templateFile = $this->projectDir . '/documentation/template.yaml';
            $docArray = Yaml::parseFile($templateFile);
        } catch (ParseException $exception) {
            $templateFile = dirname(__DIR__) . '/Resources/documentation/template.yaml';
            $docArray = Yaml::parseFile($templateFile);
        }

        $controllers = $this->jsonApiClassParser->loadControllerFiles();

        foreach ($controllers as $controllerName => $controllerCode) {
            $actionNames = $this->jsonApiClassParser->getActionNames($controllerCode);
            $className = explode('Controller', $controllerName)[0];

            if (!isset($this->entityDetails[$className])) {
                continue;
            }

            $routePath = Str::asRoutePath($this->inflector->pluralize($className));
            $routeWithId = $routePath . '/{id}';

            $attributes = $this->jsonApiClassParser->getTransformerAttributes($className);
            $relations = $this->jsonApiClassParser->getTransformerAttributes($className, 'getRelationships');

            if (in_array('index', $actionNames)) {
                $docArray['paths'][$routePath] = $this->routeFactory->createIndexRoute($className, $routePath, $attributes, $relations, $this->entityDetails[$className]);
            }

            if (in_array('new', $actionNames)) {
                $attributes = $this->jsonApiClassParser->getHydratorAttributes($className, 'getAttributeHydrator', 'Create');
                $relations = $this->jsonApiClassParser->getHydratorAttributes($className, 'getRelationshipHydrator', 'Abstract');
                $docArray['paths'][$routePath]['post'] = $this->routeFactory->createNewRoute($className, $routePath, $attributes, $relations, $this->entityDetails[$className]);
            }

            if (in_array('show', $actionNames)) {
                $docArray['paths'][$routeWithId]['get'] = $this->routeFactory->createViewRoute($className, $routeWithId, $attributes, $relations, $this->entityDetails[$className]);
            }

            if (in_array('edit', $actionNames)) {
                $attributes = $this->jsonApiClassParser->getHydratorAttributes($className, 'getAttributeHydrator', 'Update');
                $relations = $this->jsonApiClassParser->getHydratorAttributes($className, 'getRelationshipHydrator', 'Abstract');
                $docArray['paths'][$routeWithId]['patch'] = $this->routeFactory->createEditRoute($className, $routeWithId, $attributes, $relations, $this->entityDetails[$className]);
            }

            if (in_array('delete', $actionNames)) {
                $docArray['paths'][$routeWithId]['delete'] = $this->routeFactory->createDeleteRoute($className);
            }
        }

        $this->customHandlers($docArray);

        ksort($docArray['paths']);
        $content = Yaml::dump($docArray, 40, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);

        $this->filesystem->dumpFile($this->projectDir . '/documentation/api.yaml', $content);
        $symfonyStyle->success('Api documentation generated successfully in /documentation/api.yaml');

        return Command::SUCCESS;
    }

    private function customHandlers(array &$docArray): void
    {
        foreach ($this->customHandlers as $customHandler) {
            $customHandler->decorate($docArray);
        }
    }
}
