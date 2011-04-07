<?php
/* Copyright (c) 1998-2010 ILIAS open source, Extended GPL, see docs/LICENSE */

include_once ('./Services/Object/classes/class.ilObject2.php');

/**
* Verification object base class
*
* @author Jörg Lützenkirchen <luetzenkirchen@leifos.com>
*
* @version $Id$
*
* @ingroup ServicesVerification
*/
abstract class ilVerificationObject extends ilObject2
{
	protected $map = array();
	protected $properties = array();

	const TYPE_STRING = 1;
	const TYPE_BOOL = 2;
	const TYPE_INT = 3;
	const TYPE_DATE = 4;
	const TYPE_RAW = 5;
	const TYPE_ARRAY = 6;

	function __construct($a_id = 0, $a_reference = true)
	{
		parent::__construct($a_id, $a_reference);
		$this->map = $this->getPropertyMap();
	}


	/**
	 * Return property map (name => type)
	 *
	 * @return array
	 */
	abstract protected function getPropertyMap();

	/**
	 * Check if given property is valid
	 *
	 * @param string $a_name
	 * @return bool
	 */
	public function hasProperty($a_name)
	{
		return array_key_exists($a_name, $this->map);
	}

	/**
	 * Get property data type
	 *
	 * @param string $a_name
	 * @return string
	 */
	public function getPropertyType($a_name)
	{
		if($this->hasProperty($a_name))
		{
			return $this->properties[$a_name];
		}
	}

	/**
	 * Get property value
	 *
	 * @param string $a_name
	 * @return mixed
	 */
	public function getProperty($a_name)
	{
		if($this->hasProperty($a_name))
		{
			return $this->properties[$a_name];
		}
	}

	/**
	 * Set property value
	 *
	 * @param string $a_name
	 * @return mixed
	 */
	public function setProperty($a_name, $a_value)
	{
		if($this->hasProperty($a_name))
		{
			$this->properties[$a_name] = $a_value;
		}
	}

	/**
	 * Import property from database
	 *
	 * @param string $a_type
	 * @param array $a_data
	 * @param string $a_raw_data
	 */
	protected function importProperty($a_type, array $a_data = null, $a_raw_data = null)
	{
		$data_type = $this->getPropertyType($a_type);
		if($data_type)
		{
			$value = null;
			
			switch($data_type)
			{
				case self::TYPE_STRING:
					$value = (string)$a_data;
					break;

				case self::TYPE_BOOL:
					$value = (bool)$a_data;
					break;

				case self::TYPE_INT:
					$value = (int)$a_data;
					break;

				case self::TYPE_DATE:
					$value = new ilDate($a_data, IL_CAL_DATE);
					break;

				case self::TYPE_ARRAY:
					if($a_data)
					{
						$value = unserialize($a_data);
					}
					break;

				case self::TYPE_RAW:
					$value = $a_raw_data;
					break;
			}

			$this->setProperty($a_type, $value);
		}
	}

	/**
	 * Export property to database
	 *
	 * @return array(parameters, raw_data)
	 */
	protected function exportProperty($a_name)
	{
		$data_type = $this->getPropertyType($a_type);
		if($data_type)
		{
			$value = $this->getProperty($name);
			$raw_data = null;

			switch($data_type)
			{
				case self::TYPE_DATE:
					if($value)
					{
						$value = $value->get(IL_CAL_DATE);
					}
					break;

				case self::TYPE_ARRAY:
					if($value)
					{
						$value = serialize($value);
					}
					break;

				case self::TYPE_RAW:					
					$raw_data = $value;
					$value = null;
					break;
			}

			return array("parameters" => $value,
				"raw_data" => $raw_data);
		}
	}

	/**
	 * Read database entry
	 *
	 * @return bool
	 */
	protected function doRead()
	{
		global $ilDB;

		if($this->id)
		{		
			$set = $ilDB->query("SELECT * FROM il_verification".
				" WHERE id = ".$ilDB->quote($this->id, "integer"));
			if($ilDB->numRows($set))
			{
				while($row = $ilDB->fetchAssoc($set))
				{
					$this->importProperty($row["type"], $row["parameters"],
						$row["raw_data"]);
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * Save current properties to database
	 *
	 * @return bool
	 */
	public function doCreate()
	{
		global $ilDB;

		if($this->id)
		{
			foreach($this->getPropertiesMap() as $name => $type)
			{
				$property = $this->exportProperty($name);
			
				$fields = array("id" => array("integer", $this->id),
					"type" => array("text", $type),
					"parameters" => array("text", $property["parameters"]),
					"raw_data" => array("text", $property["raw_data"]));

				$ilDB->insert("ilVerification", $fields);
			}

			return true;
		}
		return false;
	}

	/**
	 * Delete entry from database
	 *
	 * @return bool
	 */
	public function doDelete()
	{
		global $ilDB;

		if($this->id)
		{
			$ilDB->manipulate("DELETE FROM il_verification".
				" WHERE id = ".$ilDB->quote($this->id, "integer"));
			return true;
		}
		return false;
	}
}

?>