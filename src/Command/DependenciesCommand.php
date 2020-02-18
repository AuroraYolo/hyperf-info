<?php
declare(strict_types = 1);
namespace Hyperf\Info\Command;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Definition\DefinitionSourceInterface;
use Hyperf\Di\Definition\FactoryDefinition;
use Hyperf\Di\Definition\ObjectDefinition;
use Hyperf\Utils\Codec\Json;
use Psr\Container\ContainerInterface;
use Hyperf\Di\Container;
use Symfony\Component\Console\Helper\TableSeparator;

/**
 * Class DependenciesCommand
 * @package App\Command
 */
class DependenciesCommand extends HyperfCommand implements InterfaceCommand
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
     * @var Container
     */
    private $container;

    /**
     * @var ConfigInterface
     */
    private $config;

    public function __construct(ContainerInterface $container, ConfigInterface $config)
    {
        parent::__construct('info:dependencies');
        $this->container = $container;
        $this->config    = $config;
    }

    /**
     * @inheritDoc
     */
    public function handle()
    {
        $this->output->writeln($this->showMsg($this->loading));
        $definitionSource = $this->container->getDefinitionSource();
        if (!$definitionSource instanceof DefinitionSourceInterface) {
            return null;
        }
        $source = $definitionSource->getDefinitions();
        $data   = [];
        foreach ($source as $dependKey => $dependObj) {
            if ($dependObj instanceof ObjectDefinition) {
                if ($dependObj->isNeedProxy()) {
                    $proxyIdentifier = $dependObj->getClassName() . '_' . md5($dependObj->getClassName());
                    $dependObj->setProxyClassName($proxyIdentifier);
                }
                $data[$dependKey] = [
                    'type'           => 'Object:' . PHP_EOL . $dependObj->getName(),
                    'className'      => $dependObj->getClassName(),
                    'proxyClassName' => $dependObj->isNeedProxy() ? $dependObj->getProxyClassName() : '',
                    'needProxy'      => $dependObj->isNeedProxy() ? 'true' : 'false',
                ];
            } else {
                if ($dependObj instanceof FactoryDefinition) {
                    $data[$dependKey] = [
                        'type'       => 'Factory:' . PHP_EOL . $dependObj->getName(),
                        'FacoryName' => $dependObj->getFactory(),
                        'Parameters' => Json::encode($dependObj->getParameters()),
                        'needProxy'  => $dependObj->isNeedProxy() ? 'true' : 'false',
                    ];
                }
            }
        }
        $rows = [];
        foreach ($data as $depend) {
            $rows[] = $depend;
            $rows[] = new TableSeparator();
        }
        $rows = array_slice($rows, 0, count($rows) - 1);
        $this->table([
            'Type',
            'ClassName',
            'ProxyClassName',
            'NeedProxy',
        ], $rows);
        $this->output->writeln($this->showMsg($this->success));
    }

    public function showMsg($str)
    {
        return str_replace(':msg', $str, "<fg=green>:msg</>");
    }

    public function show($data)
    {
        // TODO: Implement show() method.
    }
}
