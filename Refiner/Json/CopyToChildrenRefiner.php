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

class CopyToChildrenRefiner implements RefinerInterface
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
    private $childrenOption;

    /**
     * @var string
     */
    private $pathAttributeWhereCopy;

    /**
     * @var string
     */
    private $field;

    /**
     * @param Json $serializer
     * @param DotConvention $dotConvention
     * @param string $childrenOption
     * @param string $pathAttributeWhereCopy
     * @param string $field
     */
    public function __construct(
        Json $serializer,
        DotConvention $dotConvention,
        string $childrenOption,
        string $pathAttributeWhereCopy,
        string $field
    ) {
        $this->serializer = $serializer;
        $this->dotConvention = $dotConvention;
        $this->childrenOption = $childrenOption;
        $this->pathAttributeWhereCopy = $pathAttributeWhereCopy;
        $this->field = $field;
    }

    /**
     * @param int $activityId
     * @param string $refinerType
     * @param string $entityIdentifier
     * @param EntityInterface[] $entities
     * @throws CrtException
     * @throws CrtException
     */
    public function execute(
        int $activityId,
        string $refinerType,
        string $entityIdentifier,
        array $entities
    ): void {
        $collectorIdentifier = $this->dotConvention->getFirst($this->pathAttributeWhereCopy);

        $identifier = $this->dotConvention->getFromSecondInDotConvention($this->pathAttributeWhereCopy);
        if (!array_key_exists($collectorIdentifier, $entities)) {
            throw new CrtException(__('Invalid collectorIdentifier for class:%1', self::class));
        }

        $entity = $entities[$collectorIdentifier];
        $data = $entity->getDataRefined();
        $data = $this->serializer->unserialize($data);

        //for childrens
        $options = $data[$this->childrenOption];
        foreach ($options as $key => $option) {
            if (!isset($data[$this->childrenOption][$key][$this->field])) {

                if (!isset($data[$identifier])) {
                    $value = $this->dotConvention->getValue($data, $identifier);
                } else {
                    $value = $data[$identifier];
                }

                $data[$this->childrenOption][$key][$this->field] = $value;
            }
        }

        $data = $this->serializer->serialize($data);
        $entity->setDataRefined($data);
    }
}
