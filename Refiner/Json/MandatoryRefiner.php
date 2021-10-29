<?php
/*
  * Copyright © Ghost Unicorns snc. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtUtils\Refiner\Json;

use GhostUnicorns\CrtBase\Api\RefinerInterface;
use GhostUnicorns\CrtBase\Exception\CrtImportantException;
use GhostUnicorns\CrtEntity\Api\Data\EntityInterface;
use GhostUnicorns\CrtUtils\Model\DotConvention;
use Magento\Framework\Exception\LocalizedException;

class MandatoryRefiner implements RefinerInterface
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
     * @throws CrtImportantException
     */
    public function execute(
        int $activityId,
        string $refinerType,
        string $entityIdentifier,
        array $entities
    ): void {
        try {
            $value = $this->dotConvention->getValueFromEntities($entities, $this->field);
        } catch (LocalizedException $e) {
            throw new CrtImportantException(__('The field is missing:%1', $this->field));
        }
        $entity = $this->dotConvention->getEntityFromEntities($entities, $this->field);

        if ($value === '') {
            $entity->skip();
            throw new CrtImportantException(__('The field is mandatory:%1', $this->field));
        }
    }
}
