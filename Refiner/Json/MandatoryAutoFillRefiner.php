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
use Magento\Framework\Exception\LocalizedException;

class MandatoryAutoFillRefiner implements RefinerInterface
{
    /**
     * @var DotConvention
     */
    private $dotConvention;

    /**
     * @var string
     */
    private $destination;

    /**
     * @var string
     */
    private $value;

    /**
     * @param DotConvention $dotConvention
     * @param string $destination
     * @param string $value
     */
    public function __construct(
        DotConvention $dotConvention,
        string $destination,
        string $value
    ) {
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
    public function execute(
        int $activityId,
        string $refinerType,
        string $entityIdentifier,
        array $entities
    ): void {
        try {
            $value = $this->dotConvention->getValueFromEntities($entities, $this->destination);
        } catch (LocalizedException $e) {
            $value = '';
        }

        if ($value === '') {
            $this->dotConvention->setValueFromEntities($entities, $this->destination, $this->value);
        }
    }
}
