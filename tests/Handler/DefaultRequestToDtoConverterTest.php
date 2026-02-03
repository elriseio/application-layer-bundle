<?php

namespace Elrise\Bundle\AppLayerBundle\Tests\Handler;


use Elrise\Bundle\AppLayerBundle\Contract\DtoDeserializerInterface;
use Elrise\Bundle\AppLayerBundle\Handler\DefaultRequestToDtoConverter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class DefaultRequestToDtoConverterTest extends TestCase
{
    public function testConvertFromJson(): void
    {
        $dtoFqcn = DummyDto::class;
        $jsonData = ['name' => 'Alexander'];

        $request = new Request([], [], [], [], [], [], json_encode($jsonData));
        $request->headers->set('Content-Type', 'application/json');

        $deserializer = $this->createMock(DtoDeserializerInterface::class);
        $deserializer->expects($this->once())
            ->method('denormalize')
            ->with($jsonData, $dtoFqcn)
            ->willReturn(new DummyDto('Alexander'));

        $converter = new DefaultRequestToDtoConverter($deserializer);
        $dto = $converter->convert($request, $dtoFqcn);

        $this->assertInstanceOf(DummyDto::class, $dto);
        $this->assertSame('Alexander', $dto->name);
    }

    public function testConvertFromQueryAndForm(): void
    {
        $dtoFqcn = DummyDto::class;
        $queryData = ['name' => 'QueryName'];
        $formData = ['name' => 'FormName', 'age' => 30];
        $expectedData = ['name' => 'FormName', 'age' => 30]; // form overwrites query

        $request = new Request($queryData, $formData);

        $deserializer = $this->createMock(DtoDeserializerInterface::class);
        $deserializer->expects($this->once())
            ->method('denormalize')
            ->with($expectedData, $dtoFqcn)
            ->willReturn(new DummyDto('FormName', 30));

        $converter = new DefaultRequestToDtoConverter($deserializer);
        $dto = $converter->convert($request, $dtoFqcn);

        $this->assertInstanceOf(DummyDto::class, $dto);
        $this->assertSame('FormName', $dto->name);
        $this->assertSame(30, $dto->age);
    }

    public function testConvertThrowsOnInvalidJson(): void
    {
        $this->expectException(\JsonException::class);

        $request = new Request([], [], [], [], [], [], '{invalid json');
        $request->headers->set('Content-Type', 'application/json');

        $deserializer = $this->createMock(DtoDeserializerInterface::class);
        $converter = new DefaultRequestToDtoConverter($deserializer);

        $converter->convert($request, DummyDto::class);
    }
}

class DummyDto
{
    public function __construct(
        public string $name,
        public ?int $age = null
    ) {
    }
}
