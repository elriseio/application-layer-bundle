<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\Tests\Serialization;

use Elrise\Bundle\AppLayerBundle\Exception\RequestException;
use Elrise\Bundle\AppLayerBundle\Serialization\SymfonyDtoDeserializer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class SymfonyDtoDeserializerTest extends TestCase
{
    private SymfonyDtoDeserializer $deserializer;

    public function testDeserializationWithConstructor(): void
    {
        $data = ['id' => 'abc123', 'count' => 42];
        $dto = $this->deserializer->denormalize($data, ConstructorDto::class);

        $this->assertInstanceOf(ConstructorDto::class, $dto);
        $this->assertSame('abc123', $dto->id);
        $this->assertSame(42, $dto->count);
    }

    public function testDeserializationWithPublicProperties(): void
    {
        $data = ['id' => 'xyz789', 'count' => 99];
        $dto = $this->deserializer->denormalize($data, PublicPropertyDto::class);

        $this->assertInstanceOf(PublicPropertyDto::class, $dto);
        $this->assertSame('xyz789', $dto->id);
        $this->assertSame(99, $dto->count);
    }

    public function testDeserializationWithPrivateProperties(): void
    {
        $data = ['id' => 'secret', 'count' => 123];
        $dto = $this->deserializer->denormalize($data, PrivatePropertyDto::class);

        $this->assertInstanceOf(PrivatePropertyDto::class, $dto);
        $this->assertSame('secret', $dto->getId());
        $this->assertSame(123, $dto->getCount());
    }

    public function testWrapsDenormalizationFailureInRequestException(): void
    {
        $this->expectException(RequestException::class);

        $this->deserializer->denormalize(
            ['id' => 'x', 'unknownField' => 'nope'],
            PrivatePropertyDto::class,
        );
    }

    public function testDenormalizePassesGroupsContextWhenDefaultGroupIsSet(): void
    {
        $serializer = $this->createMock(DenormalizerInterface::class);
        $serializer->expects($this->once())
            ->method('denormalize')
            ->with(
                $this->isArray(),
                $this->isString(),
                $this->isNull(),
                $this->callback(
                    static fn (array $context): bool => isset($context[AbstractNormalizer::GROUPS])
                        && $context[AbstractNormalizer::GROUPS] === ['myGroup'],
                ),
            )
            ->willReturn(new ConstructorDto('abc', 1));

        $deserializer = new SymfonyDtoDeserializer($serializer, 'myGroup');

        $deserializer->denormalize(['id' => 'abc', 'count' => 1], ConstructorDto::class);
    }

    public function testDenormalizeOmitsGroupsContextWhenDefaultGroupIsNull(): void
    {
        $serializer = $this->createMock(DenormalizerInterface::class);
        $serializer->expects($this->once())
            ->method('denormalize')
            ->with(
                $this->isArray(),
                $this->isString(),
                $this->isNull(),
                $this->callback(
                    static fn (array $context): bool => !isset($context[AbstractNormalizer::GROUPS]),
                ),
            )
            ->willReturn(new ConstructorDto('abc', 1));

        $deserializer = new SymfonyDtoDeserializer($serializer, null);

        $deserializer->denormalize(['id' => 'abc', 'count' => 1], ConstructorDto::class);
    }

    public function testCreateDefaultWithGroupProducesWorkingDeserializer(): void
    {
        $deserializer = SymfonyDtoDeserializer::createDefault('myGroup');

        $dto = $deserializer->denormalize(
            ['id' => 'fromFactory', 'count' => 42],
            ConstructorDto::class,
        );

        $this->assertInstanceOf(ConstructorDto::class, $dto);
        $this->assertSame('fromFactory', $dto->id);
        $this->assertSame(42, $dto->count);
    }

    protected function setUp(): void
    {
        $this->deserializer = SymfonyDtoDeserializer::createDefault();
    }
}

final class ConstructorDto
{
    public function __construct(
        public string $id,
        public int $count,
    ) {
    }
}

final class PublicPropertyDto
{
    public string $id;
    public int $count;
}

final class PrivatePropertyDto
{
    private string $id;
    private int $count;

    public function getId(): string
    {
        return $this->id;
    }

    public function getCount(): int
    {
        return $this->count;
    }
}

final class NotADto
{
    public function __construct(public string $id)
    {
    }
}
