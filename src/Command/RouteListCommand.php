<?php
declare(strict_types = 1);
namespace Hyperf\Info\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpServer\MiddlewareManager;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Helper\Table;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Symfony\Component\Console\Helper\TableSeparator;

class RouteListCommand extends HyperfCommand implements InterfaceCommand
{
    /**
     * Loading
     * @var string
     */
    private $loading = 'Loading...';

    /**
     * @var bool
     */
    private $isFind = false;

    /**
     * Success
     * @var string
     */
    private $success = 'Success...';
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ConfigInterface
     */
    private $config;

    protected $routes = [BASE_PATH . '/config/routes.php'];

    public function __construct(ContainerInterface $container, ConfigInterface $config)
    {
        parent::__construct('info:route');
        $this->container = $container;
        $this->config    = $config;
    }

    protected function configure()
    {
        $this->setDescription('Get routes [{path}] to get the route info.');
        $this->addUsage("--path=/index");
        $this->addOption('path', 'p', InputOption::VALUE_NONE, null);
    }

    /**
     * @inheritDoc
     */
    public function handle()
    {
        $path = $this->input->getOption('path');
        if ($path !== null) {
            $this->isFind = true;
        }
        $data = [];

        $factory = $this->container->get(DispatcherFactory::class);
        $router  = $factory->getRouter('http');
        [$routers] = $router->getData();
        $this->output->writeln($this->showMsg($this->loading));
        foreach ($routers as $method => $items) {
            foreach ($items as $item) {
                $uri = $item->route;
                if (is_array($item->callback)) {
                    $action = $item->callback[0] . '@' . $item->callback[1];
                } else {
                    $action = $item->callback;
                }
                if (isset($data[$uri])) {
                    $data[$uri]['method'][] = $method;
                } else {
                    // method,uri,name,action,middleware
                    $serverName          = 'http';
                    $registedMiddlewares = MiddlewareManager::get('http', $uri, $method);
                    $middlewares         = $this->config->get('middlewares.' . $serverName, []);

                    $middlewares = array_merge($middlewares, $registedMiddlewares);
                    $data[$uri]  = [
                        'server'     => $serverName,
                        'method'     => [$method],
                        'uri'        => $uri,
                        'action'     => $action,
                        'middleware' => implode(PHP_EOL, array_unique($middlewares))
                    ];
                }
            }
        }
        if ($this->isFind) {
            foreach ($data as $uri => $route) {
                if (strpos($path, $uri) !== false) {
                    $data       = null;
                    $data[$uri] = $route;
                    break;
                }
            }
        }
        $this->show($data);
        $this->success($this->success);
    }

    public function showMsg($str)
    {
        return str_replace(':msg', $str, "<fg=green>:msg</>");
    }

    public function show($data)
    {
        $rows = [];
        foreach ($data as $route) {
            $route['method'] = implode('|', $route['method']);
            $rows[]          = $route;
            $rows[]          = new TableSeparator();
        }
        $rows  = array_slice($rows, 0, count($rows) - 1);
        $table = new Table($this->output);
        $table
            ->setHeaders(['Server', 'Method', 'URI', 'Action', 'Middleware'])
            ->setRows($rows);
        $table->render();
    }
}
