<?php

namespace Ultraleet\WcErply\Troubleshooters;

use Ultraleet\WcErply\Synchronizers\AbstractSynchronizer;

abstract class AbstractTroubleshooter extends AbstractSynchronizer
{
    const DIRECTION_TROUBLESHOOTER = 'TROUBLESHOOTER';

    public static function getDirection(): string
    {
        return self::DIRECTION_TROUBLESHOOTER;
    }

    public static function isTroubleshooter(): bool
    {
        return true;
    }
}
