<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\Extensions;

use Faker\Factory;
use Faker\Generator;

trait WithFaker
{
    protected Generator $faker;

    protected function setUp(string $locale = Factory::DEFAULT_LOCALE): void
    {
        $this->faker = Factory::create($locale);
    }
}
