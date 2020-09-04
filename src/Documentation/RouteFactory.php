<?php

namespace Bornfight\JsonApiDocumentation\Documentation;

use Faker\Factory;
use Faker\Generator;
use Paknahad\JsonApiBundle\JsonApiStr;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class RouteFactory
{
    /**
     * @var DocumentationTransformer
     */
    private $documentationTransformer;
    /**
     * @var Generator
     */
    private $faker;

    public function __construct(DocumentationTransformer $documentationTransformer)
    {
        $this->faker = Factory::create();
        $this->documentationTransformer = $documentationTransformer;
    }

    public function createIndexRoute(string $className, string $routePath, array $attributes, array $relations, EntityDetails $entityDetails): array
    {
        $responseBody = $this->createListResponseBody($className, $routePath, $attributes, $relations, $entityDetails);

        $paths['get'] = [
            'tags' => [JsonApiStr::entityNameToType($className)],
            'summary' => $className . ' List',
            'operationId' => 'list' . $className,
            'responses' => [
                '200' => [
                    'description' => 'successful operation',
                    'content' => [
                        'application/json' => [
                            'schema' => $responseBody,
                        ],
                    ],
                ],
            ],
        ];

        return $paths;
    }

    public function createNewRoute(string $className, string $routePath, array $attributes, array $relations, EntityDetails $entityDetails): array
    {
        $responseBody = $this->createSingleResponseBody($className, $routePath, $attributes, $relations, $entityDetails);

        return [
            'tags' => [JsonApiStr::entityNameToType($className)],
            'summary' => $className . ' Create',
            'operationId' => 'create' . $className,
            'requestBody' => [
                'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'data' => [
                                'type' => 'object',
                                'properties' => $this->createRequestProperties($className, $attributes, $relations, $entityDetails),
                            ],
                        ],
                    ],
                ],
            ], ],
            'responses' => [
                '200' => [
                    'description' => 'successful operation',
                    'content' => [
                        'application/json' => [
                            'schema' => $responseBody,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function createViewRoute(string $className, string $routePath, array $attributes, array $relations, EntityDetails $entityDetails): array
    {
        $responseBody = $this->createSingleResponseBody($className, $routePath, $attributes, $relations, $entityDetails);

        $parameters = [[
            'name' => 'id',
            'in' => 'path',
            'required' => true,
            'schema' => ['type' => 'integer',
                'format' => 'int64', ],
        ]];

        return [
            'tags' => [JsonApiStr::entityNameToType($className)],
            'summary' => $className . ' View',
            'operationId' => 'view' . $className,
            'parameters' => $parameters,
            'responses' => [
                '200' => [
                    'description' => 'successful operation',
                    'content' => [
                        'application/json' => [
                            'schema' => $responseBody,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function createEditRoute(string $className, string $routePath, array $attributes, array $relations, EntityDetails $entityDetails): array
    {
        $responseBody = $this->createSingleResponseBody($className, $routePath, $attributes, $relations, $entityDetails);

        $parameters = [[
            'name' => 'id',
            'in' => 'path',
            'required' => true,
            'schema' => ['type' => 'integer',
                'format' => 'int64', ],
        ]];

        return [
            'tags' => [JsonApiStr::entityNameToType($className)],
            'summary' => $className . ' Update',
            'operationId' => 'update' . $className,
            'parameters' => $parameters,
            'requestBody' => ['content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'data' => [
                                'type' => 'object',
                                'properties' => $this->createRequestProperties($className, $attributes, $relations, $entityDetails),
                            ],
                        ],
                    ],
                ],
            ]],
            'responses' => [
                '200' => [
                    'description' => 'successful operation',
                    'content' => [
                        'application/json' => [
                            'schema' => $responseBody,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function createDeleteRoute(string $className): array
    {
        $parameters = [[
            'name' => 'id',
            'in' => 'path',
            'required' => true,
            'schema' => ['type' => 'integer',
                'format' => 'int64', ],
        ]];

        return [
            'tags' => [JsonApiStr::entityNameToType($className)],
            'summary' => $className . ' Delete',
            'operationId' => 'delete' . $className,
            'parameters' => $parameters,
            'responses' => [
                '204' => [
                    'description' => 'successful operation',
                ],
            ],
        ];
    }

    private function createListResponseBody(string $className, string $routePath, array $responseAttributes, array $responseRelations, EntityDetails $entityDetails): array
    {
        $items = [
            'type' => 'object',
            'properties' => $this->createResponseProperties($className, $responseAttributes, $responseRelations, $entityDetails),
        ];

        $response = [
            'jsonapi' => [
                'type' => 'object',
                'properties' => [
                    'version' => ['type' => 'string', 'example' => '1.0'],
                ],
            ],
            'links' => Link::generateLinks('list', $routePath),
            'data' => [
                'type' => 'array',
                'items' => $items,
            ],
        ];

        return [
            'type' => 'object',
            'properties' => $response,
        ];
    }

    private function createSingleResponseBody(string $className, string $routePath, array $responseAttributes, array $responseRelations, EntityDetails $entityDetails): array
    {
        $id = (string) $this->faker->numberBetween(1, 30);
        $response = [
            'jsonapi' => [
                'type' => 'object',
                'properties' => [
                    'version' => ['type' => 'string', 'example' => '1.0'],
                ],
            ],
            'links' => Link::generateLinks('view', str_replace('{id}', $id, $routePath)),
            'data' => [
                'type' => 'object',
                'properties' => $this->createResponseProperties($className, $responseAttributes, $responseRelations, $entityDetails, $id),
            ],
        ];

        return [
            'type' => 'object',
            'properties' => $response,
        ];
    }

    public function createRequestProperties(string $className, array $attributes, array $relations, EntityDetails $entityDetails): array
    {
        $properties = [
            'type' => ['type' => 'string', 'example' => JsonApiStr::entityNameToType($className)],
        ];
        if (count($attributes) > 0) {
            $properties['attributes'] = $this->documentationTransformer->transformAttributes($attributes, $entityDetails);
            if (count($properties['attributes']) === 0) {
                unset($properties['attributes']);
            }
        }
        if (count($relations) > 0) {
            $properties['relationships'] = $this->documentationTransformer->transformRelations($relations, $entityDetails);
            if (count($properties['relationships']) === 0) {
                unset($properties['relationships']);
            }
        }

        return $properties;
    }

    public function createResponseProperties(string $className, array $attributes, array $relations, EntityDetails $entityDetails, ?string $id = null): array
    {
        if ($id === null) {
            $id = (string) $this->faker->numberBetween(1, 30);
        }
        $properties = [
            'id' => ['type' => 'integer', 'format' => 'int64', 'example' => $id],
            'type' => ['type' => 'string', 'example' => JsonApiStr::entityNameToType($className)],
        ];
        if (count($attributes) > 0) {
            $properties['attributes'] = $this->documentationTransformer->transformAttributes($attributes, $entityDetails);
            if (count($properties['attributes']) === 0) {
                unset($properties['attributes']);
            }
        }
        if (count($relations) > 0) {
            $properties['relationships'] = $this->documentationTransformer->transformRelations($relations, $entityDetails);
            if (count($properties['relationships']) === 0) {
                unset($properties['relationships']);
            }
        }

        return $properties;
    }
}
