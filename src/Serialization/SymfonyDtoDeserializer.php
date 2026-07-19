<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\Serialization;

use Elrise\Bundle\AppLayerBundle\Contract\DtoDeserializerInterface;
use Elrise\Bundle\AppLayerBundle\Exception\RequestException;
use ReflectionClass;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Throwable;

final readonly class SymfonyDtoDeserializer implements DtoDeserializerInterface
{
    public function __construct(
        private DenormalizerInterface $serializer,
        private ?string $defaultGroup = null,
    ) {
    }

    public function denormalize(array $data, string $type): object
    {
        $context = [];

        if ($this->defaultGroup !== null) {
            $context[AbstractNormalizer::GROUPS] = [$this->defaultGroup];
        }

        try {
            $object = $this->serializer->denormalize($data, $type, null, $context);
        } catch (Throwable $e) {
            throw new RequestException(sprintf('Failed to denormalize DTO "%s": %s', $type, $e->getMessage()), ['type' => $type, 'data_keys' => array_keys($data)], 0, $e);
        }

        if (!$object instanceof $type) {
            throw new RequestException(sprintf('Deserializer returned "%s" but "%s" was expected.', $object::class, $type), ['type' => $type, 'actual' => $object::class]);
        }

        return $object;
    }

    /**
     * Builds a default serializer capable of denormalizing both
     * readonly constructor-promoted DTOs and DTOs with typed
     * public/private properties (without constructors).
     *
     * Property-only DTOs are handled via direct reflection assignment
     * (no ReflectionProperty::setAccessible() — deprecated since PHP 8.5).
     */
    public static function createDefault(?string $defaultGroup = null): self
    {
        return new self(new selfDenormalizer(), $defaultGroup);
    }
}

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
        return class_exists($type) || interface_exists($type);
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['object' => true, '*' => false];
    }
}
