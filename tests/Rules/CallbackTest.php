<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Tests\Rules;

use Donatorsky\XmlTemplate\Reader\Models\Contracts\NodeInterface;
use Donatorsky\XmlTemplate\Reader\Rules\Callback;
use Donatorsky\XmlTemplate\Reader\Tests\Extensions\WithFaker;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use stdClass;

/**
 * @covers \Donatorsky\XmlTemplate\Reader\Rules\Callback
 * @coversDefaultClass \Donatorsky\XmlTemplate\Reader\Rules\Callback
 */
class CallbackTest extends TestCase
{
    use ProphecyTrait;

    use WithFaker {
        setUp as setUpFaker;
    }

    /**
     * @var Context|\Prophecy\Prophecy\ObjectProphecy
     */
    private ObjectProphecy $contextProphecy;

    protected function setUp(): void
    {
        $this->setUpFaker();

        $this->contextProphecy = $this->prophesize(Context::class);
    }

    public function testPassesCallsContextsMethod(): void
    {
        $stdClass = new stdClass();
        $string = $this->faker->sentence;
        $int = $this->faker->numberBetween();

        $rule = new Callback('customPasses', 'customProcess', $string, $int);

        $rule->withContext($this->contextProphecy->reveal());

        $returnValue = $this->faker->boolean;

        $this->contextProphecy->customPasses($stdClass, $string, $int)
            ->shouldBeCalledOnce()
            ->willReturn($returnValue);

        $this->contextProphecy->customProcess($stdClass, $string, $int)
            ->shouldNotBeCalled();

        self::assertSame($returnValue, $rule->passes($stdClass));
    }

    public function testProcessCallsContextsMethod(): void
    {
        $stdClass = new stdClass();
        $string = $this->faker->sentence;
        $int = $this->faker->numberBetween();

        $rule = new Callback('customPasses', 'customProcess', $string, $int);

        $rule->withContext($this->contextProphecy->reveal());

        $this->contextProphecy->customPasses($stdClass, $string, $int)
            ->shouldNotBeCalled();

        $returnValue = $this->faker->randomFloat();

        $this->contextProphecy->customProcess($stdClass, $string, $int)
            ->shouldBeCalledOnce()
            ->willReturn($returnValue);

        self::assertSame($returnValue, $rule->process($stdClass));
    }
}

interface Context extends NodeInterface
{
    public function customPasses(stdClass $stdClass, string $string, int $int): bool;

    public function customProcess(stdClass $stdClass, string $string, int $int): float;
}
