<?php

declare(strict_types=1);

namespace LaravelHyperf\Devtool\Generator;

use Carbon\Carbon;
use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Devtool\Generator\GeneratorCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BatchesTableCommand extends GeneratorCommand
{
    public function __construct()
    {
        parent::__construct('make:queue-batches-table');
    }

    public function configure()
    {
        $this->setDescription('Create a migration for the batches database table');
        $this->setAliases(['queue:batches-table']);

        parent::configure();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $tableName = $this->migrationTableName();
        $filename = Carbon::now()->format('Y_m_d_000000') . "_create_{$tableName}_table.php";
        $path = $this->input->getOption('path') ?: "database/migrations/{$filename}";

        // First we will check to see if the class already exists. If it does, we don't want
        // to create the class and overwrite the user's code. So, we will bail out so the
        // code is untouched. Otherwise, we will continue generating this class' files.
        if (($input->getOption('force') === false) && $this->alreadyExists($path)) {
            $output->writeln(sprintf('<fg=red>%s</>', $path . ' already exists!'));
            return 0;
        }

        // Next, we will generate the path to the location where this class' file should get
        // written. Then, we will build the class and make the proper replacements on the
        // stub files so that it gets the correctly formatted namespace and class name.
        $this->makeDirectory($path);

        $stub = file_get_contents($this->getStub());
        file_put_contents($path, $this->buildMigration($stub, $tableName));

        $output->writeln(sprintf('<info>%s</info>', "Migration {$filename} created successfully."));

        $this->openWithIde($path);

        return 0;
    }

    protected function buildMigration(string $stub, string $name): string
    {
        return str_replace('%TABLE%', $name, $stub);
    }

    protected function getStub(): string
    {
        return $this->getConfig()['stub'] ?? __DIR__ . '/stubs/batches-table.stub';
    }

    protected function alreadyExists(string $rawName): bool
    {
        return is_file(BASE_PATH . "/{$rawName}");
    }

    protected function getArguments(): array
    {
        return [];
    }

    protected function getOptions(): array
    {
        return array_merge(parent::getOptions(), [
            ['path', 'p', InputOption::VALUE_OPTIONAL, 'The path of the sessions table migration.'],
        ]);
    }

    protected function getDefaultNamespace(): string
    {
        return '';
    }

    /**
     * Get the migration table name.
     */
    protected function migrationTableName(): string
    {
        return ApplicationContext::getContainer()
            ->get(ConfigInterface::class)
            ->get('queue.batching.table', 'job_batches');
    }
}
