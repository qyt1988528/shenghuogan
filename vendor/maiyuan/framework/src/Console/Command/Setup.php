<?php
namespace MDK\Console\Command;

use Common\Model\Package;
use MDK\Package\Manager as PackageManager;
use MDK\Console\AbstractCommand;
use MDK\Console\CommandInterface;
use MDK\Console\ConsoleUtil;
use MDK\Config;
use MDK\Db\Model\Annotations\Initializer as ModelAnnotationsInitializer;
use MDK\Db\Schema;
use Phalcon\DI;

/**
 * Cache command.
 *
 * @CommandName(['setup'])
 * @CommandDescription('Install management.')
 */
class Setup extends AbstractCommand implements CommandInterface
{
    /**
     * System requirements.
     *
     * @var array
     */
    protected $_requirements = [
        'php' => [
            'version' => '7.0.0',
            'title' => 'PHP V7.0'
        ],
        'phalcon' => [
            'version' => '3',
            'title' => "Phalcon V"
        ],
        'zlib' => false,
        'mbstring' => false,
        'mcrypt' => false,
        'iconv' => false,
        'gd' => false,
        'fileinfo' => false,
        'zip' => false,
    ];

    /**
     * Check Install.
     *
     * @return void
     */
    public function checkAction()
    {
        foreach ($this->_requirements as $req => $version) {
            $title = $req;
            if (is_array($version)) {
                $title = $version['title'];
                $version = $version['version'];
            }

            if ($req == 'phalcon') {
                $title .= $version;
            }

            if ($req == 'php') {
                $passed = version_compare(phpversion(), $version, '>=');
            } else {
                $passed = extension_loaded($req);
                $comparison = '>=';
                if ($passed && $version !== false) {
                    $passed = version_compare(phpversion($req), $version, $comparison);
                }
            }
            print ConsoleUtil::commandLine($title , isset($passed) ? 'success' : 'bad', ConsoleUtil::FG_CYAN);
        }
        print PHP_EOL;

    }

    /**
     * Run Install Script to DB.
     *
     * @return void
     */
    public function dbAction(){
        $di = $this->getDI();

        // Install schema.
        $schema = new Schema($di);
        $schema->updateDatabase();

        $packageManager = new PackageManager([], $di);
        foreach ($di->get('registry')->modules as $moduleName) {
            $packageManager->runInstallScript(
                new Config(
                    [
                        'name' => $moduleName,
                        'type' => PackageManager::PACKAGE_TYPE_MODULE,
                        'currentVersion' => '0',
                        'isUpdate' => false
                    ]
                )
            );
        }
        #$this->getDI()->get('app')->config->save('database');
        print ConsoleUtil::success('Database is up to date');
        print PHP_EOL;
    }

    public function runAction(){
        $packageManager = new PackageManager(Package::find());
        $packageManager->generateMetadata();
        print ConsoleUtil::success('Install is finish.');
        print PHP_EOL;
    }
}