<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * @author		Björn Heyser <bheyser@databay.de>
 * @version		$Id$
 *
 * @package		Modules/Test
 */
class ilTestRandomQuestionSetSourcePoolDefinitionList implements Iterator
{
	/**
	 * global $ilDB object instance
	 *
	 * @var ilDB
	 */
	protected $db = null;
	
	/**
	 * object instance of current test
	 *
	 * @var ilObjTest
	 */
	protected $testOBJ = null;
	
	/**
	 * @var ilTestRandomQuestionSetSourcePoolDefinition[]
	 */
	private $sourcePoolDefinitions = array();

	/**
	 * @var ilTestRandomQuestionSetSourcePoolDefinitionFactory
	 */
	private $sourcePoolDefinitionFactory = null;
	
	/**
	 * Constructor
	 * 
	 * @param ilDB $db
	 * @param ilObjTest $testOBJ
	 */
	public function __construct(ilDB $db, ilObjTest $testOBJ, ilTestRandomQuestionSetSourcePoolDefinitionFactory $sourcePoolDefinitionFactory)
	{
		$this->db = $db;
		$this->testOBJ = $testOBJ;
		$this->sourcePoolDefinitionFactory = $sourcePoolDefinitionFactory;
	}
	
	
	public function loadDefinitions()
	{
		$query = "SELECT * FROM tst_rnd_quest_set_qpls WHERE test_fi = %s";
		$res = $this->db->queryF($query, array('integer'), array($this->testOBJ->getTestId()));

		while( $row = $this->db->fetchAssoc($res) )
		{
			$sourcePoolDefinition = $this->sourcePoolDefinitionFactory->getEmptySourcePoolDefinition();

			$sourcePoolDefinition->initFromArray($row);

			$this->sourcePoolDefinitions[ $sourcePoolDefinition->getId() ] = $sourcePoolDefinition;
		}
	}
	
	public function saveDefinitions()
	{
		foreach($this as $sourcePoolDefinition)
		{
			$sourcePoolDefinition->saveToDb();
		}
	}

	public function reindexPositions()
	{
		$positionIndex = array();

		foreach($this as $definition)
		{
			/** @var ilTestRandomQuestionSetSourcePoolDefinition $definition */
			$positionIndex[ $definition->getId() ] = $definition->getSequencePosition();
		}

		sort($positionIndex);

		$i = 1;

		foreach($positionIndex as $definitionId => $definitionPosition)
		{
			$index[$definitionId] = $i++;
		}

		foreach($this as $definition)
		{
			$definition->setSequencePosition( $positionIndex[$definition->getId()] );
		}
	}
	
	public function getNextPosition()
	{
		return ( count($this->sourcePoolDefinitions) + 1 );
	}

	/**
	 * @return ilTestRandomQuestionSetSourcePoolDefinition
	 */
	public function rewind()
	{
		return reset($this->sourcePoolDefinitions);
	}

	/**
	 * @return ilTestRandomQuestionSetSourcePoolDefinition
	 */
	public function current()
	{
		return current($this->sourcePoolDefinitions);
	}

	/**
	 * @return integer
	 */
	public function key()
	{
		return key($this->sourcePoolDefinitions);
	}

	/**
	 * @return ilTestRandomQuestionSetSourcePoolDefinition
	 */
	public function next()
	{
		return next($this->sourcePoolDefinitions);
	}

	/**
	 * @return boolean
	 */
	public function valid()
	{
		return key($this->sourcePoolDefinitions) !== null;
	}
}