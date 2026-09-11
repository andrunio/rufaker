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
                    class_exists($import) || interface_exists($import) || trait_exists($import),
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

    #[Test]
    public function every_result_implements_the_common_contract(): void
    {
        foreach ($this->sources() as $path => $contents) {
            if (!preg_match('/^namespace RuFaker\\\\Result;$/m', $contents)) {
                continue;
            }

            if (!preg_match('/^final (?:readonly )?class /m', $contents)) {
                continue;
            }

            $this->assertMatchesRegularExpression(
                '/^final (?:readonly )?class \w+ implements [^\n]*\bResult\b/m',
                $contents,
                "Class in $path must implement Result: the contract is what makes every assembled "
                . 'set serialisable the same way.',
            );
        }
    }

    #[Test]
    public function every_exception_of_the_package_implements_the_common_contract(): void
    {
        foreach ($this->sources() as $path => $contents) {
            if (!preg_match('/^namespace RuFaker\\\\Exception;$/m', $contents)) {
                continue;
            }

            if (!preg_match('/^final (?:readonly )?class /m', $contents)) {
                continue;
            }

            $this->assertMatchesRegularExpression(
                '/^final (?:readonly )?class \w+ extends \w+ implements [^\n]*\bException\b/m',
                $contents,
                "Class in $path must extend an SPL exception and implement Exception: the interface "
                . 'is what lets a caller catch everything the package throws by one type.',
            );
        }
    }

    #[Test]
    public function everything_inside_internal_is_marked_internal(): void
    {
        foreach ($this->sources() as $path => $contents) {
            if (!str_starts_with($path, 'Internal/')) {
                continue;
            }

            $this->assertMatchesRegularExpression(
                '/^ \* @internal$/m',
                $contents,
                "Declaration in $path must carry @internal: the package promises that everything "
                . 'in RuFaker\\Internal changes without notice, and the promise needs the mark.',
            );
        }
    }

    /**
     * Reads every PHP source of the package, keyed by its path inside src.
     *
     * @return array<string, string>
     */
    private function sources(): array
    {
        $sources = [];
        $root = dirname(__DIR__) . '/src';

        /** @var SplFileInfo $file */
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root)) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $path = str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen($root) + 1));
            $sources[$path] = (string)file_get_contents($file->getPathname());
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
