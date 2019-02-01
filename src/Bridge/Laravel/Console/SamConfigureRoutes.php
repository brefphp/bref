<?php
/**
 * Created by PhpStorm.
 * User: bubba
 * Date: 2019-02-01
 * Time: 08:56
 */

namespace Bref\Bridge\Laravel\Console;
use Illuminate\Console\Command;
use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Yaml\Yaml;

class SamConfigureRoutes extends Command
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

    public function handle(): int {
        $samConfig = Yaml::parseFile(base_path('template.yaml'), Yaml::PARSE_CUSTOM_TAGS);

        $samConfig['Resources']['Website']['Properties']['FunctionName'] = strtolower(env('APP_NAME').'-apigateway');

        // Handle the website events.
        $samConfig['Resources']['Website']['Properties']['Events'] = [];
        /** @var RouteCollection $routeCollection */
        $routeCollection = Route::getRoutes();
        /** @var \Illuminate\Routing\Route $route */
        foreach($routeCollection->getRoutes() as $route){
            foreach($route->methods() as $method)
                $routeName = ($route->uri() == '/') ? 'root' : preg_replace('/[^A-Za-z0-9\-]/', '', $route->uri());
                $name = $route->getName() ?: sprintf("%s%s", ucfirst(strtolower($method)), ucfirst(strtolower($routeName)));
                $method = strtoupper($method);
                $path = $route->uri()[0] == '/' ? $route->uri() : '/'.$route->uri();
                $samConfig['Resources']['Website']['Properties']['Events'][] = [
                    $name => [
                        'Type' => 'Api',
                        'Properties' => [
                            "Path" => $path,
                            "Method" => $method
                        ]
                    ]
                ];
            }
        file_put_contents(base_path('template.yaml'), Yaml::dump($samConfig, 8, 4));
        return 0;
    }
}
