<?php

namespace MDK\Package\Model;

use MDK\Application;
use MDK\Db\Model;
use MDK\Package\Manager;
use Phalcon\DI;
use Phalcon\Mvc\Model\Resultset\Simple;

/**
 * Abstract package.
 */
abstract class Package extends Model
{
    /**
     *
     * @Primary
     * @Identity
     * @Column(type="integer", size="32", nullable=false, column="id")
     */
    public $id;

    /**
     *
     * @Column(type="string", size="64", nullable=false, column="name")
     */
    public $name;

    /**
     *
     * @Column(type="string", size="64", nullable=false, column="type")
     */
    public $type;

    /**
     *
     * @Column(type="string", size="64", nullable=false, column="title")
     */
    public $title;

    /**
     *
     * @Column(type="string", nullable=true, column="description")
     */
    public $description;

    /**
     *
     * @Column(type="string", size="32", nullable=false, column="version")
     */
    public $version;

    /**
     *
     * @Column(type="string", size="255", nullable=true, column="author")
     */
    public $author;

    /**
     *
     * @Column(type="string", size="255", nullable=true, column="website")
     */
    public $website;

    /**
     *
     * @Column(type="integer", size="16", nullable=false, column="enabled")
     */
    public $enabled;

    /**
     *
     * @Column(type="integer", size="16", nullable=false, column="is_system")
     */
    public $is_system;

    /**
     *
     * @Column(type="string", nullable=true, column="data")
     */
    public $data;

    /**
     * Find package by type.
     *
     * @param string      $type    Package type.
     * @param null|bool   $enabled Is enabled.
     * @param null|string $order   Order by field.
     *
     * @return Simple
     */
    public static function findByType($type = Manager::PACKAGE_TYPE_MODULE, $enabled = null, $order = null)
    {
        /** @var \Phalcon\Mvc\Model\Query\Builder $query */
        $query = DI::getDefault()->get('modelsManager')->createBuilder()
            ->from(['t' => 'MDK\Model\Package'])
            ->where("t.type = '{$type}'");

        if ($enabled !== null) {
            $query->andWhere("t.enabled = {$enabled}");
        }

        if ($order !== null) {
            $query->orderBy('t.' . $order);
        }

        return $query->getQuery()->execute();
    }

    /**
     * Get default metadata structure.
     *
     * @return array
     */
    public final function getDefaultMetadata()
    {
        return [
            'type' => $this->type,
            'name' => $this->name,
            'title' => $this->title,
            'description' => $this->description,
            'version' => $this->version,
            'author' => $this->author,
            'website' => $this->website,
            'dependencies' => [
                [
                    'name' => Application::SYSTEM_DEFAULT_MODULE,
                    'type' => Manager::PACKAGE_TYPE_MODULE,
                    'version' => CORE_VERSION,
                ],
            ],
            'events' => [],
            'i18n' => []
        ];
    }

    /**
     * Return the related "AbstractPackageDependency" entity.
     *
     * @param array $arguments Entity params.
     *
     * @return PackageDependency[]
     */
    public function getPackageDependency($arguments = [])
    {
        return $this->getRelated('PackageDependency', $arguments);
    }

    /**
     * Return the related "AbstractPackageDependency" entity.
     *
     * @param array $arguments Entity params.
     *
     * @return PackageDependency[]
     */
    public function getRelatedPackages($arguments = [])
    {
        return $this->getRelated('RelatedPackages', $arguments);
    }

    /**
     * Check if there is some related data.
     *
     * @param string $name Data name.
     *
     * @return bool
     */
    public function hasData($name)
    {
        $data = $this->getData();
        if ($data && isset($data[$name])) {
            return true;
        }

        return false;
    }

    /**
     * Get package data, convert json to array.
     *
     * @param bool $assoc Return as associative array.
     *
     * @return array|null
     */
    public function getData($assoc = true)
    {
        if (is_array($this->data)) {
            return $this->data;
        }

        if (!empty($this->data)) {
            return json_decode($this->data, $assoc);
        }

        return null;
    }

    /**
     * Add additional data to package.
     *
     * @param string $name    Data name.
     * @param mixed  $value   Data value.
     * @param bool   $asArray Add data to array.
     *
     * @return $this
     */
    public function addData($name, $value, $asArray = false)
    {
        if (!is_array($this->data)) {
            $this->data = $this->getData();
        }

        if ($asArray) {
            if (!isset($this->data[$name]) || !is_array($this->data[$name])) {
                $this->data[$name] = [];
            }

            $this->data[$name][] = $value;
        } else {
            $this->data[$name] = $value;
        }

        return $this;
    }

    /**
     * Assign package data.
     *
     * @param array $data Package data.
     * @param array|null $columnMap Column map.
     *
     * @param null $whiteList
     * @return $this
     */
    public function assign(array $data, $columnMap = null, $whiteList = null)
    {
        parent::assign($data, $columnMap, $whiteList);
        if ($data['type'] == Manager::PACKAGE_TYPE_MODULE) {
            $this->data = [
                'events' => (!empty($data['events']) ? $data['events'] : [])
            ];
        }
        if (!empty($data['module'])) {
            $this->addData('module', $data['module']);
        }
        return $this;
    }

    /**
     * Return package as string, package metadata.
     *
     * @param array $params Some additional params.
     *
     * @return string
     */
    abstract public function toJson(array $params = []);

    /**
     * Create package from json string.
     *
     * @param string $content Content data.
     *
     * @return void
     */
    abstract public function fromJson($content);

    /**
     * Logic before save.
     *
     * @return void
     */
    protected function beforeSave()
    {
        if (empty($this->data)) {
            $this->data = null;
        } elseif (is_array($this->data)) {
            $this->data = json_encode($this->data);
        }
    }

    /**
     * Logic before removal.
     *
     * @return void
     */
    protected function beforeDelete()
    {
        $this->getPackageDependency()->delete();
    }
}