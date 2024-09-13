<?php

declare(strict_types=1);

namespace WebVision\WvDeepltranslate\Service\Glossary;

use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use WebVision\WvDeepltranslate\ClientInterface;

class LanguagePairsListProvider
{
    public const GLOSSARY_PAIRS_CACHE_IDENTIFIER = 'wv-deepl-glossary-pairs';

    private FrontendInterface $cache;
    private ClientInterface $client;

    public function __construct(
        FrontendInterface $cache,
        ClientInterface $client
    ) {
        $this->cache = $cache;
        $this->client = $client;
    }

    public function getPossibleGlossaryLanguageConfig(): array
    {
        if (($pairMappingArray = $this->cache->get(self::GLOSSARY_PAIRS_CACHE_IDENTIFIER)) !== false) {
            return $pairMappingArray;
        }

        $possiblePairs = $this->client->getGlossaryLanguagePairs();

        $pairMappingArray = [];
        foreach ($possiblePairs as $possiblePair) {
            $pairMappingArray[$possiblePair->sourceLang][] = $possiblePair->targetLang;
        }

        $this->cache->set(self::GLOSSARY_PAIRS_CACHE_IDENTIFIER, $pairMappingArray);

        return $pairMappingArray;
    }
}
