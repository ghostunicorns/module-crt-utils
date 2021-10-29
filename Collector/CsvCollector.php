<?php
/*
  * Copyright © Ghost Unicorns snc. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtUtils\Collector;

use Exception;
use GhostUnicorns\CrtBase\Api\CollectorInterface;
use GhostUnicorns\CrtBase\Api\CrtConfigInterface;
use GhostUnicorns\CrtBase\Exception\CrtException;
use GhostUnicorns\CrtEntity\Api\Data\EntityInterface;
use GhostUnicorns\CrtEntity\Api\EntityRepositoryInterface;
use GhostUnicorns\CrtEntity\Model\EntityModel;
use GhostUnicorns\CrtEntity\Model\EntityModelFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Serialize\SerializerInterface;
use Monolog\Logger;

class CsvCollector implements CollectorInterface
{
    /**
     * @var int
     */
    protected $ok = 0;

    /**
     * @var int
     */
    protected $ko = 0;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CrtConfigInterface
     */
    private $config;

    /**
     * @var EntityRepositoryInterface
     */
    private $entityRepository;

    /**
     * @var Csv
     */
    private $csv;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var EntityModelFactory
     */
    private $entityModelFactory;

    /**
     * @var string
     */
    private $filePath;

    /**
     * @var string
     */
    private $fileName;

    /**
     * @var string
     */
    private $identifier;

    /**
     * @var array
     */
    private $identifiers;

    /**
     * @param Logger $logger
     * @param CrtConfigInterface $config
     * @param EntityRepositoryInterface $entityRepository
     * @param Csv $csv
     * @param DirectoryList $directoryList
     * @param SerializerInterface $serializer
     * @param EntityModelFactory $entityModelFactory
     * @param string $filePath
     * @param string $fileName
     * @param string $identifier
     * @param array $identifiers
     */
    public function __construct(
        Logger $logger,
        CrtConfigInterface $config,
        EntityRepositoryInterface $entityRepository,
        Csv $csv,
        DirectoryList $directoryList,
        SerializerInterface $serializer,
        EntityModelFactory $entityModelFactory,
        string $filePath,
        string $fileName,
        string $identifier = '',
        array $identifiers = []
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->entityRepository = $entityRepository;
        $this->csv = $csv;
        $this->directoryList = $directoryList;
        $this->serializer = $serializer;
        $this->entityModelFactory = $entityModelFactory;
        $this->fileName = $fileName;
        $this->filePath = $filePath;
        $this->identifiers = $identifiers;
        if ($identifier !== '') {
            array_unshift($this->identifiers, $identifier);
        }
    }

    /**
     * @param int $activityId
     * @param string $collectorType
     * @throws CrtException
     */
    public function execute(int $activityId, string $collectorType): void
    {
        $this->logger->info(__(
            'activityId:%1 ~ Collector ~ collectorType:%2 ~ START',
            $activityId,
            $collectorType
        ));

        try {
            $fileNameWithPath = $this->getFileNameWithPath();
        } catch (FileSystemException $e) {
            throw new CrtException(__(
                'activityId:%1 ~ Collector ~ collectorType:%2 ~ ERROR ~ error:%3',
                $activityId,
                $collectorType,
                $e->getMessage()
            ));
        }

        try {
            $data = $this->csv->getData($fileNameWithPath);
            $rows = $this->getRows($data);
        } catch (Exception $e) {
            $this->logger->error(__(
                'activityId:%1 ~ Collector ~ collectorType:%2 ~ ERROR ~ error:%3',
                $activityId,
                $collectorType,
                $e->getMessage()
            ));
            return;
        }

        $this->ok = 0;
        $this->ko = 0;
        foreach ($rows as $key => $row) {
            try {
                $identifier = $this->getIdentifier($row);

                $dataOriginal = $this->serializer->serialize($row);

                $entity = $this->getOrCreateEntity($activityId, $identifier, $collectorType);

                $entity->setDataOriginal($dataOriginal);

                $this->entityRepository->save($entity);
                $this->ok++;
            } catch (Exception $e) {
                $errorMessage = __(
                    'activityId:%1 ~ Collector ~ collectorType:%2 ~ KO ~ row:%3 ~ error:%4',
                    $activityId,
                    $collectorType,
                    $key + 1,
                    $e->getMessage()
                );
                if ($this->config->continueInCaseOfErrors()) {
                    $this->logger->error($errorMessage);
                    $this->ko++;
                } else {
                    throw new CrtException($errorMessage);
                }
            }
        }

        $this->logger->info(__(
            'activityId:%1 ~ Collector ~ collectorType:%2 ~ okCount:%3 koCount:%4',
            $activityId,
            $collectorType,
            $this->ok,
            $this->ko
        ));
    }

    /**
     * @return string
     * @throws FileSystemException
     */
    private function getFileNameWithPath(): string
    {
        $directory = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        return $directory . $this->filePath . DIRECTORY_SEPARATOR . $this->fileName;
    }

    /**
     * @param array $data
     * @return array
     */
    public function getRows(array $data): array
    {
        $headers = array_shift($data);
        $rows = [];

        foreach ($data as $row) {
            $rows[] = array_combine($headers, $row);
        }
        return $rows;
    }

    /**
     * @param array $row
     * @return string
     * @throws CrtException
     */
    private function getIdentifier(array $row): string
    {
        $result = '';

        foreach ($this->identifiers as $identifier) {
            if (!array_key_exists($identifier, $row)) {
                throw new CrtException(__('Identifier column not found:%1', $identifier));
            }
            $result .= '.' . $row[$identifier];
        }

        $result = ltrim($result, '.');

        if ($result === '') {
            throw new CrtException(__('Invalid identifier:%1', $result));
        }

        return $result;
    }

    /**
     * @param int $activityId
     * @param string $identifier
     * @param string $collectorType
     * @return EntityInterface|EntityModel
     * @throws Exception
     */
    private function getOrCreateEntity(int $activityId, string $identifier, string $collectorType)
    {
        $entity = $this->entityRepository
            ->getByActivityIdAndIdentifierAndType($activityId, $identifier, $collectorType);
        if ($entity->getId()) {
            return $entity;
        }

        $entity = $this->entityModelFactory->create();
        $entity->setActivityId($activityId);
        $entity->setType($collectorType);
        $entity->setIdentifier($identifier);
        return $entity;
    }
}
