<?php

namespace Bornfight\JsonApiDocumentation;

use Bornfight\JsonApiDocumentation\DependencyInjection\JsonApiDocumentationExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class BornfightJsonApiDocumentation extends Bundle
{
    public function getContainerExtension()
    {
        return new JsonApiDocumentationExtension();
    }
}
