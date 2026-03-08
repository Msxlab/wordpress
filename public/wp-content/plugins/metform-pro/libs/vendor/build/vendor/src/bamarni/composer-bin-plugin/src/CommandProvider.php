<?php

namespace MetFormProVendor\Bamarni\Composer\Bin;

use MetFormProVendor\Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
class CommandProvider implements CommandProviderCapability
{
    /**
     * {@inheritDoc}
     */
    public function getCommands()
    {
        return [new BinCommand()];
    }
}
