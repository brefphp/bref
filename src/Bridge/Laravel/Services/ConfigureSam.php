<?php

namespace Bref\Bridge\Laravel\Services;

use Bref\Bridge\Laravel\Events\SamConfigurationRequested;
use Dotenv\Dotenv;
use Symfony\Component\Yaml\Yaml;

class ConfigureSam
{
    /**
     * @var Array
     */
    protected $config;

    /**
     * Handles configuration of our AWS Serverless Application Model template
     * @param SamConfigurationRequested $event
     */
    public function handle(SamConfigurationRequested $event)
    {
        $this->config = Yaml::parseFile(base_path('template.yaml'), Yaml::PARSE_CUSTOM_TAGS);
        $this->setFunctionName('Website', config('bref.website_name'));
        $this->setFunctionName('Artisan', config('bref.artisan_name'));
        $this->setEnvironmentVariables();
        file_put_contents(base_path('template.yaml'), Yaml::dump($this->config, 10, 4));
    }

    /**
     * Sets the function names for us.
     * @param string $resource
     * @param string $functionName
     */
    protected function setFunctionName(string $resource, string $functionName): void
    {
        $this->config['Resources'][$resource]['Properties']['FunctionName'] = $functionName;
    }

    /**
     * Given a list of variable names, or defaults to retrieving them from .env
     * we get and set the environment variables in the SAM template
     * @param array $variableNames
     */
    protected function setEnvironmentVariables(array $variableNames = []): void
    {
        if (empty($variables)) {
            $dot = new Dotenv(base_path());
            $dot->load();
            $variableNames = $dot->getEnvironmentVariableNames();
        }

        foreach ($variableNames as $variableName) {
            $this->config['Globals']['Function']['Environment']['Variables'][$variableName] = (string)env($variableName,
                '');
        }
    }

    /**
     * Check all the routes defined in laravel and ensure we have them setup in
     * the API Gateway for our function.
     */
    protected function setRoutes(): void
    {
        // Handle the website events.
        $this->config['Resources']['Website']['Properties']['Events'] = [];

        /** @var RouteCollection $routeCollection */
        $routeCollection = Route::getRoutes();

        /** @var \Illuminate\Routing\Route $route */
        foreach ($routeCollection->getRoutes() as $route) {
            $methods = $route->methods();
            (collect($methods))->each(function (string $method) use ($route) {
                list($name, $config) = $this->routing($method, $route->uri, $route->getName());
                $this->config['Resources']['Website']['Properties']['Events'][$name] = $config;
            });
        }
    }

    /**
     * Figure out the routing for me.
     *
     * @param string $method
     * @param string $uri
     * @param $name
     * @return array
     */
    protected function routing(string $method, string $uri, $name): array
    {
        $routeName = ($uri == '/') ? 'root' : preg_replace('/[^A-Za-z0-9\-]/', '', $uri);
        $name = $name ? $name : sprintf("%s%s", ucfirst(strtolower($method)), ucfirst(strtolower($routeName)));
        $method = strtoupper($method);
        $path = $uri[0] == '/' ? $uri : '/' . $uri;
        $config = [
            'Type' => 'Api',
            'Properties' => [
                "Path" => $path,
                "Method" => $method
            ]
        ];
        return [$name, $config];
    }
}
