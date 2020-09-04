<?php

namespace Bornfight\JsonApiDocumentation\Documentation;

use Symfony\Component\Yaml\Yaml;

class CustomDocumentationHandler implements CustomDocumentationInterface
{
    /**
     * @var string
     */
    private $projectDir;

    /**
     * CustomDocumentationHandler constructor.
     */
    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    /**
     * @SuppressWarnings(PHPMD.StaticAccess)
     */
    public function decorate(array &$documentation): void
    {
        $baseDir = '/documentation/parts/';

        // add custom routes
        //login
        $templateFile = $this->projectDir . $baseDir . 'login.yaml';
        $routeDefinition = Yaml::parseFile($templateFile);
        $documentation['paths']['/auth/login'] = $routeDefinition;

        //refresh
        $templateFile = $this->projectDir . $baseDir . 'refresh_token.yaml';
        $routeDefinition = Yaml::parseFile($templateFile);
        $documentation['paths']['/auth/refresh-token'] = $routeDefinition;

        //img upload
        $templateFile = $this->projectDir . $baseDir . 'image.yaml';
        $routeDefinition = Yaml::parseFile($templateFile);
        $documentation['paths']['/images']['post'] = $routeDefinition;

        //attachment upload
        $templateFile = $this->projectDir . $baseDir . 'attachment.yaml';
        $routeDefinition = Yaml::parseFile($templateFile);
        $documentation['paths']['/attachments']['post'] = $routeDefinition;

        //pdf
        $templateFile = $this->projectDir . $baseDir . 'uploaded_pdf.yaml';
        $routeDefinition = Yaml::parseFile($templateFile);
        $documentation['paths']['/uploaded/pdfs']['post'] = $routeDefinition;

        //translations
        $templateFile = $this->projectDir . $baseDir . 'translation_index.yaml';
        $routeDefinition = Yaml::parseFile($templateFile);
        $documentation['paths']['/translations/index']['get'] = $routeDefinition;

        $templateFile = $this->projectDir . $baseDir . 'translation_get.yaml';
        $routeDefinition = Yaml::parseFile($templateFile);
        $documentation['paths']['/translations']['get'] = $routeDefinition;

        $templateFile = $this->projectDir . $baseDir . 'translation_post.yaml';
        $routeDefinition = Yaml::parseFile($templateFile);
        $documentation['paths']['/translations']['post'] = $routeDefinition;

        //price calculation
        $templateFile = $this->projectDir . $baseDir . 'price_calculator.yaml';
        $routeDefinition = Yaml::parseFile($templateFile);
        $documentation['paths']['/price/calculator']['post'] = $routeDefinition;
    }
}
