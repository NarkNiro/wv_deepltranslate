<?php

declare(strict_types=1);

namespace WebVision\WvDeepltranslate\Domain\Model;

use DateTime;

class Glossary
{
    protected ?int $uid = null;
    protected ?int $pid = null;
    protected string $identifier = '';
    protected string $name = '';
    protected bool $ready  = false;
    protected ?DateTime $lastSync = null;
    protected string $sourceLanguage = '';
    protected string $targetLanguage = '';
    protected array $entries = [];

    public function __construct(
        string $identifier,
        string $name,
        bool $ready,
        string $sourceLanguage,
        string $targetLanguage,
        ?DateTime $lastSync,
        array $entries = []
    ) {
    }

    public static function createFromTableInformation(array $data): self
    {
        $object =  new self(
            $data['glossary_id'],
            $data['glossary_name'],
            $data['glossary_ready'] == 1,
            $data['source_lang'],
            $data['target_lang'],
            (new DateTime())->setTimestamp($data['glossary_last_sync']),
            []
        );

        $object->setUid($data['uid']);
        $object->setPid($data['pid']);

        return $object;
    }

    public function getUid(): ?int
    {
        return $this->uid;
    }

    public function setUid(?int $uid): void
    {
        $this->uid = $uid;
    }

    public function getPid(): ?int
    {
        return $this->pid;
    }

    public function setPid(?int $pid): void
    {
        $this->pid = $pid;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function setIdentifier(string $identifier): void
    {
        $this->identifier = $identifier;
    }

    public function getName(): string
    {
        if ($this->name === '') {
            return sprintf('Glossary %s <> %s', $this->sourceLanguage, $this->targetLanguage);
        }

        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function isReady(): bool
    {
        return $this->ready;
    }

    public function setReady(bool $ready): void
    {
        $this->ready = $ready;
    }

    public function getLastSync(): ?DateTime
    {
        return $this->lastSync;
    }

    public function setLastSync(?DateTime $lastSync): void
    {
        $this->lastSync = $lastSync;
    }

    public function getSourceLanguage(): string
    {
        return $this->sourceLanguage;
    }

    public function setSourceLanguage(string $sourceLanguage): void
    {
        $this->sourceLanguage = $sourceLanguage;
    }

    public function getTargetLanguage(): string
    {
        return $this->targetLanguage;
    }

    public function setTargetLanguage(string $targetLanguage): void
    {
        $this->targetLanguage = $targetLanguage;
    }

    public function getEntries(): array
    {
        return $this->entries;
    }

    public function setEntries(array $entries): void
    {
        $this->entries = $entries;
    }

    public function getEntriesCount(): int
    {
        return count($this->entries);
    }
}
