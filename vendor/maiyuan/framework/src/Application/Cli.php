<?php
namespace MDK\Application;

use MDK\Application;
use MDK\Console\Command\Assets;
use MDK\Console\CommandsListener;
use MDK\Console\ConsoleUtil;

/**
 * Console class.
 */
class Cli extends Application
{

    public function __construct() {
        unset($this->_bootstrap[array_search('event', $this->_bootstrap)]);
        // Init commands.
        $this->_bootstrap[] = 'commands';
        unset($this->_service[array_search('view', $this->_service)]);
        unset($this->_service[array_search('router', $this->_service)]);
        unset($this->_service[array_search('session', $this->_service)]);
        parent::__construct();
    }

    /**
     * Defined engine commands.
     * @var AbstractCommand[]
     */
    private $_commands = [];

    /**
     * Run application.
     * @return void
     */
    public function run()
    {
        $this->getOutput();
    }

    /**
     * Init commands.
     *
     * @return void
     */
    protected function _initCommands()
    {
        // Get engine commands.
        $this->_getCommandsFrom(
            $this->dir->library('core/Console/Command/*.php'),
            'MDK\Console\Command\\'
        );

        $modules = array_keys($this->getModules());
        // Get modules commands.
        foreach ($modules as $module) {
            $module = strtolower($module);
            $nameModule = ucfirst($module);
            $path = $this->dir->app("{$module}/command/*.php");
            $namespace = $nameModule . '\Command\\';
            $this->_getCommandsFrom($path, $namespace);
        }
    }

    /**
     * Get commands located in directory.
     *
     * @param string $commandsLocation  Commands location path.
     * @param string $commandsNamespace Commands namespace.
     *
     * @return void
     */
    protected function _getCommandsFrom($commandsLocation, $commandsNamespace)
    {
        // Get all file names.
        $files = glob($commandsLocation);
        // Iterate files.
        foreach ($files as $file) {
            $commandClass = $commandsNamespace . ucfirst(basename($file, '.php'));
            $this->_commands[] = new $commandClass($this->getDI());
        }
    }

    /**
     * Handle all data and output result.
     *
     * @throws Exception
     * @return mixed
     */
    public function getOutput()
    {
        print ConsoleUtil::infoLine('', true, 0);
        print ConsoleUtil::infoLine(
            "
****************************************************
*                Commands Manager                  *
****************************************************
             ", false, 1
        );
        print ConsoleUtil::infoLine('', false, 0);

        // Not arguments?
        if (!isset($_SERVER['argv'][1])) {
            $this->printAvailableCommands();
            die();
        }

        // Check if 'help' command was used.
        if ($this->_helpIsRequired()) {
            return;
        }

        // Try to dispatch the command.
        if ($cmd = $this->_getRequiredCommand()) {
            return $cmd->dispatch();
        }

        // Check for alternatives.
        $available = [];
        foreach ($this->_commands as $command) {
            $providedCommands = $command->getCommands();
            foreach ($providedCommands as $command) {
                $soundex = soundex($command);
                if (!isset($available[$soundex])) {
                    $available[$soundex] = [];
                }
                $available[$soundex][] = $command;
            }
        }

        // Show exception with/without alternatives.
        $soundex = soundex($_SERVER['argv'][1]);
        if (isset($available[$soundex])) {
            print ConsoleUtil::warningLine(
                'Command "' . $_SERVER['argv'][1] .
                '" not found. Did you mean: ' . join(' or ', $available[$soundex]) . '?'
            );
            $this->printAvailableCommands();
        } else {
            print ConsoleUtil::warningLine('Command "' . $_SERVER['argv'][1] . '" not found.');
            $this->printAvailableCommands();
        }
    }

    /**
     * Output available commands.
     *
     * @return void
     */
    public function printAvailableCommands()
    {
        print ConsoleUtil::headLine('Available commands:');
        foreach ($this->_commands as $command) {
            print ConsoleUtil::commandLine(join(', ', $command->getCommands()), $command->getDescription());
        }
        print PHP_EOL;
    }

    /**
     * Get required command.
     *
     * @param string|null $input Input from console.
     *
     * @return AbstractCommand|null
     */
    protected function _getRequiredCommand($input = null)
    {
        if (!$input) {
            $input = $_SERVER['argv'][1];
        }

        foreach ($this->_commands as $command) {
            $providedCommands = $command->getCommands();
            if (in_array($input, $providedCommands)) {
                return $command;
            }
        }

        return null;
    }

    /**
     * Check help system.
     *
     * @return bool
     */
    protected function _helpIsRequired()
    {
        if ($_SERVER['argv'][1] != 'help') {
            return false;
        }

        if (empty($_SERVER['argv'][2])) {
            $this->printAvailableCommands();
            return true;
        }

        $command = $this->_getRequiredCommand($_SERVER['argv'][2]);
        if (!$command) {
            print ConsoleUtil::warningLine('Command "' . $_SERVER['argv'][2] . '" not found.');
            return true;
        }

        $command->getHelp((!empty($_SERVER['argv'][3]) ? $_SERVER['argv'][3] : null));
        return true;
    }
}