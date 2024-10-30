<?php

declare(strict_types=1);

namespace WebVision\WvDeepltranslate\Factory;

use DeepL\GlossaryEntries;
use DeepL\GlossaryInfo;
use Doctrine\DBAL\Driver\Exception;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WebVision\WvDeepltranslate\Domain\Model\Glossary;
use WebVision\WvDeepltranslate\Domain\Repository\GlossaryRepository;
use WebVision\WvDeepltranslate\Service\Glossary\LanguagePairsListProvider;

/**
 * Factory object to create DeepL glossary information from TYPO3 Record/Structure
 * Only glossaries language combination that are supported by the DeepL-API through "/glossary-language-pairs" are created
 */
class GlossaryFactory
{
    private SiteFinder $siteFinder;

    private GlossaryRepository $glossaryRepository;
    private LanguagePairsListProvider $languagePairsListGenerator;

    public function __construct(
        SiteFinder $siteFinder,
        GlossaryRepository $glossaryRepository,
        LanguagePairsListProvider $languagePairsListGenerator
    ) {
        $this->siteFinder = $siteFinder;
        $this->glossaryRepository = $glossaryRepository;
        $this->languagePairsListGenerator = $languagePairsListGenerator;
    }

    /**
     * @return array<int, array{
     *     glossary_name: string,
     *     uid: int,
     *     glossary_id: string,
     *     source_lang: string,
     *     target_lang: string,
     *     entries: array<int, array{source: string, target: string}>
     * }>
     *
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     * @throws SiteNotFoundException
     */
    public function createGlossaryInformation(int $pageId): array
    {
        $page = BackendUtility::getRecord('pages', $pageId, '*');
        if ($this->pageRecordIsValidToUseAsGlossary($page) === false) {
            throw new \RuntimeException('Glossary module not found for the given page ID', 1716556217634);
        }

        $localizationArray = [];

        $sourceLangIsoCode = $this->getDefaultLanguageCode($pageId);
        $entries = $this->glossaryRepository->getOriginalEntries($pageId);
        $localizationArray[$sourceLangIsoCode] = $entries;

        $localizationLanguageIds = $this->getAvailableLocalizations($pageId);
        // fetch all language information available for building all glossaries
        foreach ($localizationLanguageIds as $localizationLanguageId) {
            $localizedEntries = $this->glossaryRepository->getLocalizedEntries($pageId, $localizationLanguageId);
            $targetLanguageIsoCode = $this->getTargetLanguageCode($pageId, $localizationLanguageId);
            $localizationArray[$targetLanguageIsoCode] = $localizedEntries;
        }

        $glossaries = [];
        $availableLanguagePairs = $this->languagePairsListGenerator->getPossibleGlossaryLanguageConfig();

        foreach ($availableLanguagePairs as $availableSourceLanguage => $availableTargetLanguages) {
            // no entry to possible source in the current page
            if (!isset($localizationArray[$availableSourceLanguage])) {
                continue;
            }

            foreach ($availableTargetLanguages as $targetLang) {

                // target isn't configured in the current page
                if (!isset($localizationArray[$targetLang])) {
                    continue;
                }

                // target is site default, continue
                if ($targetLang === $sourceLangIsoCode) {
                    continue;
                }

                $glossaryInformation = $this->glossaryRepository->getGlossaryBySourceAndTargetForSync(
                    $availableSourceLanguage,
                    $targetLang,
                    $page
                );
                $glossaryInformation['source_lang'] = $availableSourceLanguage;
                $glossaryInformation['target_lang'] = $targetLang;

                $entries = [];
                foreach ($localizationArray[$availableSourceLanguage] as $entryId => $sourceEntry) {
                    // no source target pair, next
                    if (!isset($localizationArray[$targetLang][$entryId])) {
                        continue;
                    }
                    $entries[] = [
                        'source' => $sourceEntry['term'],
                        'target' => $localizationArray[$targetLang][$entryId]['term'],
                    ];
                }
                // no pairs detected
                if (count($entries) == 0) {
                    continue;
                }

                // remove duplicates
                $sources = [];
                foreach ($entries as $position => $entry) {
                    if (in_array($entry['source'], $sources)) {
                        unset($entries[$position]);
                        continue;
                    }
                    $sources[] = $entry['source'];
                }

                // reset entries keys
                $glossaryInformation['entries'] = array_values($entries);
                $glossaries[] = $glossaryInformation;
            }
        }

        return $glossaries;
    }

    private function pageRecordIsValidToUseAsGlossary(array $pageRecord): bool
    {
        return $pageRecord['module'] === 'glossary' && $pageRecord['doktype'] === PageRepository::DOKTYPE_SYSFOLDER;
    }

    private function createGlossary(string $sourceLanguage, string $targetLanguage, int $pageId): Glossary
    {
        return Glossary::createFromTableInformation([]);
    }

    private function createEntryListForGlossary(): array
    {
        // Select all glossary entries for current glossary
    }

    public function transformTypo3GlossaryObjectToDeeplObject(Glossary $glossary): GlossaryInfo
    {
        return new GlossaryInfo(
            $glossary->getIdentifier(),
            $glossary->getName(),
            $glossary->isReady(),
            $glossary->getSourceLanguage(),
            $glossary->getTargetLanguage(),
            $glossary->getLastSync(),
            $glossary->getEntriesCount()
        );
    }

    public function transformTypo3GlossaryEntriesToDeeplObject(Glossary $glossary): GlossaryEntries
    {
        return GlossaryEntries::fromEntries($glossary->getEntries());
    }

    /**
     * ToDo: maybe move the function to @see \WebVision\WvDeepltranslate\Service\LanguageService
     *
     * @return array<int, mixed>
     */
    private function getAvailableLocalizations(int $pageId): array
    {
        $translations = GeneralUtility::makeInstance(TranslationConfigurationProvider::class)
            ->translationInfo('pages', $pageId);

        // Error string given, if not matching. Return an empty array then
        if (!is_array($translations)) {
            return [];
        }

        $availableTranslations = [];
        foreach ($translations['translations'] as $translation) {
            $availableTranslations[] = $translation['sys_language_uid'];
        }

        return $availableTranslations;
    }

    /**
     * ToDo: maybe move the function to @see \WebVision\WvDeepltranslate\Service\LanguageService
     */
    protected function getTargetLanguageCode(int $pageId, int $languageId): string
    {
        $site = $this->siteFinder->getSiteByPageId($pageId);
        $typo3Version = new Typo3Version();
        if ($typo3Version->getMajorVersion() < 12) {
            $targetLangIsoCode = $site->getLanguageById($languageId)->getTwoLetterIsoCode();
        } else {
            $targetLangIsoCode = $site->getLanguageById($languageId)->getLocale()->getLanguageCode();
        }

        return $targetLangIsoCode;
    }

    /**
     * ToDo: maybe move the function to @see \WebVision\WvDeepltranslate\Service\LanguageService
     */
    private function getDefaultLanguageCode(int $pageId): string
    {
        $site = $this->siteFinder->getSiteByPageId($pageId);
        $typo3Version = new Typo3Version();
        if ($typo3Version->getMajorVersion() < 12) {
            $sourceLangIsoCode = $site->getDefaultLanguage()->getTwoLetterIsoCode();
        } else {
            $sourceLangIsoCode = $site->getDefaultLanguage()->getLocale()->getLanguageCode();
        }
        return $sourceLangIsoCode;
    }

}
