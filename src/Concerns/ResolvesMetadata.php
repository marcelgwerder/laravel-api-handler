<?php

namespace Marcelgwerder\ApiHandler\Concerns;

trait ResolvesMetadata
{
    protected function resolveMetadata(Collection $providers)
    {
        return $providers->mapWithKeys(function ($provider) {
            return [$provider->getKey() => $provider->getValue()];
        });
    }
}
