<?php

namespace Bornfight\JsonApiDocumentation\Documentation;

use Exception;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class JsonApiClassParser
{
    /**
     * @var string
     */
    private $projectDir;
    /**
     * @var Parser
     */
    private $parser;

    /**
     * JsonApiClassParser constructor.
     */
    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
        $this->parser = (new ParserFactory())->create(ParserFactory::ONLY_PHP7);
    }

    /**
     * @return string[]
     */
    public function loadControllerFiles(): array
    {
        $controllers = [];
        $finder = new Finder();

        $finder->in($this->projectDir . '/src/Controller/');

        foreach ($finder as $file) {
            if ($file->getExtension() === 'php') {
                $controllers[$file->getFilename()] = $file->getContents();
            }
        }

        return $controllers;
    }

    /**
     * @return string[]
     */
    public function getTransformerAttributes(string $className, string $methodName = 'getAttributes'): array
    {
        $finder = new Finder();

        $finder->in($this->projectDir . '/src/JsonApi/Transformer/');

        foreach ($finder as $file) {
            if ($file->getFilenameWithoutExtension() === $className . 'ResourceTransformer') {
                return $this->parseReturnValues($file, $methodName);
            }
        }
        throw new Exception(sprintf('Could not find transformer %s() method', $methodName));
    }

    /**
     * @return string[]
     */
    public function getHydratorAttributes(string $className, string $methodName = 'getAttributeHydrator', string $prefix = 'Abstract'): array
    {
        $finder = new Finder();

        $finder->in($this->projectDir . '/src/JsonApi/Hydrator/' . $className);

        foreach ($finder as $file) {
            if ($file->getFilenameWithoutExtension() === $prefix . $className . 'Hydrator') {
                return $this->parseReturnValues($file, $methodName);
            }
        }
        throw new Exception(sprintf('Could not find Hydrator file %s', $prefix . $className . 'Hydrator'));
    }

    private function parseReturnValues(SplFileInfo $file, string $methodName): array
    {
        $ast = $this->parser->parse($file->getContents());

        $nodeFinder = new NodeFinder();

        $actions = $nodeFinder->findInstanceOf($ast, Node\Stmt\ClassMethod::class);

        /** @var Node\Stmt\ClassMethod $classMethod */
        foreach ($actions as $classMethod) {
            if ($classMethod->name->name === $methodName) {
                $stmts = $classMethod->getStmts();
                foreach ($stmts as $stmt) {
                    if ($stmt instanceof Node\Stmt\Return_) {
                        /** @var Node\Expr\Array_ $array */
                        $array = $nodeFinder->findFirstInstanceOf($stmt, Node\Expr\Array_::class);
                        if ($array !== null) {
                            return array_map(function (Node\Expr\ArrayItem $item) {
                                /** @var Node\Scalar\String_ $key */
                                $key = $item->key;

                                return $key->value;
                            }, $array->items);
                        }
                    }
                }
            }
        }
        throw new Exception(sprintf('Could not find class %s() method', $methodName));
    }

    /**
     * @return string[]
     */
    public function getActionNames(string $controllerCode): array
    {
        $ast = $this->parser->parse($controllerCode);

        $nodeFinder = new NodeFinder();

        $actions = $nodeFinder->findInstanceOf($ast, Node\Stmt\ClassMethod::class);

        return array_map(function (Node\Stmt\ClassMethod $node) {
            return $node->name->name;
        }, $actions);
    }
}
