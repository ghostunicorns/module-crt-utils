<?php
/*
 * Copyright © Ghost Unicorns snc. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtUtils\Refiner\Json;

use GhostUnicorns\CrtBase\Api\RefinerInterface;
use GhostUnicorns\CrtBase\Exception\CrtImportantException;
use GhostUnicorns\CrtEntity\Api\Data\EntityInterface;
use GhostUnicorns\CrtUtils\Model\DotConvention;
use Magento\Framework\Serialize\Serializer\Json;

class StripUrlBasePathRefiner implements RefinerInterface
{
    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var DotConvention
     */
    private $dotConvention;

    /**
     * @var string
     */
    private $field;

    /**
     * @param Json $serializer
     * @param DotConvention $dotConvention
     * @param string $field
     * @param bool $mandatory
     */
    public function __construct(
        Json $serializer,
        DotConvention $dotConvention,
        string $field
    ) {
        $this->serializer = $serializer;
        $this->dotConvention = $dotConvention;
        $this->field = $field;
    }

    /**
     * @param int $activityId
     * @param string $refinerType
     * @param string $entityIdentifier
     * @param EntityInterface[] $entities
     * @throws CrtImportantException
     */
    public function execute(int $activityId, string $refinerType, string $entityIdentifier, array $entities): void
    {
        $value = $this->dotConvention->getValueFromEntities($entities, $this->field);
        $entity = $this->dotConvention->getEntityFromEntities($entities, $this->field);

        $newValue = parse_url($value, PHP_URL_PATH);

        $this->dotConvention->setValueFromEntities($entities, $this->field, $newValue);
    }
}
