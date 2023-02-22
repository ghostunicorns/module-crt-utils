<?php
/*
  * Copyright © Ghost Unicorns snc. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtUtils\Refiner\Json;

use GhostUnicorns\CrtBase\Api\RefinerInterface;
use GhostUnicorns\CrtBase\Exception\CrtException;
use GhostUnicorns\CrtEntity\Api\Data\EntityInterface;
use GhostUnicorns\CrtUtils\Model\DotConvention;

class StripPrefixRefiner implements RefinerInterface
{
    /**
     * @var DotConvention
     */
    private $dotConvention;

    /**
     * @var string
     */
    private $field;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @param DotConvention $dotConvention
     * @param string $field
     * @param string $prefix
     */
    public function __construct(
        DotConvention $dotConvention,
        string $field,
        string $prefix
    ) {
        $this->dotConvention = $dotConvention;
        $this->field = $field;
        $this->prefix = $prefix;
    }

    /**
     * @param int $activityId
     * @param string $refinerType
     * @param string $entityIdentifier
     * @param EntityInterface[] $entities
     * @throws CrtException
     */
    public function execute(
        int $activityId,
        string $refinerType,
        string $entityIdentifier,
        array $entities
    ): void {
        $value = $this->dotConvention->getValueFromEntities($entities, $this->field);

        if (substr($value, 0, strlen($this->prefix)) == $this->prefix) {
            $value = substr($value, strlen($this->prefix));
        }

        $this->dotConvention->setValueFromEntities($entities, $this->field, $value);
    }
}
