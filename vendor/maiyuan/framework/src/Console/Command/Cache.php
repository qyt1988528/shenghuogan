<?php
namespace MDK\Console\Command;

use MDK\Console\AbstractCommand;
use MDK\Console\CommandInterface;
use MDK\Console\ConsoleUtil;
use \Phalcon\DI;

/**
 * Cache command.
 *
 * @CommandName(['cache'])
 * @CommandDescription('Cache management.')
 */
class Cache extends AbstractCommand implements CommandInterface
{
    /**
     * Cleanup cache data.
     *
     * @return void
     */
    public function cleanupAction()
    {
        $this->getDI()->get('app')->clearCache();

        print ConsoleUtil::success('Cache successfully removed.') . PHP_EOL;
    }
}