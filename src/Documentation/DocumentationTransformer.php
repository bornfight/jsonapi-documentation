<?php

namespace Bornfight\JsonApiDocumentation\Documentation;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Faker\Factory;
use Faker\Generator;
use Paknahad\JsonApiBundle\JsonApiStr;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 */
class DocumentationTransformer
{
    /**
     * @var array
     */
    private $toManyRelations;
    /**
     * @var Generator
     */
    private $faker;

    public function __construct()
    {
        $this->toManyRelations = [ClassMetadataInfo::TO_MANY, ClassMetadataInfo::MANY_TO_MANY, ClassMetadataInfo::ONE_TO_MANY];
        $this->faker = Factory::create('sl_SI');
        $this->faker->seed(2131);
    }

    /**
     * @param string[] $responseAttributes
     */
    public function transformAttributes(array $responseAttributes, EntityDetails $entityDetails): array
    {
        $fields = [];

        foreach ($responseAttributes as $responseAttribute) {
            if ($entityDetails->containsAttribute($responseAttribute)) {
                $fields[$responseAttribute] = $this->parseType($entityDetails->getAttribute($responseAttribute)->type);
                continue;
            }
            $fields[$responseAttribute] = $this->parseType($this->guessType($responseAttribute));
        }
        if (count($fields) === 0) {
            return [];
        }

        return [
            'type' => 'object',
            'properties' => $fields,
        ];
    }

    public function transformRelations(array $responseRelations, EntityDetails $entityDetails): array
    {
        $relations = [];

        foreach ($responseRelations as $responseRelation) {
            if ($entityDetails->containsRelation($responseRelation)) {
                $relations[$responseRelation] = $this->transformRelation($entityDetails->getRelation($responseRelation));
            }
        }
        if (count($relations) === 0) {
            return [];
        }

        return [
            'type' => 'object',
            'properties' => $relations,
        ];
    }

    private function transformRelation(Relation $relation): array
    {
        $objectDoc = ['type' => 'object', 'properties' => [
                'type' => ['type' => 'string', 'example' => JsonApiStr::entityNameToType($relation->class)], 'id' => ['type' => 'number', 'format' => 'int64', 'example' => (string) $this->faker->numberBetween(1, 40)],
            ],
        ];

        $relationResponse = [
            'type' => 'object', 'properties' => ['data' => [],
            ],
        ];

        if (in_array($relation->type, $this->toManyRelations)) {
            $relationResponse['properties']['data'] = ['type' => 'array', 'items' => $objectDoc];

            return $relationResponse;
        }

        $relationResponse = [
            'type' => 'object', 'properties' => ['data' => $objectDoc,
            ],
        ];

        return $relationResponse;
    }

    private function parseType(string $type): array
    {
        switch ($type) {
            case 'text':
                return [
                    'type' => 'string',
                    'example' => $this->faker->text(500),
                ];
            case 'string':
                return [
                    'type' => 'string',
                    'example' => $this->faker->word,
                ];
            case 'float':
            case 'decimal':
                return [
                    'type' => 'number',
                    'format' => $type,
                    'example' => $this->faker->randomFloat(),
                ];

            case 'smallint':
            case 'bigint':
            case 'integer':
                return [
                    'type' => 'integer',
                    'format' => ($type === 'bigint') ? 'int64' : 'int32',
                    'example' => $this->faker->numberBetween(0, 2000),
                ];
            case 'bool':
            case 'boolean':
                return  [
                    'type' => 'boolean',
                ];
            case 'date':
            case 'date_immutable':
                return [
                    'type' => 'string',
                    'format' => 'date',
                ];

            case 'datetime':
            case 'datetime_immutable':
            case 'datetimetz':
            case 'datetimetz_immutable':
                return [
                    'type' => 'string',
                    'format' => 'date-time',
                ];

            default:
                return [
                    'type' => 'object',
                ];
        }
    }

    private function guessType(string $responseAttribute): string
    {
        if (stripos($responseAttribute, 'date') !== false) {
            return 'datetime';
        }
        if (stripos($responseAttribute, 'price') !== false or stripos($responseAttribute, 'tax') !== false) {
            return 'float';
        }

        return 'string';
    }
}
