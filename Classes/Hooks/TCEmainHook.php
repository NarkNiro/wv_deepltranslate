<?php

declare(strict_types=1);

namespace WebVision\WvDeepltranslate\Hooks;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\DataHandlerCheckModifyAccessListHookInterface;

class TCEmainHook implements DataHandlerCheckModifyAccessListHookInterface
{
    /**
     * Manipulate the hook to make behave the 'localization' index in cmdmap array as a 'pseudo' table
     *
     * @param bool &$accessAllowed Whether the user has access to modify a table
     * @param string $table The name of the table to be modified
     * @param DataHandler $parent The calling parent object
     */
    public function checkModifyAccessList(&$accessAllowed, $table, DataHandler $parent)
    {
        if ($table == 'localization') {
            $accessAllowed = true;
        }
    }
}
