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

class PurifierRefiner implements RefinerInterface
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
    private $identifier;

    /**
     * @var array
     */
    private $fieldsToKeep;

    /**
     * @param Json $serializer
     * @param DotConvention $dotConvention
     * @param string $identifier
     * @param array $fieldsToKeep
     */
    public function __construct(
        Json $serializer,
        DotConvention $dotConvention,
        string $identifier,
        array $fieldsToKeep
    ) {
        $this->serializer = $serializer;
        $this->dotConvention = $dotConvention;
        $this->identifier = $identifier;
        $this->fieldsToKeep = $fieldsToKeep;
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
        if (empty($this->fieldsToKeep)) {
            throw new CrtException(__('Invalid fieldsToKeep for class:%1', self::class));
        }

        if (!array_key_exists($this->identifier, $entities)) {
            throw new CrtException(__('Invalid identifier:%1 for class:%2', $this->identifier, self::class));
        }

        $entity = $entities[$this->identifier];
        $data = $entity->getDataRefined();
        $data = $this->serializer->unserialize($data);

        $purifiedData = [];
        foreach ($this->fieldsToKeep as $field) {
            $purifiedDataPointer = &$purifiedData;
            $identifiers = $this->dotConvention->getAll($field);

            $field = &$data;

            foreach ($identifiers as $identifier) {
                if (!array_key_exists($identifier, $field)) {
                    throw new CrtException(__('Invalid identifier field:%1 for class:%2', $field, self::class));
                }
                $field = &$field[$identifier];
                $purifiedDataPointer[$identifier] = [];
                $purifiedDataPointer = &$purifiedDataPointer[$identifier];
            }
            $purifiedDataPointer = $field;
        }

        $purifiedData = $this->serializer->serialize($purifiedData);
        $entity->setDataRefined($purifiedData);
    }
}
