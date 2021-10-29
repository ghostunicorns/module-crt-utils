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

class UrlKeyRefiner implements RefinerInterface
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
     * @throws CrtException|CrtImportantException
     */
    public function execute(
        int $activityId,
        string $refinerType,
        string $entityIdentifier,
        array $entities
    ): void {
        $value = $this->dotConvention->getValueFromEntities($entities, $this->field);
        $entity = $this->dotConvention->getEntityFromEntities($entities, $this->field);

        if ($value !== '' &&
            !preg_match('/^[-a-zA-Z0-9@:%._\+~#=]{1,256}$/', $value)
        ) {
            $entity->skip();
            throw new CrtImportantException(__('Invalid url-key:%1', $value));
        }

        $this->dotConvention->setValueFromEntities($entities, $this->field, $value);
    }
}
