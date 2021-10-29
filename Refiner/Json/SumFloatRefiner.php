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

class SumFloatRefiner implements RefinerInterface
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
     * @var string[]
     */
    private $paths;

    /**
     * @param Json $serializer
     * @param DotConvention $dotConvention
     * @param string $destination
     * @param string[] $paths
     */
    public function __construct(
        Json $serializer,
        DotConvention $dotConvention,
        string $destination,
        array $paths
    ) {
        $this->serializer = $serializer;
        $this->dotConvention = $dotConvention;
        $this->destination = $destination;
        $this->paths = $paths;
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

        $entity = $entities[$collectorIdentifier];
        $data = $entity->getDataRefined();
        $data = $this->serializer->unserialize($data);

        if (!array_key_exists($collectorIdentifier, $entities)) {
            throw new CrtException(__('Invalid collectorIdentifier for class ', self::class));
        }

        $value = $this->getValue($entities);
        $destination = $this->dotConvention->getFromSecondInDotConvention($this->destination);
        $this->dotConvention->setValue($data, $destination, $value);

        $data = $this->serializer->serialize($data);
        $entity->setDataRefined($data);
    }

    /**
     * @param array $entities
     * @return float
     * @throws CrtException
     */
    private function getValue(array $entities): float
    {
        $value = 0.0;

        foreach ($this->paths as $path) {
            $collectorIdentifier = $this->dotConvention->getFirst($path);
            $entity = $entities[$collectorIdentifier];
            $data = $entity->getDataRefined();
            $data = $this->serializer->unserialize($data);

            if (!array_key_exists($collectorIdentifier, $entities)) {
                throw new CrtException(__('Invalid collectorIdentifier for class ', self::class));
            }

            $pathIdentifier = $this->dotConvention->getFromSecondInDotConvention($path);
            $pathValue = $this->dotConvention->getValue($data, $pathIdentifier);
            $value += (float)$pathValue;
        }

        return $value;
    }
}
