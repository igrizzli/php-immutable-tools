<?php

namespace Vlx\Immutable\src;

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
