<?php
%header%
namespace %nameUpper%;

use MDK\Component\Installer as CoreInstaller;

/**
 * Installer for %nameUpper%.
 *
 * @category Maiyuan\Module
 * @package  Module
 */
class Installer extends CoreInstaller
{
    /**
     * Used to install specific database entities or other specific action.
     *
     * @return void
     */
    public function install()
    {

    }

    /**
     * Used before package will be removed from the system.
     *
     * @return void
     */
    public function remove()
    {

    }

    /**
     * Used to apply some updates.
     *
     * @param string $currentVersion Current version name.
     *
     * @return mixed 'string' (new version) if migration is not finished, 'null' if all updates were applied
     */
    public function update($currentVersion)
    {

        return null;
    }
}