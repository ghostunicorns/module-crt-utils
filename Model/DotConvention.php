<?php
/*
  * Copyright © Ghost Unicorns snc. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtUtils\Model;

use GhostUnicorns\CrtBase\Exception\CrtException;
use GhostUnicorns\CrtEntity\Api\Data\EntityInterface;
use Magento\Framework\Serialize\Serializer\Json;

class DotConvention
{
    /**
     * @var Json
     */
    private $serializer;

    /**
     * @param Json $serializer
     */
    public function __construct(
        Json $serializer
    ) {
        $this->serializer = $serializer;
    }

    /**
     * @return mixed
     * @throws CrtException
     */
    public function getValueFromEntities(array $entities, string $fullPath)
    {
        $entity = $this->getEntityFromEntities($entities, $fullPath);
        $data = $entity->getDataRefined();
        $data = $this->serializer->unserialize($data);
        return $this->getValueFromSecond($data, $fullPath);
    }

    /**
     * @param EntityInterface[] $entities
     * @param string $fullPath
     * @return EntityInterface
     */
    public function getEntityFromEntities(array $entities, string $fullPath): EntityInterface
    {
        $first = $this->getFirst($fullPath);
        return $entities[$first];
    }

    /**
     * @param string $path
     * @return string
     */
    public function getFirst(string $path): string
    {
        $values = $this->getAll($path);

        return (string)array_shift($values);
    }

    /**
     * @param string $path
     * @return string[]
     */
    public function getAll(string $path): array
    {
        return explode('.', $path);
    }

    /**
     * @param array $data
     * @param string $fullPath
     * @return mixed
     * @throws CrtException
     */
    public function getValueFromSecond(array $data, string $fullPath)
    {
        $identifiers = $this->getFromSecond($fullPath);

        $value = $data;

        foreach ($identifiers as $identifier) {
            if (!array_key_exists($identifier, $value)) {
                $serializeData = $this->serializer->serialize($data);
                throw new CrtException(__('Invalid identifier/path: '.
                    '%1 into provided data: %2', $fullPath, $serializeData));
            }
            $value = $value[$identifier];
        }

        return $value;
    }

    /**
     * @param string $fullPath
     * @return array
     */
    public function getFromSecond(string $fullPath): array
    {
        $values = $this->getAll($fullPath);

        array_shift($values);

        return $values;
    }

    /**
     * @param array $entities
     * @param string $fullPath
     * @param $value
     * @throws CrtException
     */
    public function setValueFromEntities(array $entities, string $fullPath, $value): void
    {
        $entity = $this->getEntityFromEntities($entities, $fullPath);
        $data = $entity->getDataRefined();
        $data = $this->serializer->unserialize($data);
        $path = $this->getFromSecondInDotConvention($fullPath);
        $this->setValue($data, $path, $value);
        $dataSerialized = $this->serializer->serialize($data);
        $entity->setDataRefined($dataSerialized);
    }

    /**
     * @param array $data
     * @param string $path
     * @param $value
     * @throws CrtException
     */
    public function setValue(array &$data, string $path, $value): void
    {
        $field = &$data;

        $identifiers = explode('.', $path);

        foreach ($identifiers as $key => $identifier) {
            if ($identifier === '*' && is_array($field)) {
                $subFields = &$field;
                $remainingIdentifiers = array_slice($identifiers, array_search($identifier, $identifiers) + 1);
                $remainingIdentifiers = $this->serialize($remainingIdentifiers);
                foreach ($subFields as &$subField) {
                    $this->setValue($subField, $remainingIdentifiers, $value);
                }
                return;
            }

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
    }

    /**
     * @param array $values
     * @return string
     */
    public function serialize(array $values): string
    {
        return implode('.', $values);
    }

    /**
     * @param array $data
     * @param string $path
     * @return mixed
     * @throws CrtException
     */
    public function getValue(array $data, string $path)
    {
        $identifier = $this->getFirst($path);

        if (!array_key_exists($identifier, $data)) {
            $serializeData = $this->serializer->serialize($data);
            throw new CrtException(
                __('Invalid identifier/path: %1 into provided data: %2', $path, $serializeData)
            );
        }

        $value = $data[$identifier];

        $identifiers = $this->getFromSecond($path);

        foreach ($identifiers as $identifier) {
            if (!array_key_exists($identifier, $value)) {
                $serializeData = $this->serializer->serialize($data);
                throw new CrtException(__('Invalid identifier/path: %1 into provided data: %2', $path, $serializeData));
            }
            $value = $value[$identifier];
        }

        return $value;
    }

    /**
     * @param string $fullPath
     * @return string
     */
    public function getFromSecondInDotConvention(string $fullPath): string
    {
        $values = $this->getAll($fullPath);

        array_shift($values);

        return implode('.', $values);
    }
}
