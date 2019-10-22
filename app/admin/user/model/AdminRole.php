<?php

namespace Admin\User\Model;

use MDK\Model;

class AdminRole extends Model
{

	/**
	 * 
	 * @Primary
	 * @Identity
	 * @Column(type="integer", size="10", nullable=false, column="id")
	 */
	public $id;

	/**
	 * 
	 * @Column(type="string", size="255", nullable=true, column="name")
	 */
	public $name;

	/**
	 * Initialize method for model.
	 */
	public function initialize()
	{
	}

	/**
	 * Allows to query a set of records that match the specified conditions
	 *
	 * @param mixed $parameters
	 * @return AdminRole[]|AdminRole
	 */
	public static function find($parameters = null)
	{
		return parent::find($parameters);
	}

	/**
	 * Allows to query the first record that match the specified conditions
	 *
	 * @param mixed $parameters
	 * @return AdminRole
	 */
	public static function findFirst($parameters = null)
	{
		return parent::findFirst($parameters);
	}

}
