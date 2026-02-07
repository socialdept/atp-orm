<?php

namespace SocialDept\AtpOrm\Support;

use Illuminate\Support\Str;
use SocialDept\AtpSupport\PathHelper;

class RecordClassResolver
{
    /**
     * Resolve an NSID to a fully-qualified Data class name.
     * Returns null if no matching class is found.
     */
    public static function resolve(string $nsid): ?string
    {
        $classPath = collect(explode('.', $nsid))
            ->map(fn (string $part) => Str::studly($part))
            ->implode('\\');

        // 1. Check app lexicons namespace first
        $appNamespace = PathHelper::pathToNamespace(config('atp-schema.generators.lexicon_path', 'app/Lexicons'));
        $appClass = $appNamespace.'\\'.$classPath;

        if (class_exists($appClass)) {
            return $appClass;
        }

        // 2. Check pre-generated namespace
        $generatedNamespace = config('atp-schema.generated.namespace', 'SocialDept\\AtpSchema\\Generated');
        $generatedClass = $generatedNamespace.'\\'.$classPath;

        if (class_exists($generatedClass)) {
            return $generatedClass;
        }

        return null;
    }
}
