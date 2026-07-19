<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\Tests\Serialization;

use DateTimeImmutable;
use DateTimeInterface;
use Elrise\Bundle\AppLayerBundle\Serialization\selfDenormalizer;
use PHPUnit\Framework\TestCase;
use stdClass;
use Stringable;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use UnitEnum;

final class SelfDenormalizerTest extends TestCase
{
    public function testSupportsDenormalizationReturnsTrueForDtoClasses(): void
    {
        $denormalizer = new selfDenormalizer();

        $this->assertTrue($denormalizer->supportsDenormalization([], stdClass::class));
    }

    public function testSupportsDenormalizationReturnsFalseForDateTime(): void
    {
        $denormalizer = new selfDenormalizer();

        $this->assertFalse($denormalizer->supportsDenormalization([], DateTimeImmutable::class));
    }

    public function testSupportsDenormalizationReturnsFalseForUnknownClass(): void
    {
        $denormalizer = new selfDenormalizer();

        $this->assertFalse($denormalizer->supportsDenormalization([], 'App\\Nonexistent\\Class'));
    }

    public function testSupportsDenormalizationReturnsFalseForBuiltInExceptionClass(): void
    {
        $denormalizer = new selfDenormalizer();

        $this->assertFalse($denormalizer->supportsDenormalization([], NotNormalizableValueException::class));
    }

    public function testSupportsDenormalizationReturnsFalseForStringable(): void
    {
        $denormalizer = new selfDenormalizer();

        $this->assertFalse($denormalizer->supportsDenormalization([], StringableStub::class));
    }

    public function testGetSupportedTypesDeclaresBuiltInExclusions(): void
    {
        $denormalizer = new selfDenormalizer();

        $types = $denormalizer->getSupportedTypes(null);

        $this->assertArrayHasKey(DateTimeInterface::class, $types);
        $this->assertFalse($types[DateTimeInterface::class]);
        $this->assertArrayHasKey(UnitEnum::class, $types);
        $this->assertFalse($types[UnitEnum::class]);
        $this->assertArrayHasKey(Stringable::class, $types);
        $this->assertFalse($types[Stringable::class]);
        $this->assertArrayHasKey('object', $types);
        $this->assertTrue($types['object']);
        $this->assertArrayHasKey('*', $types);
        $this->assertFalse($types['*']);
    }
}

final class StringableStub implements Stringable
{
    public function __toString(): string
    {
        return 'stub';
    }
}
