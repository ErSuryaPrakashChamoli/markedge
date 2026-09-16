<?php

namespace App\Seo\Schema;

interface SchemaBuilder
{
    public function supports(SchemaContext $context): bool;

    /**
     * Zero or more JSON-LD nodes built only from data that actually exists.
     *
     * @return array<int, array<string, mixed>>
     */
    public function build(SchemaContext $context): array;
}
