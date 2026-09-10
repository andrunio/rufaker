<?php

declare(strict_types=1);

namespace RuFaker\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

/**
 * Guards the promises that would otherwise rest on discipline alone.
 */
final class ArchitectureTest extends TestCase
{
    /** Namespace every class of the package lives in. */
    private const string NAMESPACE = 'RuFaker\\';

    #[Test]
    public function the_core_imports_nothing_but_php_itself(): void
    {
        foreach ($this->sources() as $path => $contents) {
            foreach ($this->imports($contents) as $import) {
                if (str_starts_with($import, self::NAMESPACE)) {
                    continue;
                }

                $this->assertTrue(
                    class_exists($import) || interface_exists($import),
                    "$path imports $import, which does not exist.",
                );

                $this->assertTrue(
                    (new ReflectionClass($import))->isInternal(),
                    "$path imports $import, which is neither PHP core nor part of the package. "
                    . 'The package promises zero dependencies.',
                );
            }
        }
    }

    #[Test]
    public function every_class_of_the_package_is_final(): void
    {
        foreach ($this->sources() as $path => $contents) {
            if (!preg_match('/^(?:final )?(?:readonly )?class /m', $contents)) {
                continue;
            }

            $this->assertMatchesRegularExpression(
                '/^final (?:readonly )?class /m',
                $contents,
                "Class in $path must be final: dropping final later is safe, adding it is not.",
            );
        }
    }

    #[Test]
    public function no_class_of_the_package_is_abstract(): void
    {
        foreach ($this->sources() as $path => $contents) {
            $this->assertDoesNotMatchRegularExpression(
                '/^abstract (?:readonly )?class /m',
                $contents,
                "Class in $path must not be abstract: the package extends by composition, "
                . 'and the final check above does not see an abstract class at all.',
            );
        }
    }

    #[Test]
    public function every_requisite_implements_the_common_contract(): void
    {
        foreach ($this->sources() as $path => $contents) {
            if (!preg_match('/^namespace RuFaker\\\\Requisite;$/m', $contents)) {
                continue;
            }

            if (!preg_match('/^final (?:readonly )?class /m', $contents)) {
                continue;
            }

            $this->assertMatchesRegularExpression(
                '/^final (?:readonly )?class \w+ implements [^\n]*\bRequisite\b/m',
                $contents,
                "Class in $path must implement Requisite: the contract is what makes a requisite "
                . 'interchangeable with any other.',
            );
        }
    }

    /**
     * Reads every PHP source of the package, keyed by file name.
     *
     * @return array<string, string>
     */
    private function sources(): array
    {
        $sources = [];

        /** @var SplFileInfo $file */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__) . '/src')) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $sources[$file->getFilename()] = (string)file_get_contents($file->getPathname());
        }

        return $sources;
    }

    /**
     * Lists the classes a source file imports.
     *
     * @param string $contents
     * @return list<string>
     */
    private function imports(string $contents): array
    {
        preg_match_all('/^use ([^;]+);/m', $contents, $matches);

        return $matches[1];
    }
}
