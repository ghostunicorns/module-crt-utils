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
use GhostUnicorns\CrtEntity\Model\EntityModel;
use GhostUnicorns\CrtUtils\Model\DotConvention;
use Magento\Framework\Serialize\Serializer\Json;

class CopyFromOriginalOrNullRefiner implements RefinerInterface
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
    private $source;

    /**
     * @var string
     */
    private $destination;

    private $destinationType;

    /**
     * @param Json $serializer
     * @param DotConvention $dotConvention
     * @param string $source
     * @param string $destination
     */
    public function __construct(
        Json $serializer,
        DotConvention $dotConvention,
        string $source,
        string $destination,
        $destinationType = 'string'
    ) {
        $this->serializer = $serializer;
        $this->dotConvention = $dotConvention;
        $this->source = $source;
        $this->destination = $destination;
        $this->destinationType = $destinationType;
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
        $sourceIdentifier = $this->dotConvention->getFirst($this->source);
        $source = $this->dotConvention->getFromSecondInDotConvention($this->source);

        if (!array_key_exists($sourceIdentifier, $entities)) {
            throw new CrtException(__('Invalid sourceIdentifier for class:%1', self::class));
        }

        $destinationIdentifier = $this->dotConvention->getFirst($this->destination);
        $destination = $this->dotConvention->getFromSecondInDotConvention($this->destination);

        if (!array_key_exists($destinationIdentifier, $entities)) {
            throw new CrtException(__('Invalid destinationIdentifier for class:%1', self::class));
        }

        $entity = $entities[$sourceIdentifier];
        $dataOriginal = $entity->getDataOriginal();
        $dataRefined = $entity->getData(EntityModel::DATA_REFINED) ?? [];

        if ($dataRefined) {
            $dataRefined = $this->serializer->unserialize($dataRefined);
        }

        $dataOriginal = $this->serializer->unserialize($dataOriginal);

        try {
            $value = $this->dotConvention->getValue($dataOriginal, $source);
            switch ($this->destinationType) {
                case 'float':
                    $value = (float)$value;
                    break;
                case 'int':
                    $value = (int)$value;
                    break;
            }
            $this->dotConvention->setValue($dataRefined, $destination, $value);
        } catch(\Exception $exception) {
            $dataRefined[$destination] = null;
        }

        $data = $this->serializer->serialize($dataRefined);
        $entity->setDataRefined($data);
    }
}
