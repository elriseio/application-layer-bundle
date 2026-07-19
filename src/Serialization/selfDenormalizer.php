<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\Serialization;

use DateTimeInterface;
use ReflectionClass;
use Stringable;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use UnitEnum;

/**
 * Denormalizer that routes to ObjectNormalizer for classes with a
 * constructor and to direct reflection assignment for property-only
 * DTOs. Property-only assignment relies on PHP 8.1+ semantics:
 * typed properties accept assignment without setAccessible().
 *
 * @internal
 */
final class selfDenormalizer implements DenormalizerInterface
{
    private ObjectNormalizer $constructorDelegate;

    public function __construct()
    {
        $this->constructorDelegate = new ObjectNormalizer();
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (!class_exists($type)) {
            throw new \Symfony\Component\Serializer\Exception\NotNormalizableValueException(sprintf('Class "%s" does not exist.', $type));
        }

        $reflection = new ReflectionClass($type);

        if ($reflection->getConstructor() !== null) {
            return $this->constructorDelegate->denormalize($data, $type, $format, $context);
        }

        if (!\is_array($data)) {
            throw new \Symfony\Component\Serializer\Exception\NotNormalizableValueException(sprintf('Data expected to be an array, "%s" given.', get_debug_type($data)));
        }

        $object = $reflection->newInstanceWithoutConstructor();

        foreach ($data as $field => $value) {
            if (!$reflection->hasProperty($field)) {
                throw new \Symfony\Component\Serializer\Exception\NotNormalizableValueException(sprintf('Property "%s" does not exist on "%s".', $field, $type));
            }

            $property = $reflection->getProperty($field);
            $property->setValue($object, $value);
        }

        return $object;
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        if (!class_exists($type)) {
            return false;
        }

        if (is_subclass_of($type, DateTimeInterface::class)) {
            return false;
        }

        if (is_subclass_of($type, UnitEnum::class)) {
            return false;
        }

        if (is_subclass_of($type, Stringable::class)) {
            return false;
        }

        if (str_starts_with($type, 'Symfony\\Component\\') || str_starts_with($type, 'Doctrine\\')) {
            return false;
        }

        return true;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            DateTimeInterface::class => false,
            UnitEnum::class => false,
            Stringable::class => false,
            'object' => true,
            '*' => false,
        ];
    }
}
