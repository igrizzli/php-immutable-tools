<?php

namespace Vlx\Immutable;

trait ImmutableData
{
    /**
     * @param array<string, mixed> $values
     */
    public function with(...$values): self
    {
        return new self(...array_merge((array) $this, $values));
    }
}
