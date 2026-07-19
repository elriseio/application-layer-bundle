<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\Tests\Exception;

use Elrise\Bundle\AppLayerBundle\Exception\RequestException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RequestExceptionTest extends TestCase
{
    public function testDetailsAreStoredFromConstructor(): void
    {
        $exception = new RequestException('boom', ['type' => 'foo']);

        $this->assertSame(['type' => 'foo'], $exception->getDetails());
    }

    public function testWithDetailsReturnsNewInstance(): void
    {
        $original = new RequestException('boom', ['type' => 'foo']);
        $derived = $original->withDetails(['type' => 'bar']);

        $this->assertNotSame($original, $derived);
    }

    public function testWithDetailsDoesNotMutateOriginal(): void
    {
        $original = new RequestException('boom', ['type' => 'foo']);
        $derived = $original->withDetails(['type' => 'bar']);

        $this->assertSame(['type' => 'foo'], $original->getDetails());
        $this->assertSame(['type' => 'bar'], $derived->getDetails());
    }

    public function testWithDetailsPreservesMessageCodeAndPrevious(): void
    {
        $previous = new RuntimeException('root cause');
        $original = new RequestException('boom', ['k' => 'v'], 42, $previous);
        $derived = $original->withDetails(['k' => 'v2']);

        $this->assertSame('boom', $derived->getMessage());
        $this->assertSame(42, $derived->getCode());
        $this->assertSame($previous, $derived->getPrevious());
    }
}
