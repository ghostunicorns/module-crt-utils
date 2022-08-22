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
use Magento\Framework\Serialize\Serializer\Json;

class FixedNullValueRefiner implements RefinerInterface
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
    private $destination;

    /**
     * @var mixed
     */
    private $value;

    /**
     * @param Json $serializer
     * @param DotConvention $dotConvention
     * @param string $destination
     * @param $value
     */
    public function __construct(
        Json $serializer,
        DotConvention $dotConvention,
        string $destination,
        $value
    ) {
        $this->serializer = $serializer;
        $this->dotConvention = $dotConvention;
        $this->destination = $destination;
        $this->value = $value;
    }

    /**
     * @param int $activityId
     * @param string $refinerType
     * @param string $entityIdentifier
     * @param EntityInterface[] $entities
     * @throws CrtException
     */
    public function execute(int $activityId, string $refinerType, string $entityIdentifier, array $entities): void
    {
        $collectorIdentifier = $this->dotConvention->getFirst($this->destination);
        $identifiers = $this->dotConvention->getFromSecondInDotConvention($this->destination);

        if (!array_key_exists($collectorIdentifier, $entities)) {
            throw new CrtException(__('Invalid collectorIdentifier for class:%1', self::class));
        }

        $entity = $entities[$collectorIdentifier];
        $data = $entity->getDataRefined();
        $data = $this->serializer->unserialize($data);

        $this->dotConvention->setValue($data, $identifiers, null);

        $data = $this->serializer->serialize($data);
        $entity->setDataRefined($data);
    }
}
