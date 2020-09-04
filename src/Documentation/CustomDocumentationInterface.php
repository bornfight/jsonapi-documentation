<?php

namespace Bornfight\JsonApiDocumentation\Documentation;

interface CustomDocumentationInterface
{
    public function decorate(array &$documentation): void;
}
