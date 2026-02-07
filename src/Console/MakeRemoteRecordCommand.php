<?php

namespace SocialDept\AtpOrm\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use SocialDept\AtpOrm\Support\RecordClassResolver;
use SocialDept\AtpSchema\Support\PathHelper;

class MakeRemoteRecordCommand extends Command
{
    protected $signature = 'make:remote-record
        {name : The name of the remote record class}
        {--collection= : The AT Protocol collection NSID}
        {--no-dto : Skip DTO generation and use class discovery only}';

    protected $description = 'Create a new remote record class';

    public function handle(): int
    {
        $name = $this->argument('name');
        $collection = $this->option('collection') ?? $this->askForCollection($name);

        $path = config('atp-orm.generators.path', 'app/Remote');
        $namespace = PathHelper::pathToNamespace($path);

        $stub = $this->getStub();
        $stub = str_replace('{{ namespace }}', $namespace, $stub);
        $stub = str_replace('{{ class }}', $name, $stub);
        $stub = str_replace('{{ collection }}', $collection, $stub);
        $stub = str_replace('{{ recordClass }}', $this->resolveRecordClass($collection), $stub);

        $filePath = base_path($path.'/'.$name.'.php');

        if (file_exists($filePath)) {
            $this->error("File already exists: {$filePath}");

            return self::FAILURE;
        }

        $directory = dirname($filePath);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($filePath, $stub);

        $this->info("Remote record [{$filePath}] created successfully.");

        return self::SUCCESS;
    }

    protected function getStub(): string
    {
        $customStub = base_path('stubs/remote-record.stub');

        if (file_exists($customStub)) {
            return file_get_contents($customStub);
        }

        return file_get_contents(__DIR__.'/../../stubs/remote-record.stub');
    }

    protected function askForCollection(string $name): string
    {
        $guess = 'app.bsky.'.Str::snake($name, '.');

        return $this->ask('What is the AT Protocol collection NSID?', $guess);
    }

    protected function resolveRecordClass(string $collection): string
    {
        $resolved = RecordClassResolver::resolve($collection);

        if ($resolved) {
            return $resolved;
        }

        if (! $this->option('no-dto')) {
            $this->info("Generating DTO for [{$collection}]...");

            $this->callSilently('schema:generate', ['nsid' => $collection]);

            $resolved = RecordClassResolver::resolve($collection);

            if ($resolved) {
                return $resolved;
            }

            $this->warn("DTO generation did not produce a discoverable class for [{$collection}]. Falling back to namespace convention.");
        }

        // Fall back to the pre-generated namespace convention
        $generatedNamespace = config('atp-schema.generated.namespace', 'SocialDept\\AtpSchema\\Generated');
        $classPath = collect(explode('.', $collection))
            ->map(fn (string $part) => Str::studly($part))
            ->implode('\\');

        return $generatedNamespace.'\\'.$classPath;
    }
}
