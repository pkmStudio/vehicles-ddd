<?php

declare(strict_types=1);

namespace Tests\Feature\Architecture;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Tests\TestCase;

final class LayerDependencyTest extends TestCase
{
    public function test_domain_does_not_depend_on_outer_layers(): void
    {
        $violations = [];

        foreach ($this->phpFiles() as $file) {
            if (! $this->isLayerFile($file, 'Domain')) {
                continue;
            }

            foreach ($this->imports($file) as $import) {
                if (str_contains($import, '\\Application\\')
                    || str_contains($import, '\\Infrastructure\\')
                    || str_contains($import, '\\Presentation\\')) {
                    $violations[] = $this->violation($file, $import);
                }
            }
        }

        self::assertSame([], $violations, implode(PHP_EOL, $violations));
    }

    public function test_application_does_not_depend_on_outer_layers(): void
    {
        $violations = [];

        foreach ($this->phpFiles() as $file) {
            if (! $this->isLayerFile($file, 'Application')) {
                continue;
            }

            foreach ($this->imports($file) as $import) {
                if (str_contains($import, '\\Infrastructure\\') || str_contains($import, '\\Presentation\\')) {
                    $violations[] = $this->violation($file, $import);
                }
            }
        }

        self::assertSame([], $violations, implode(PHP_EOL, $violations));
    }

    public function test_application_uses_only_own_feature_domain_or_allowed_shared_kernel(): void
    {
        $violations = [];

        foreach ($this->phpFiles() as $file) {
            $context = $this->featureApplicationContext($file);

            if ($context === null) {
                continue;
            }

            foreach ($this->imports($file) as $import) {
                $module = $this->moduleName($import);

                if ($module !== null && $module !== $context['module'] && $module !== 'Templates') {
                    $violations[] = $this->violation($file, $import);

                    continue;
                }

                $featureDomain = $this->featureDomainImport($import);

                if ($featureDomain !== null
                    && $featureDomain['module'] === $context['module']
                    && $featureDomain['feature'] !== $context['feature']) {
                    $violations[] = $this->violation($file, $import);
                }
            }
        }

        self::assertSame([], $violations, implode(PHP_EOL, $violations));
    }

    public function test_public_clients_do_not_query_database_directly(): void
    {
        $violations = [];

        foreach ($this->phpFiles() as $file) {
            if (! str_contains($file->getPathname(), '/Infrastructure/Clients/')) {
                continue;
            }

            $contents = (string) file_get_contents($file->getPathname());

            if (str_contains($contents, 'DB::')
                || str_contains($contents, 'Model::query(')
                || str_contains($contents, 'Illuminate\\Support\\Facades\\DB')) {
                $violations[] = $file->getPathname();
            }
        }

        self::assertSame([], $violations, implode(PHP_EOL, $violations));
    }

    public function test_kit_properties_exceptions_do_not_leak_to_consumer_application_layers(): void
    {
        $ownerException = 'App\\Modules\\Warehouse\\Features\\KitProperties\\Domain\\Exceptions\\KitCompositionException';
        $violations = [];

        foreach ($this->phpFiles() as $file) {
            $path = $file->getPathname();

            if (str_contains($path, '/Warehouse/Features/KitProperties/')) {
                continue;
            }

            if (str_contains($path, '/Warehouse/Features/') && str_contains($path, '/Infrastructure/Clients/')) {
                continue;
            }

            foreach ($this->imports($file) as $import) {
                if ($import === $ownerException) {
                    $violations[] = $this->violation($file, $import);
                }
            }
        }

        self::assertSame([], $violations, implode(PHP_EOL, $violations));
    }

    /**
     * @return iterable<int, SplFileInfo>
     */
    private function phpFiles(): iterable
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(base_path('app/Modules')),
        );

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || ! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            if (str_contains($file->getPathname(), '/Features/Maintenance/')) {
                continue;
            }

            yield $file;
        }
    }

    private function isLayerFile(SplFileInfo $file, string $layer): bool
    {
        return str_contains($file->getPathname(), "/{$layer}/");
    }

    /**
     * @return list<string>
     */
    private function imports(SplFileInfo $file): array
    {
        $contents = (string) file_get_contents($file->getPathname());

        preg_match_all('/^use\s+([^;]+);/m', $contents, $matches);

        return $matches[1] ?? [];
    }

    /**
     * @return null|array{module: string, feature: string}
     */
    private function featureApplicationContext(SplFileInfo $file): ?array
    {
        $path = $file->getPathname();

        if (preg_match('#/Modules/([^/]+)/Features/([^/]+)/Application/#', $path, $matches) !== 1) {
            return null;
        }

        return [
            'module' => $matches[1],
            'feature' => $matches[2],
        ];
    }

    /**
     * @return null|array{module: string, feature: string}
     */
    private function featureDomainImport(string $import): ?array
    {
        if (preg_match('/^App\\\\Modules\\\\([^\\\\]+)\\\\Features\\\\([^\\\\]+)\\\\Domain\\\\/', $import, $matches) !== 1) {
            return null;
        }

        return [
            'module' => $matches[1],
            'feature' => $matches[2],
        ];
    }

    private function moduleName(string $import): ?string
    {
        if (preg_match('/^App\\\\Modules\\\\([^\\\\]+)/', $import, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    private function violation(SplFileInfo $file, string $import): string
    {
        return $file->getPathname().' imports '.$import;
    }
}
