<?php

namespace Bornfight\JsonApiDocumentation\Documentation;

use Exception;

class EntityDetails
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var Relation[]
     */
    public $relations = [];

    /**
     * @var Attribute[]
     */
    public $attributes = [];

    public function containsAttribute(string $attributeName): bool
    {
        foreach ($this->attributes as $attribute) {
            if ($attribute->name === $attributeName) {
                return true;
            }
        }

        return false;
    }

    public function containsRelation(string $relationName): bool
    {
        foreach ($this->relations as $relation) {
            if ($relation->name === $relationName) {
                return true;
            }
        }

        return false;
    }

    public function getAttribute(string $attributeName): Attribute
    {
        foreach ($this->attributes as $attribute) {
            if ($attribute->name === $attributeName) {
                return $attribute;
            }
        }
        throw new Exception(sprintf('Attribute %s does not exist', $attributeName));
    }

    public function getRelation(string $relationName): Relation
    {
        foreach ($this->relations as $relation) {
            if ($relation->name === $relationName) {
                return $relation;
            }
        }
        throw new Exception(sprintf('Relation %s does not exist', $relationName));
    }
}
