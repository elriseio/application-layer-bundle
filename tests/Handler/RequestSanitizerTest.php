<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\Tests\Handler;

use Elrise\Bundle\AppLayerBundle\Contract\RequestSanitizerInterface;
use Elrise\Bundle\AppLayerBundle\Handler\RequestSanitizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class RequestSanitizerTest extends TestCase
{
    public function testSanitizeReturnsSameRequestInstance(): void
    {
        $sanitizer = new RequestSanitizer();
        $request = new Request();

        $this->assertSame($request, $sanitizer->sanitize($request));
    }

    public function testSanitizePreservesRequestPayload(): void
    {
        $sanitizer = new RequestSanitizer();
        $request = Request::create('/orders', 'POST', ['note' => 'priority']);
        $request->headers->set('X-Trace', 'abc-123');

        $result = $sanitizer->sanitize($request);

        $this->assertSame($request, $result);
        $this->assertSame('priority', $result->request->get('note'));
        $this->assertSame('abc-123', $result->headers->get('X-Trace'));
        $this->assertSame('POST', $result->getMethod());
    }

    public function testSanitizeReturnsRequest(): void
    {
        $sanitizer = new RequestSanitizer();

        $this->assertInstanceOf(Request::class, $sanitizer->sanitize(new Request()));
    }

    public function testImplementsRequestSanitizerInterface(): void
    {
        $this->assertInstanceOf(RequestSanitizerInterface::class, new RequestSanitizer());
    }
}
