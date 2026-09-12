<?php

declare(strict_types=1);

namespace RuFaker\Tests\Faker;

use Faker\Factory;
use Faker\Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;
use RuFaker\Faker\RuFakerProvider;
use RuFaker\Faker\Values;
use RuFaker\RuFaker;

#[CoversClass(RuFakerProvider::class)]
final class RuFakerProviderTest extends TestCase
{
    #[Test]
    public function it_answers_a_faker_call_both_ways(): void
    {
        $faker = $this->faker();

        $this->assertInstanceOf(Values::class, $faker->format('ruFaker'));
        $this->assertInstanceOf(Values::class, $faker->__call('ruFaker', []));
    }

    #[Test]
    public function it_registers_exactly_one_name_with_faker(): void
    {
        $methods = (new ReflectionClass(RuFakerProvider::class))
            ->getMethods(ReflectionMethod::IS_PUBLIC);

        $names = array_map(
            static fn(ReflectionMethod $method): string => $method->getName(),
            $methods,
        );

        // Every public method becomes a formatter, and a clash is resolved silently in favour of
        // the provider added last. One name is one chance of that.
        $this->assertSame(
            ['__construct', 'ruFaker'],
            $names,
        );
    }

    #[Test]
    public function it_follows_the_seed_faker_sets(): void
    {
        $faker = $this->faker();

        $faker->seed(1234);

        $first = [
            $this->values($faker)->inn(),
            $this->values($faker)->bankAccount(),
        ];

        $faker->seed(1234);

        $this->assertSame(
            $first,
            [
                $this->values($faker)->inn(),
                $this->values($faker)->bankAccount(),
            ],
        );
    }

    #[Test]
    public function it_leaves_the_native_russian_provider_alone(): void
    {
        $faker = $this->faker();

        $this->assertIsString($faker->format('inn10'));
        $this->assertIsString($faker->format('kpp'));
    }

    #[Test]
    public function it_takes_values_of_its_own(): void
    {
        $provider = new RuFakerProvider(
            new Values(
                RuFaker::seeded(1234),
            ),
        );

        $this->assertSame(
            RuFaker::seeded(1234)->inn()->value,
            $provider->ruFaker()->inn(),
        );
    }

    /**
     * Resolves the entry point through Faker itself, the way a caller reaches it.
     *
     * @param Generator $faker
     * @return Values
     */
    private function values(Generator $faker): Values
    {
        $values = $faker->format('ruFaker');

        if (!$values instanceof Values) {
            $this->fail('Faker resolved ruFaker to something other than the values of the package.');
        }

        return $values;
    }

    /**
     * Builds a Faker generator with the package registered on it.
     *
     * @return Generator
     */
    private function faker(): Generator
    {
        $faker = Factory::create('ru_RU');
        $faker->addProvider(new RuFakerProvider());

        return $faker;
    }
}
