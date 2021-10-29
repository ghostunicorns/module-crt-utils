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

class BooleanRefiner implements RefinerInterface
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
     * @param DotConvention $dotConvention
     * @param string $field
     */
    public function __construct(
        DotConvention $dotConvention,
        string $field
    ) {
        $this->dotConvention = $dotConvention;
        $this->field = $field;
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

        $values = [
            '1',
            '0',
            'true',
            'false'
        ];

        if (!in_array($strValue, $values)) {
            $entity->skip();
            throw new CrtImportantException(__('The value is not a boolean value:%1', $strValue));
        }

        $boolValue = $value === '1' || $value === 'true';

        $this->dotConvention->setValueFromEntities($entities, $this->field, $boolValue);
    }
}
