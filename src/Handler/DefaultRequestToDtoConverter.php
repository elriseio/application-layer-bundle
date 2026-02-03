<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\Handler;

use Elrise\Bundle\AppLayerBundle\Contract\DtoDeserializerInterface;
use Elrise\Bundle\AppLayerBundle\Contract\RequestToDtoConverterInterface;
use Symfony\Component\HttpFoundation\Request;

final class DefaultRequestToDtoConverter implements RequestToDtoConverterInterface
{
    public function __construct(
        private DtoDeserializerInterface $deserializer,
    ) {
    }

    public function convert(Request $request, string $dtoFqcn): object
    {
        $data = $this->extractData($request);

        return $this->deserializer->denormalize($data, $dtoFqcn);
    }

    private function extractData(Request $request): array
    {
        if ($request->getContentTypeFormat() === 'json') {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } else {
            $data = [...$request->query->all(), ...$request->request->all()];
        }

        return $data;
    }
}
