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

class FloatvalRefiner implements RefinerInterface
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
     * @var bool
     */
    private $mandatory;

    /**
     * @param Json $serializer
     * @param DotConvention $dotConvention
     * @param string $field
     * @param bool $mandatory
     */
    public function __construct(
        Json $serializer,
        DotConvention $dotConvention,
        string $field,
        bool $mandatory = true
    ) {
        $this->serializer = $serializer;
        $this->dotConvention = $dotConvention;
        $this->field = $field;
        $this->mandatory = $mandatory;
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
        $collectorIdentifier = $this->dotConvention->getFirst($this->field);
        $identifiers = $this->dotConvention->getFromSecond($this->field);

        if (!array_key_exists($collectorIdentifier, $entities)) {
            if ($this->mandatory) {
                throw new CrtException(__('Invalid collectorIdentifier for class:%1', self::class));
            } else {
                return;
            }
        }

        $entity = $entities[$collectorIdentifier];
        $data = $entity->getDataRefined();
        $data = $this->serializer->unserialize($data);

        $field = &$data;

        foreach ($identifiers as $identifier) {
            if (!array_key_exists($identifier, $field)) {
                return;
            }
            $field = &$field[$identifier];
        }

        if ($field !== null) {
            $field = floatval($field);
        }

        $data = $this->serializer->serialize($data);
        $entity->setDataRefined($data);
    }
}
