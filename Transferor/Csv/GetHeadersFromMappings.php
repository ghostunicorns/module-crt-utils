<?php
/*
  * Copyright © Ghost Unicorns snc. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtUtils\Transferor\Csv;

use GhostUnicorns\CrtUtils\Transferor\Mappings\MappingTypeInterface;

class GetHeadersFromMappings
{
    /**
     * @param MappingTypeInterface[] $mappings
     * @return string[]
     */
    public function execute(array $mappings): array
    {
        $headers = [];
        foreach ($mappings as $mapping) {
            $headers[] = $mapping->getHead();
        }
        return $headers;
    }
}
