<?php
/*
  * Copyright © Ghost Unicorns snc. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtUtils\Refiner\Json;

use GhostUnicorns\CrtBase\Api\RefinerInterface;
use GhostUnicorns\CrtBase\Exception\CrtException;
use GhostUnicorns\CrtBase\Exception\CrtImportantException;
use GhostUnicorns\CrtEntity\Api\Data\EntityInterface;
use GhostUnicorns\CrtUtils\Model\DotConvention;
use Magento\Framework\Exception\LocalizedException;

class IntvalRefiner implements RefinerInterface
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
     * @var int|null
     */
    private $greaterThan;

    /**
     * @var int|null
     */
    private $lessThan;

    /**
     * @param DotConvention $dotConvention
     * @param string $field
     * @param int|null $greaterThan
     * @param int|null $lessThan
     */
    public function __construct(
        DotConvention $dotConvention,
        string $field,
        int $greaterThan = null,
        int $lessThan = null
    ) {
        $this->dotConvention = $dotConvention;
        $this->field = $field;
        $this->greaterThan = $greaterThan;
        $this->lessThan = $lessThan;
    }

    /**
     * @param int $activityId
     * @param string $refinerType
     * @param string $entityIdentifier
     * @param EntityInterface[] $entities
     * @throws CrtException|LocalizedException
     */
    public function execute(
        int $activityId,
        string $refinerType,
        string $entityIdentifier,
        array $entities
    ): void {
        $value = $this->dotConvention->getValueFromEntities($entities, $this->field);
        $entity = $this->dotConvention->getEntityFromEntities($entities, $this->field);

        $strValue = (string)$value;
        $intValue = (int)$value;

        if ($strValue !== (string)$intValue) {
            $entity->skip();
            throw new CrtImportantException(__('The value is not a numeric value:%1', $strValue));
        }

        if ($this->greaterThan !== null &&
            $intValue < $this->greaterThan
        ) {
            $entity->skip();
            throw new CrtImportantException(__('The value must be greater than:%1', $intValue));
        }

        if ($this->lessThan !== null &&
            $intValue > $this->lessThan
        ) {
            $entity->skip();
            throw new CrtImportantException(__('The value must be less than:%1', $intValue));
        }

        $this->dotConvention->setValueFromEntities($entities, $this->field, $intValue);
    }
}
