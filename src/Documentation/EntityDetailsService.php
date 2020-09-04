<?php

namespace Bornfight\JsonApiDocumentation\Documentation;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Exception;
use Symfony\Component\Finder\Finder;

class EntityDetailsService
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var string
     */
    private $projectDir;

    public function __construct(string $projectDir, EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->projectDir = $projectDir;
    }

    public function getEntityDetails(): array
    {
        $entityDetails = [];
        $finder = new Finder();

        $finder->in($this->projectDir . '/src/Entity/');

        foreach ($finder as $file) {
            if ($file->getExtension() === 'php') {
                try {
                    $metadata = $this->entityManager->getClassMetadata('App\\Entity\\' . $file->getFilenameWithoutExtension());
                } catch (Exception $exception) {
                    continue;
                }
                $entityDetails[$file->getFilenameWithoutExtension()] = $this->createEntityDetails($metadata);
            }
        }

        return $entityDetails;
    }

    private function createEntityDetails(ClassMetadata $metadata): EntityDetails
    {
        $entityDetails = new EntityDetails();
        $entityDetails->name = $metadata->getName();

        foreach ($metadata->getFieldNames() as $fieldName) {
            $attribute = new Attribute();
            $attribute->name = $fieldName;
            $attribute->type = $metadata->getTypeOfField($fieldName);
            $entityDetails->attributes[] = $attribute;
        }

        foreach ($metadata->getAssociationNames() as $associationName) {
            $mappings = $metadata->getAssociationMapping($associationName);
            $relation = new Relation();
            $relation->name = $associationName;
            $relation->type = $mappings['type'];
            $relation->class = $mappings['targetEntity'];
            $entityDetails->relations[] = $relation;
        }

        return $entityDetails;
    }
}
