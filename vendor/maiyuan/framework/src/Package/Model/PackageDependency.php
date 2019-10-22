<?php
namespace MDK\Package\Model;

use MDK\Db\Model;

/**
 * Abstract package dependency.
 */
abstract class PackageDependency extends Model
{
    /**
     * @Primary
     * @Identity
     * @Column(type="integer", nullable=false, column="id", size="11")
     */
    public $id;

    /**
     * @Column(type="integer", nullable=false, column="package_id", size="11")
     */
    public $package_id;

    /**
     * @Column(type="integer", nullable=false, column="dependency_id", size="11")
     */
    public $dependency_id;

    /**
     * Get related package.
     *
     * @param array $arguments Arguments.
     *
     * @return Package
     */
    public function getDependencyPackage($arguments = [])
    {
        return $this->getRelated('Dependency', $arguments);
    }

    /**
     * Get package.
     *
     * @param array $arguments Arguments.
     *
     * @return Package
     */
    public function getPackage($arguments = [])
    {
        return $this->getRelated('Common\Model\Package', $arguments);
    }
}
