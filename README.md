# jsonapi-documentation

This is a Symfony 5 package that is meant to be used in combination with [Pakhanad JsonApi Bundle](https://github.com/paknahad/jsonapi-bundle).
It can be used to generate Api documentation at anytime during the development, not only after generating entities. This can be
particularly useful when you need to make a lot of changes on your domain model during development.

## Installation
To install this package use composer:
```
composer require bornfight/jsonapi-documentation --dev
```

Register you bundle by adding:
```php
    Bornfight\JsonApiDocumentation\BornfightJsonApiDocumentation::class => [ 'all' => true],
```
 in `bundles.php`
## Usage
To generate JsonApi documentation, use command:
```
php bin/console jsonapi:documentation:generate
```
this will create `documentation/api.yaml` file that can be used as API Documentation on external services like
[Swagger](https://swagger.io/), or to go generate [Postman](https://www.postman.com/) requests.

You can use this command anytime, it will look for the latest changes in your API and overwrite old ``api.yaml`` file.

## How it works

This command will check your Controller classes and look for methods:
- list()
- new()
- view()
- edit()
- delete()

After that, it will check for your Transformer and Hydrator classes to generate request and response schemas.
It expects these methods:
```
getRelationships()
getAttributes()
```
These methods should return an array. The keys of array elements will be used as attributes and relationships in requests.

## Customization

### Using a different template

If you want, you can use a different template. Create ``template.yaml`` file in your `ocumentation/` directory.
Original template file:
```yaml
#template.yaml
openapi: 3.0.0
info:
  description: "This is where you can give more detailed description of your API"
  version: "1.0.0"
  title: "Openapi JsonApi"
  termsOfService: "http://swagger.io/terms/"
  license:
    name: "Apache 2.0"
    url: "http://www.apache.org/licenses/LICENSE-2.0.html"
paths:
externalDocs:
  description: "Find out more about Swagger"
  url: "http://swagger.io"
servers:
  - url: https://localhost:8000/api
    description: Local Environment
components:
  securitySchemes:
    bearerToken:
      type: http
      scheme: bearer
      bearerFormat: JWT
security:
  - bearerToken: []
```

### Add custom documentation
If you want to add custom routes, you can create a class that implements
`CustomDocumentationInterface` class. You also need to give this class a tag with:
```yaml
#services.yaml
    App\Documentation\CustomDocumentationHandler:
        tags: ['doc.custom_handler']
```
After that, you can add any logic you want after the generation process, for example:
```php
<?php

namespace App\Documentation;

use Bornfight\JsonApiDocumentation\Documentation\CustomDocumentationInterface;
use Symfony\Component\Yaml\Yaml;

class CustomDocumentationHandler implements CustomDocumentationInterface
{
    /**
     * @var string
     */
    private $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }


    public function decorate(array &$documentation): void
    {
        $baseDir = '/documentation/parts/';

        // add custom routes
        //login
        $templateFile = $this->projectDir . $baseDir . 'login.yaml';
        $routeDefinition = Yaml::parseFile($templateFile);
        $documentation['paths']['/auth/login'] = $routeDefinition;
    }
}
```
This code will look for you custom yaml file and insert its contents into the documentation.