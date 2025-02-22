<?php

declare(strict_types=1);

namespace LaravelHyperf\Devtool\Commands;

use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Concerns\NullDisableEventDispatcher;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Watcher\Option;
use Hyperf\Watcher\Watcher;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Input\InputOption;

use function Hyperf\Support\make;

class WatchCommand extends HyperfCommand
{
    use NullDisableEventDispatcher;

    public function __construct(protected ContainerInterface $container)
    {
        parent::__construct('watch');
        $this->setDescription('A hot-reload watcher command for restarting server when files changed.');
        $this->addOption('config', 'C', InputOption::VALUE_OPTIONAL, '', '.watcher.php');
        $this->addOption('file', 'F', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, '', []);
        $this->addOption('dir', 'D', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, '', []);
        $this->addOption('no-restart', 'N', InputOption::VALUE_NONE, 'Whether no need to restart server');
    }

    public function handle()
    {
        if (! class_exists(Watcher::class)) {
            $this->output->error('The hyperf/watcher package is not installed.');
            return;
        }

        $options = $this->container->get(ConfigInterface::class)->get('watcher', []);
        if (empty($options)
            && file_exists($defaultConfigPath = BASE_PATH . '/vendor/hyperf/watcher/publish/watcher.php')
        ) {
            $options = include $defaultConfigPath;
        }
        if (file_exists($configFile = $this->input->getOption('config'))) {
            $options = array_replace($options, (array) include $configFile);
        }

        if (! isset($options['command'])) {
            $options['command'] = 'artisan serve';
        }

        $option = make(Option::class, [
            'options' => $options,
            'dir' => $this->input->getOption('dir'),
            'file' => $this->input->getOption('file'),
            'restart' => ! $this->input->getOption('no-restart'),
        ]);

        $watcher = make(Watcher::class, [
            'option' => $option,
            'output' => $this->output,
        ]);

        $watcher->run();
    }
}
