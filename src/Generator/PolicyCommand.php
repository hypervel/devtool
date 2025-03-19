<?php

declare(strict_types=1);

namespace Hypervel\Devtool\Generator;

use Hyperf\Devtool\Generator\GeneratorCommand;
use Hyperf\Stringable\Str;
use LogicException;
use Symfony\Component\Console\Input\InputOption;

use function Hyperf\Config\config;

class PolicyCommand extends GeneratorCommand
{
    public function __construct()
    {
        parent::__construct('make:policy');
    }

    public function configure()
    {
        $this->setDescription('Create a new policy class');

        parent::configure();
    }

    /**
     * Replace the class name for the given stub.
     */
    protected function replaceClass(string $stub, string $name): string
    {
        $stub = parent::replaceClass($stub, $name);
        if (! $model = trim($this->input->getOption('model') ?? '')) {
            $modelParts = explode('\\', $name);
            $model = end($modelParts);
            $model = Str::ucfirst(Str::before($model, 'Policy')) ?? 'Dummy';
        }

        $modelNamespace = $this->getConfig()['model_namespace'] ?? 'App\Models';
        $modelNamespace = "{$modelNamespace}\\{$model}";
        $modelVariable = Str::camel($model);

        $userModelNamespace = $this->userProviderModel();
        $userModel = class_basename($userModelNamespace);

        return str_replace(
            ['%NAMESPACED_MODEL%', '%NAMESPACED_USER_MODEL%', '%USER%', '%MODEL%', '%MODEL_VARIABLE%'],
            [$modelNamespace, $userModelNamespace, $userModel, $model, $modelVariable],
            $stub
        );
    }

    protected function userProviderModel(string $modelNamespace = 'App\Models'): string
    {
        $guard = $this->input->getOption('guard') ?: config('auth.defaults.guard');
        if (is_null($guardProvider = config("auth.guards.{$guard}.provider"))) {
            throw new LogicException('The [' . $guard . '] guard is not defined in your "auth" configuration file.');
        }

        if (! config("auth.providers.{$guardProvider}.model")) {
            return "{$modelNamespace}\\User";
        }

        return config("auth.providers.{$guardProvider}.model");
    }

    protected function getStub(): string
    {
        return $this->getConfig()['stub'] ?? __DIR__ . (
            $this->input->getOption('model')
                ? '/stubs/policy.stub'
                : '/stubs/policy.plain.stub'
        );
    }

    protected function getDefaultNamespace(): string
    {
        return $this->getConfig()['namespace'] ?? 'App\Policies';
    }

    protected function getOptions(): array
    {
        return array_merge(parent::getOptions(), [
            ['model', 'm', InputOption::VALUE_OPTIONAL, 'The model that the policy applies to'],
            ['guard', 'g', InputOption::VALUE_OPTIONAL, 'The guard that the policy relies on'],
        ]);
    }
}
