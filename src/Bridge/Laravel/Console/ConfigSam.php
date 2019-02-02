<?php
/**
 * Created by PhpStorm.
 * User: bubba
 * Date: 2019-02-01
 * Time: 08:56
 */

namespace Bref\Bridge\Laravel\Console;

use Dotenv\Dotenv;
use Illuminate\Console\Command;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Yaml\Yaml;

class ConfigSam extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'bref:config-sam';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configure the SAM Template with our Laravel Routes';

    public function handle(): int
    {
        $samConfig = Yaml::parseFile(base_path('template.yaml'), Yaml::PARSE_CUSTOM_TAGS);
        $samConfig['Resources']['Website']['Properties']['FunctionName'] = strtolower(env('APP_NAME') . '-apigateway');


        /** @var Dotenv $dotenv */
        $dotenv = new Dotenv(base_path());
        $dotenv->load();
        // Import the environment settings from .env
        foreach ($dotenv->getEnvironmentVariableNames() as $environmentVariableName){
            $samConfig['Globals']['Function']['Environment']['Variables'][$environmentVariableName] = (string)env($environmentVariableName, '');
        }

        // Handle the website events.
        $samConfig['Resources']['Website']['Properties']['Events'] = [];
        /** @var RouteCollection $routeCollection */
        $routeCollection = Route::getRoutes();
        /** @var \Illuminate\Routing\Route $route */
        foreach ($routeCollection->getRoutes() as $route) {
            $methods = $route->methods();
            (collect($methods))->each(function (string $method) use ($route, &$samConfig) {
                list($name, $config) = $this->routing($method, $route->uri, $route->getName());
                $samConfig['Resources']['Website']['Properties']['Events'][$name] = $config;
            });
        }
        file_put_contents(base_path('template.yaml'), Yaml::dump($samConfig, 8, 4));
        return 0;
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
