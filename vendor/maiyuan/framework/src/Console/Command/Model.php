<?php
namespace MDK\Console\Command;

use MDK\Helper\Utils;
use MDK\Console\Snippet;
use MDK\Console\AbstractCommand;
use MDK\Console\CommandInterface;
use MDK\Console\ConsoleUtil;
use \Phalcon\Db\Column;
use \Phalcon\Validation;
use \Phalcon\Validation\Validator\Namespaces;
use \Phalcon\Db\ReferenceInterface;
use \Phalcon\DI;
use \ReflectionClass;

/**
 * Model command.
 *
 * @CommandName(['model'])
 * @CommandDescription('Model management.')
 */
class Model extends AbstractCommand implements CommandInterface
{

    /**
     * Map of scalar data objects
     * @var array
     */
    private $_typeMap = [
        //'Date' => 'Date',
        //'Decimal' => 'Decimal'
    ];

    /**
     * Test Model.
     *
     * @param string $name   Module name such as Hello.
     * @param string $module   Module name such as Hello.
     *
     * @return void
     */
    public function syncAction($name = null, $module = '', $camelize = false, $force = false, $className = '', $fileName = '', $abstract = false, $annotate = true, $schema = false)
    {
        $options = [];
        if (is_null($name)) {
            $warting[] = 'Please set the parameters:';
            $warting[] = '';
            $warting[] = '  --name Model file name. [required]';
            $warting[] = '  --module=common Own module.';
            $warting[] = '  --schema=public Database mode.';
            $warting[] = '  --force=false Forced generation.';
            $warting = implode(PHP_EOL, $warting);
            print ConsoleUtil::warningLine($warting);
            return;
        }
        $options['name'] = $name;
        $options['module'] = empty($module) ? 'Common' : ucfirst($module);
        $options['camelize'] = $camelize;
        $options['force'] = $force;
        $options['className'] = empty($className) ? Utils::camelize($options['name']) : $className;
        $options['fileName'] = empty($fileName) ? $options['name'] : $fileName;
        $options['abstract'] = $abstract;
        $options['annotate'] = $annotate;
        $options['schemaName'] = $schema;


        if ($options['abstract']) {
            $options['className'] = 'Abstract' . $options['className'];
        }

        $options['namespace'] = $options['module'] . '\\Model';

        $snippet = new Snippet();

        $methodRawCode = [];
        $className = $options['className'];
        $modelPath = $this->dir->app($options['module'] . '/models') . DIRECTORY_SEPARATOR . $className . '.php';

        if (is_file($modelPath) && !$options['force']) {
            print ConsoleUtil::error(sprintf(
                'The model file "%s.php" already exists in models dir',
                $className
            )) . PHP_EOL;
            return;
        }

        $namespace = 'namespace '.$options['namespace'].';'.PHP_EOL.PHP_EOL;

        $genDocMethods = isset($options['genDocMethods']) ? $options['genDocMethods'] : false;
        $useSettersGetters = isset($options['genSettersGetters']) ? $options['genSettersGetters'] : false;

        // An array for use statements
        $uses = [];

        $uses[] = $snippet->getUse(MDK\Model::class);
        //$uses[] = $snippet->getUse(\Phalcon\Mvc\Model\Behavior\Timestampable::class);

        $di = $this->getDI();
        $db = $di->getDb();
        $config = $di->get('config');

        $initialize = [];
        if (!$schema){
            $schema = Utils::resolveDbSchema($config->database);
        }

        if ($schema) {
            $initialize['schema'] = $snippet->getThisMethod('setSchema', $schema);
        }
        $table = $options['name'];
        if ($options['fileName'] != $table && !isset($initialize['schema'])) {
            $initialize[] = $snippet->getThisMethod('setSource', $table);
        }
        if (!$db->tableExists($table, $schema)) {
            print ConsoleUtil::error(sprintf('Table "%s" does not exist.', $table)) . PHP_EOL;
            return;
        }
        $fields = $db->describeColumns($table, $schema);

        foreach ($db->listTables() as $tableName) {
            foreach ($db->describeReferences($tableName, $schema) as $reference) {
                if ($reference->getReferencedTable() != $options['name']) {
                    continue;
                }

                $entityNamespace = '';

                $refColumns = $reference->getReferencedColumns();
                $columns = $reference->getColumns();
                $initialize[] = $snippet->getRelation(
                    'hasMany',
                    $options['camelize'] ? Utils::lowerCamelize($refColumns[0]) : $refColumns[0],
                    $entityNamespace . Utils::camelize($tableName),
                    $options['camelize'] ? Utils::lowerCamelize($columns[0]) : $columns[0],
                    "['alias' => '" . Utils::camelize($tableName) . "']"
                );
            }
        }
        foreach ($db->describeReferences($options['name'], $schema) as $reference) {
            $entityNamespace = '';

            $refColumns = $reference->getReferencedColumns();
            $columns = $reference->getColumns();
            $initialize[] = $snippet->getRelation(
                'belongsTo',
                $options['camelize'] ? Utils::lowerCamelize($columns[0]) : $columns[0],
                $this->getEntityClassName($reference, $entityNamespace),
                $options['camelize'] ? Utils::lowerCamelize($refColumns[0]) : $refColumns[0],
                "['alias' => '" . Utils::camelize($reference->getReferencedTable()) . "']"
            );
        }

        $alreadyInitialized  = false;
        $alreadyValidations  = false;
        $alreadyFind         = false;
        $alreadyFindFirst    = false;
        $alreadyColumnMapped = false;

        if (file_exists($modelPath)) {
            try {
                $possibleMethods = [];
                if ($useSettersGetters) {
                    foreach ($fields as $field) {
                        /** @var \Phalcon\Db\Column $field */
                        $methodName = Utils::camelize($field->getName());

                        $possibleMethods['set' . $methodName] = true;
                        $possibleMethods['get' . $methodName] = true;
                    }
                }

                $possibleMethods['getSource'] = false;

                /** @noinspection PhpIncludeInspection */
                require_once $modelPath;

                $linesCode = file($modelPath);
                $fullClassName = $options['className'];
                $reflection = new ReflectionClass($options['namespace'] . DIRECTORY_SEPARATOR . $fullClassName);

                if (!empty($options['namespace'])) {
                    $fullClassName = $options['namespace'].'\\'.$fullClassName;
                }
                foreach ($reflection->getMethods() as $method) {
                    if ($method->getDeclaringClass()->getName() != $fullClassName) {
                        continue;
                    }

                    $methodName = $method->getName();
                    if (isset($possibleMethods[$methodName])) {
                        continue;
                    }

                    $indent = PHP_EOL;
                    if ($method->getDocComment()) {
                        $firstLine = $linesCode[$method->getStartLine() - 1];
                        preg_match('#^\s+#', $firstLine, $matches);
                        if (isset($matches[0])) {
                            $indent .= $matches[0];
                        }
                    }

                    $methodDeclaration = join(
                        '',
                        array_slice(
                            $linesCode,
                            $method->getStartLine() - 1,
                            $method->getEndLine() - $method->getStartLine() + 1
                        )
                    );

                    $methodRawCode[$methodName] = $indent . $method->getDocComment() . PHP_EOL . $methodDeclaration;

                    switch ($methodName) {
                        case 'initialize':
                            $alreadyInitialized = true;
                            break;
                        case 'validation':
                            $alreadyValidations = true;
                            break;
                        case 'find':
                            $alreadyFind = true;
                            break;
                        case 'findFirst':
                            $alreadyFindFirst = true;
                            break;
                        case 'columnMap':
                            $alreadyColumnMapped = true;
                            break;
                    }
                }
            } catch (\Exception $e) {
                print ConsoleUtil::error(sprintf('Failed to create the model "%s". Error: %s',
                        $options['className'],
                        $e->getMessage()
                    )) . PHP_EOL;
                return;
            }
        }

        $validations = [];
        foreach ($fields as $field) {
            if ($field->getType() === Column::TYPE_CHAR) {
                if ($options['camelize']) {
                    $fieldName = Utils::lowerCamelize($field->getName());
                } else {
                    $fieldName = $field->getName();
                }
                $domain = [];
                if (preg_match('/\((.*)\)/', $field->getType(), $matches)) {
                    foreach (explode(',', $matches[1]) as $item) {
                        $domain[] = $item;
                    }
                }
                if (count($domain)) {
                    $varItems = join(', ', $domain);
                    $validations[] = $snippet->getValidateInclusion($fieldName, $varItems);
                }
            }
        }
        if (count($validations)) {
            $validations[] = $snippet->getValidationEnd();
        }

        // Check if there has been an extender class
        $extends = empty($options['extends']) ? 'Model' : $options['extends'];

        // Check if there have been any excluded fields
        $exclude = [];
        if (!empty($options['excludeFields'])) {
            $keys = explode(',', $options['excludeFields']);
            if (count($keys) > 0) {
                foreach ($keys as $key) {
                    $exclude[trim($key)] = '';
                }
            }
        }

        $attributes = [];
        $setters = [];
        $getters = [];
        foreach ($fields as $field) {
            if (array_key_exists(strtolower($field->getName()), $exclude)) {
                continue;
            }
            $type = $this->getPHPType($field->getType());
            $fieldName = $options['camelize'] ? Utils::lowerCamelize($field->getName()) : $field->getName();
            $attributes[] = $snippet->getAttributes($type, $useSettersGetters ? 'protected' : 'public', $field, $options['annotate'], $fieldName);
            if ($useSettersGetters) {
                $methodName   = Utils::camelize($field->getName());
                $setters[] = $snippet->getSetter($fieldName, $type, $methodName);

                if (isset($this->_typeMap[$type])) {
                    $getters[] = $snippet->getGetterMap($fieldName, $type, $methodName, $this->_typeMap[$type]);
                } else {
                    $getters[] = $snippet->getGetter($fieldName, $type, $methodName);
                }
            }
        }

        $validationsCode = '';
        if ($alreadyValidations == false && count($validations) > 0) {
            $validationsCode = $snippet->getValidationsMethod($validations);
            $uses[] = $snippet->getUse(Validation::class);
        }

        $initCode = '';
        if ($alreadyInitialized == false && count($initialize) > 0) {
            $initCode = $snippet->getInitialize($initialize);
        }

        $license = '';
        if (is_file('license.txt')) {
            $license = trim(file_get_contents('license.txt')) . PHP_EOL . PHP_EOL;
        }

        if (false == $alreadyFind) {
            $methodRawCode[] = $snippet->getModelFind($className);
        }

        if (false == $alreadyFindFirst) {
            $methodRawCode[] = $snippet->getModelFindFirst($className);
        }

        $content = join('', $attributes);

        if ($useSettersGetters) {
            $content .= join('', $setters) . join('', $getters);
        }

        $content .= $validationsCode . $initCode;
        foreach ($methodRawCode as $methodCode) {
            $content .= $methodCode;
        }

        $classDoc = '';
        if ($genDocMethods) {
            $classDoc = $snippet->getClassDoc($className, $namespace);
        }

        if (!empty($options['mapColumn']) && false == $alreadyColumnMapped) {
            $content .= $snippet->getColumnMap($fields, $options['camelize']);
        }

        $useDefinition = '';
        if (!empty($uses)) {
            usort($uses, function ($a, $b) {
                return strlen($a) - strlen($b);
            });

            $useDefinition = join("\n", $uses) . PHP_EOL . PHP_EOL;
        }

        $abstract = ($options['abstract'] ? 'abstract ' : '');

        $code = $snippet->getClass($namespace, $useDefinition, $classDoc, $abstract, $className, $extends, $content, $license);

        if (file_exists($modelPath) && !is_writable($modelPath)) {
            throw new BuilderException(sprintf('Unable to write to %s. Check write-access of a file.', $modelPath));
        }

        if (!file_put_contents(str_replace('\\', DIRECTORY_SEPARATOR, $modelPath), $code)) {
            throw new BuilderException(sprintf('Unable to write to %s', $modelPath));
        }

        print sprintf('Model "%s" was successfully created.', Utils::camelize($options['name'])). PHP_EOL;


    }

    /**
     * Returns the associated PHP type
     *
     * @param  string $type
     * @return string
     */
    public function getPHPType($type)
    {
        switch ($type) {
            case Column::TYPE_INTEGER:
            case Column::TYPE_BIGINTEGER:
                return 'integer';
                break;
            case Column::TYPE_DECIMAL:
            case Column::TYPE_FLOAT:
                return 'double';
                break;
            case Column::TYPE_DATE:
            case Column::TYPE_VARCHAR:
            case Column::TYPE_DATETIME:
            case Column::TYPE_CHAR:
            case Column::TYPE_TEXT:
                return 'string';
                break;
            default:
                return 'string';
                break;
        }
    }

    protected function checkNamespace($namespace)
    {
        $validation = new Validation();

        $validation->add('namespace', new Namespaces([
            'allowEmpty' => true
        ]));

        $messages = $validation->validate(['namespace' => $namespace]);

        if (count($messages)) {
            $errors = [];
            foreach ($messages as $message) {
                $errors[] = $message->getMessage();
            }

            throw new BuilderException(sprintf('%s', implode(PHP_EOL, $errors)));
        }

        return true;
    }

    protected function getEntityClassName(ReferenceInterface $reference, $namespace)
    {
        $referencedTable = Utils::camelize($reference->getReferencedTable());
        $fqcn = "{$namespace}\\{$referencedTable}";

        return $fqcn;
    }

}