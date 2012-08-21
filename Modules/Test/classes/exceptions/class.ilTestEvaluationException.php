<?php

require_once('Modules/Test/classes/exceptions/class.ilTestException.php');

/**
 * Test Evaluation Exception
 *
 * @author		Björn Heyser <bheyser@databay.de>
 * @version		$Id$
 * 
 * @ingroup ModulesTest
 */
class ilTestEvaluationException extends ilTestException
{
	/**
	 * ilTestException Constructor
	 *
	 * @access public
	 * 
	 */
	public function __construct($a_message,$a_code = 0)
	{
	 	parent::__construct($a_message,$a_code);
	}
}
