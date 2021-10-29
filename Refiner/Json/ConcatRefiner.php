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

class ConcatRefiner implements RefinerInterface
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
     * @var string
     */
    private $glue;

    /**
     * @param Json $serializer
     * @param DotConvention $dotConvention
     * @param string $destination
     * @param string[] $paths
     * @param string $glue
     */
    public function __construct(
        Json $serializer,
        DotConvention $dotConvention,
        string $destination,
        array $paths,
        string $glue
    ) {
        $this->serializer = $serializer;
        $this->dotConvention = $dotConvention;
        $this->destination = $destination;
        $this->paths = $paths;
        $this->glue = $glue;
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
        $value = $this->getValue($entities);

        $collectorIdentifier = $this->dotConvention->getFirst($this->destination);

        $entity = $entities[$collectorIdentifier];
        $data = $entity->getDataRefined();
        $data = $this->serializer->unserialize($data);

        if (!array_key_exists($collectorIdentifier, $entities)) {
            throw new CrtException(__('Invalid collectorIdentifier for class ', self::class));
        }

        $field = &$data;

        $identifiers = $this->dotConvention->getFromSecond($this->destination);
        foreach ($identifiers as $key => $identifier) {
            if (!array_key_exists($identifier, $field)) {
                if ($key < (count($identifiers) - 1)) {
                    $field[$identifier] = [];
                } else {
                    $field[$identifier] = '';
                }
            }
            $field = &$field[$identifier];
        }

        $field = $value;

        $data = $this->serializer->serialize($data);
        $entity->setDataRefined($data);
    }

    /**
     * @param array $entities
     * @return string
     * @throws CrtException
     */
    private function getValue(array $entities): string
    {
        $value = '';

        foreach ($this->paths as $path) {
            $collectorIdentifier = $this->dotConvention->getFirst($path);
            $entity = $entities[$collectorIdentifier];
            $data = $entity->getDataRefined();
            $data = $this->serializer->unserialize($data);

            if (!array_key_exists($collectorIdentifier, $entities)) {
                throw new CrtException(__('Invalid collectorIdentifier for class ', self::class));
            }

            $identifiers = $this->dotConvention->getFromSecond($path);

            $pathValue = $data;
            foreach ($identifiers as $identifier) {
                if (!array_key_exists($identifier, $pathValue)) {
                    throw new CrtException(__('Non existing identifier %1', $path));
                }
                $pathValue = $pathValue[$identifier];
            }
            $value .= $pathValue . $this->glue;
        }

        return (string)substr($value, 0, -strlen($this->glue));
    }
}
