<?php

declare(strict_types=1);

namespace WebVision\WvDeepltranslate\Service;

use DeepL\GlossaryEntries;
use DeepL\GlossaryInfo;
use Doctrine\DBAL\Driver\Exception;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use WebVision\WvDeepltranslate\ClientInterface;
use WebVision\WvDeepltranslate\Domain\Repository\GlossaryRepository;
use WebVision\WvDeepltranslate\Exception\FailedToCreateGlossaryException;
use WebVision\WvDeepltranslate\Exception\GlossaryEntriesNotExistException;
use WebVision\WvDeepltranslate\Factory\GlossaryFactory;

final class DeeplGlossaryService
{
    private ClientInterface $client;

    private GlossaryFactory $glossaryFactory;

    protected GlossaryRepository $glossaryRepository;

    public function __construct(
        ClientInterface $client,
        GlossaryRepository $glossaryRepository,
        GlossaryFactory $glossaryFactory
    ) {
        $this->client = $client;
        $this->glossaryRepository = $glossaryRepository;
        $this->glossaryFactory = $glossaryFactory;
    }

    /**
     * Calls the glossary-Endpoint and return Json-response as an array
     *
     * @return GlossaryInfo[]
     */
    public function listGlossaries(): array
    {
        return $this->client->getAllGlossaries();
    }

    /**
     * Creates a glossary, entries must be formatted as [sourceText => entryText] e.g: ['Hallo' => 'Hello']
     *
     * @param array<int, array{source: string, target: string}> $entries
     *
     * @throws GlossaryEntriesNotExistException
     */
    public function createGlossary(
        string $name,
        array $entries,
        string $sourceLang = 'de',
        string $targetLang = 'en'
    ): GlossaryInfo {
        if (empty($entries)) {
            throw new GlossaryEntriesNotExistException(
                'Glossary Entries are required',
                1677169192
            );
        }

        return $this->client->createGlossary($name, $sourceLang, $targetLang, $entries);
    }

    /**
     * Deletes a glossary
     *
     * @param string $glossaryId
     */
    public function deleteGlossary(string $glossaryId): void
    {
        $this->client->deleteGlossary($glossaryId);
    }

    /**
     * Gets information about a glossary
     */
    public function glossaryInformation(string $glossaryId): ?GlossaryInfo
    {
        return $this->client->getGlossary($glossaryId);
    }

    /**
     * Fetch glossary entries and format them as an associative array [source => target]
     */
    public function glossaryEntries(string $glossaryId): ?GlossaryEntries
    {
        return $this->client->getGlossaryEntries($glossaryId);
    }

    /**
     * @throws Exception
     * @throws SiteNotFoundException
     * @throws \Doctrine\DBAL\Exception
     */
    public function syncGlossaries(int $glossaryModulePageId): void
    {
        $glossaries = $this->glossaryFactory->createGlossaryInformation($glossaryModulePageId);

        if (empty($glossaries)) {
            throw new FailedToCreateGlossaryException(
                'Glossary can not created, the TYPO3 information are invalide.',
                1714987594661
            );
        }

        foreach ($glossaries as $glossaryInformation) {
            // Remove are exist glossary before upgrade the information
            // will only be removed when this glossary status is ready
            if (
                $glossaryInformation['glossary_id'] !== ''
                && $glossaryInformation['glossary_ready'] === 1
            ) {
                $this->deleteGlossary($glossaryInformation['glossary_id']);
            }

            try {
                $glossary = $this->createGlossary(
                    $glossaryInformation['glossary_name'],
                    $glossaryInformation['entries'],
                    $glossaryInformation['source_lang'],
                    $glossaryInformation['target_lang']
                );

                $this->glossaryRepository->updateLocalGlossary(
                    $glossary,
                    (int)$glossaryInformation['uid']
                );
            } catch (GlossaryEntriesNotExistException $exception) {
                // ToDo: Write log error entry
            }
        }
    }
}
