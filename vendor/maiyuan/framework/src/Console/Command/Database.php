<?php
namespace MDK\Console\Command;

use MDK\Console\AbstractCommand;
use MDK\Console\CommandInterface;
use MDK\Console\ConsoleUtil;
use MDK\Db\Schema;
use Phalcon\DI;

/**
 * Database command.
 *
 * @CommandName(['database', 'db'])
 * @CommandDescription('Database management.')
 */
class Database extends AbstractCommand implements CommandInterface
{
    /**
     * Update database schema according to models metadata.
     *
     * @param string|null $model   Model name to update. Example: \Test\Model\Class.
     * @param bool        $cleanup Cleanup database? Drop not related tables.
     *
     * @return void
     */
    public function updateAction($model = null, $cleanup = false)
    {
        $schema = new Schema($this->getDI());
        if ($model) {
            if (!class_exists($model)) {
                print ConsoleUtil::error('Model with class "' . $model . '" doesn\'t exists.') . PHP_EOL;

                return;
            }
            $count = current($schema->updateTable($model));
            if ($count) {
                print ConsoleUtil::headLine('Table update for model: ' . $model);
                print ConsoleUtil::commandLine('Executed queries:', $count, ConsoleUtil::FG_CYAN);
            } else {
                print ConsoleUtil::success('Table is up to date');
            }
            print PHP_EOL;
        } else {
            $queriesCount = $schema->updateDatabase($cleanup);
            if (!empty($queriesCount)) {
                print ConsoleUtil::headLine('Database update:');
                foreach ($queriesCount as $model => $count) {
                    print ConsoleUtil::commandLine($model . ':', $count, ConsoleUtil::FG_CYAN);
                }
            } else {
                print ConsoleUtil::success('Database is up to date');
            }
            print PHP_EOL;
        }
    }
}