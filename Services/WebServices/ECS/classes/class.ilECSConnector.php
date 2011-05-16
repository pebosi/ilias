<?php
/*
	+-----------------------------------------------------------------------------+
	| ILIAS open source                                                           |
	+-----------------------------------------------------------------------------+
	| Copyright (c) 1998-2006 ILIAS open source, University of Cologne            |
	|                                                                             |
	| This program is free software; you can redistribute it and/or               |
	| modify it under the terms of the GNU General Public License                 |
	| as published by the Free Software Foundation; either version 2              |
	| of the License, or (at your option) any later version.                      |
	|                                                                             |
	| This program is distributed in the hope that it will be useful,             |
	| but WITHOUT ANY WARRANTY; without even the implied warranty of              |
	| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
	| GNU General Public License for more details.                                |
	|                                                                             |
	| You should have received a copy of the GNU General Public License           |
	| along with this program; if not, write to the Free Software                 |
	| Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
	+-----------------------------------------------------------------------------+
*/

/** 
* 
* @author Stefan Meyer <meyer@leifos.com>
* @version $Id$
* 
* 
* @ilCtrl_Calls 
* @ingroup ServicesWebServicesECS 
*/

include_once('Services/WebServices/ECS/classes/class.ilECSSetting.php');
include_once('Services/WebServices/ECS/classes/class.ilECSResult.php');
include_once('Services/WebServices/Curl/classes/class.ilCurlConnection.php');

class ilECSConnector
{
	const HTTP_CODE_CREATED = 201;
	const HTTP_CODE_OK = 200;
	const HTTP_CODE_NOT_FOUND = 404;
	
	const HEADER_MEMBERSHIPS = 'X-EcsReceiverMemberships';
	const HEADER_COMMUNITIES = 'X-EcsReceiverCommunities';


	protected $path_postfix = '';
	
	protected $settings;

	protected $header_strings = array();
	
	/**
	 * Constructor
	 *
	 * @access public
	 * @param
	 * 
	 */
	public function __construct()
	{
	 	$this->settings = ilECSSetting::_getInstance();
	}

	// Header methods
	/**
	 * Add Header
	 * @param string $a_name
	 * @param string $a_value
	 */
	public function addHeader($a_name,$a_value)
	{
		$this->header_strings[] = ($a_name.': '.$a_value);
	}

	public function getHeader()
	{
		return (array) $this->header_strings;
	}

	
	///////////////////////////////////////////////////////
	// auths methods 
	///////////////////////////////////////////////////////
	
	/**
	 * Add auth resource
	 *
	 * @access public
	 * @param string post data 
	 * @return int new econtent id
	 * @throws ilECSConnectorException 
	 * 
	 */
	public function addAuth($a_post,$a_target_mid)
	{
		global $ilLog;
		
		$ilLog->write(__METHOD__.': Add new Auth resource...');

	 	$this->path_postfix = '/sys/auths';
	 	
	 	try 
	 	{
	 		$this->prepareConnection();

			$this->addHeader('Content-Type', 'application/json');
			$this->addHeader('Accept', 'application/json');
			$this->addHeader(ilECSConnector::HEADER_MEMBERSHIPS, $a_target_mid);
			#$this->addHeader(ilECSConnector::HEADER_MEMBERSHIPS, 1);

			$this->curl->setOpt(CURLOPT_HTTPHEADER, $this->getHeader());
	 		$this->curl->setOpt(CURLOPT_POST,true);
	 		$this->curl->setOpt(CURLOPT_POSTFIELDS,$a_post);
			$ret = $this->call();

			$info = $this->curl->getInfo(CURLINFO_HTTP_CODE);
	
			$ilLog->write(__METHOD__.': Checking HTTP status...');
			if($info != self::HTTP_CODE_CREATED)
			{
				$ilLog->write(__METHOD__.': Cannot create auth resource, did not receive HTTP 201. ');
				$ilLog->write(__METHOD__.': POST was: '.$a_post);
				$ilLog->write(__METHOD__.': HTTP code: '.$info);
				throw new ilECSConnectorException('Received HTTP status code: '.$info);
			}
			$ilLog->write(__METHOD__.': ... got HTTP 201 (created)');

			$result = new ilECSResult($ret);
			$auth = $result->getResult();

			$ilLog->write(__METHOD__.': ... got hash: '.$auth->hash);

			return $auth->hash;
	 	}
	 	catch(ilCurlConnectionException $exc)
	 	{
	 		throw new ilECSConnectorException('Error calling ECS service: '.$exc->getMessage());
	 	}
	}
	
	/**
	 * get auth resource
	 *
	 * @access public
	 * @param auth hash (transfered via GET)
	 * @throws ilECSConnectorException 
	 */
	public function getAuth($a_hash)
	{
		global $ilLog;
		
		if(!strlen($a_hash))
		{
			$ilLog->write(__METHOD__.': No auth hash given. Aborting.');
			throw new ilECSConnectorException('No auth hash given.');
		}
		
		$this->path_postfix = '/sys/auths/'.$a_hash;

	 	try 
	 	{
	 		$this->prepareConnection();
			$res = $this->call();
			$info = $this->curl->getInfo(CURLINFO_HTTP_CODE);
	
			$ilLog->write(__METHOD__.': Checking HTTP status...');
			if($info != self::HTTP_CODE_OK)
			{
				$ilLog->write(__METHOD__.': Cannot get auth resource, did not receive HTTP 200. ');
				throw new ilECSConnectorException('Received HTTP status code: '.$info);
			}
			$ilLog->write(__METHOD__.': ... got HTTP 200 (ok)');
			return new ilECSResult($res);
	 	}
	 	catch(ilCurlConnectionException $exc)
	 	{
	 		throw new ilECSConnectorException('Error calling ECS service: '.$exc->getMessage());
	 	}
	}
	
	///////////////////////////////////////////////////////
	// eventqueues methods
	///////////////////////////////////////////////////////
	
	/**
	 * get event queue 
	 *
	 * @access public
	 * @throw ilECSConnectorException
	 * @deprecated
	 */
	public function getEventQueues()
	{
		global $ilLog;
		
		$this->path_postfix = '/eventqueues';

	 	try 
	 	{
	 		$this->prepareConnection();
	 		
			$res = $this->call();
			$info = $this->curl->getInfo(CURLINFO_HTTP_CODE);
	
			$ilLog->write(__METHOD__.': Checking HTTP status...');
			if($info != self::HTTP_CODE_OK)
			{
				$ilLog->write(__METHOD__.': Cannot get event queue, did not receive HTTP 200. ');
				throw new ilECSConnectorException('Received HTTP status code: '.$info);
			}
			$ilLog->write(__METHOD__.': ... got HTTP 200 (ok)');			
			return new ilECSResult($res);
	 	}
	 	catch(ilCurlConnectionException $exc)
	 	{
	 		throw new ilECSConnectorException('Error calling ECS service: '.$exc->getMessage());
	 	}
	}

	#######################################################
	# event fifo methods
	#####################################################
	/**
	 * Read event fifo
	 *
	 * @param bool set to true for deleting the current element
	 * @throws ilECSConnectorException
	 */
	public function readEventFifo($a_delete = false)
	{
		$this->path_postfix = '/sys/events/fifo';

		try {
			$this->prepareConnection();
			$this->addHeader('Content-Type', 'application/json');
			$this->addHeader('Accept', 'application/json');

			if($a_delete)
			{
				$this->curl->setOpt(CURLOPT_POST,true);
				$this->curl->setOpt(CURLOPT_POSTFIELDS, '');
			}
			$res = $this->call();

			$result = new ilECSResult($res);
			return $result;
	 	}
	 	catch(ilCurlConnectionException $exc)
	 	{
	 		throw new ilECSConnectorException('Error calling ECS service: '.$exc->getMessage());
	 	}
	}
	
	///////////////////////////////////////////////////////
	// econtents methods
	///////////////////////////////////////////////////////

	public function getResourceList()
	{
		global $ilLog;

		$this->path_postfix = '/campusconnect/courselinks';

		try {
			$this->prepareConnection();
			$this->curl->setOpt(CURLOPT_HTTPHEADER, $this->getHeader());
			$res = $this->call();

			return new ilECSResult($res,false,  ilECSResult::RESULT_TYPE_URL_LIST);

		}
	 	catch(ilCurlConnectionException $exc) {
	 		throw new ilECSConnectorException('Error calling ECS service: '.$exc->getMessage());
	 	}
	}

	
	/**
	 * Get resources from ECS server.
	 *  
	 * 
	 *
	 * @access public
	 * @param int e-content id
	 * @return object ECSResult 
	 * @throws ilECSConnectorException 
	 */
	public function getResource($a_econtent_id, $a_details_only = false)
	{
	 	global $ilLog;
		
		if($a_econtent_id)
		{
			$ilLog->write(__METHOD__.': Get resource with ID: '.$a_econtent_id);
		}
		else
		{
			$ilLog->write(__METHOD__.': Get all resources ...');
		}
	 	
		$this->path_postfix = '/campusconnect/courselinks';
	 	if($a_econtent_id)
	 	{
	 		$this->path_postfix .= ('/'.(int) $a_econtent_id);
	 	}
		if($a_details_only)
		{
			$this->path_postfix .= ('/details');
		}
	 	
	 	try 
	 	{
	 		$this->prepareConnection();
			$res = $this->call();

//print_r($this->curl->getResponseHeaderArray());
			$info = $this->curl->getInfo(CURLINFO_HTTP_CODE);
			
			$result = new ilECSResult($res);
			$result->setHeaders($this->curl->getResponseHeaderArray());
			$result->setHTTPCode($info);
			
			return $result;
	 	}
	 	catch(ilCurlConnectionException $exc)
	 	{
	 		throw new ilECSConnectorException('Error calling ECS service: '.$exc->getMessage());
	 	}
	}
	
	/**
	 * Add resource
	 *
	 * @access public
	 * @param string post data 
	 * @return int new econtent id
	 * @throws ilECSConnectorException 
	 * 
	 */
	public function addResource($a_post)
	{
		global $ilLog;
		
		$ilLog->write(__METHOD__.': Add new EContent...');

	 	$this->path_postfix = '/campusconnect/courselinks';
	 	
	 	try 
	 	{
	 		$this->prepareConnection();

			$this->addHeader('Content-Type', 'application/json');

			$this->curl->setOpt(CURLOPT_HTTPHEADER, $this->getHeader());
	 		$this->curl->setOpt(CURLOPT_HEADER,true);
	 		$this->curl->setOpt(CURLOPT_POST,true);
	 		$this->curl->setOpt(CURLOPT_POSTFIELDS,$a_post);
			$res = $this->call();
			
			$info = $this->curl->getInfo(CURLINFO_HTTP_CODE);
	
			$ilLog->write(__METHOD__.': Checking HTTP status...');
			if($info != self::HTTP_CODE_CREATED)
			{
				$ilLog->write(__METHOD__.': Cannot create econtent, did not receive HTTP 201. ');
				throw new ilECSConnectorException('Received HTTP status code: '.$info);
			}
			$ilLog->write(__METHOD__.': ... got HTTP 201 (created)');			

			include_once('./Services/WebServices/ECS/classes/class.ilECSUtils.php');
			$eid =  ilECSUtils::_fetchEContentIdFromHeader($this->curl->getResponseHeaderArray());
			return $eid;
	 	}
	 	catch(ilCurlConnectionException $exc)
	 	{
	 		throw new ilECSConnectorException('Error calling ECS service: '.$exc->getMessage());
	 	}
	}
	
	/**
	 * update resource
	 *
	 * @access public
	 * @param int econtent id
	 * @param string post content
	 * @throws ilECSConnectorException
	 */
	public function updateResource($a_econtent_id,$a_post_string)
	{
	 	global $ilLog;
		
		$ilLog->write(__METHOD__.': Update resource with id '.$a_econtent_id);

	 	$this->path_postfix = '/campusconnect/courselinks';
	 	
	 	if($a_econtent_id)
	 	{
	 		$this->path_postfix .= ('/'.(int) $a_econtent_id);
	 	}
	 	else
	 	{
	 		throw new ilECSConnectorException('Error calling updateResource: No content id given.');
	 	}
	 	try 
	 	{
			$this->prepareConnection();
			$this->addHeader('Content-Type', 'application/json');
			$this->addHeader('Accept', 'application/json');
			$this->curl->setOpt(CURLOPT_HTTPHEADER, $this->getHeader());
	 		$this->curl->setOpt(CURLOPT_HEADER,true);
	 		$this->curl->setOpt(CURLOPT_PUT,true);

			$tempfile = ilUtil::ilTempnam();
			$ilLog->write(__METHOD__.': Created new tempfile: '.$tempfile);

	 		$fp = fopen($tempfile,'w');
	 		fwrite($fp,$a_post_string);
	 		fclose($fp);
	 		
			$this->curl->setOpt(CURLOPT_UPLOAD,true);
	 		$this->curl->setOpt(CURLOPT_INFILESIZE,filesize($tempfile));
			$fp = fopen($tempfile,'r');
	 		$this->curl->setOpt(CURLOPT_INFILE,$fp);
	 		
			$res = $this->call();
			unlink($tempfile);
			return new ilECSResult($res);
	 	}
	 	catch(ilCurlConnectionException $exc)
	 	{
	 		throw new ilECSConnectorException('Error calling ECS service: '.$exc->getMessage());
	 	}
	}
	
	/**
	 * Delete resource
	 *
	 * @access public
	 * @param string econtent id
	 * @throws ilECSConnectorException 
	 */
	public function deleteResource($a_econtent_id)
	{
	 	global $ilLog;
		
		$ilLog->write(__METHOD__.': Delete resource with id '.$a_econtent_id);

	 	$this->path_postfix = '/campusconnect/courselinks';
	 	
	 	if($a_econtent_id)
	 	{
	 		$this->path_postfix .= ('/'.(int) $a_econtent_id);
	 	}
	 	else
	 	{
	 		throw new ilECSConnectorException('Error calling deleteResource: No content id given.');
	 	}
	
	 	try 
	 	{
	 		$this->prepareConnection();
	 		$this->curl->setOpt(CURLOPT_CUSTOMREQUEST,'DELETE');
			$res = $this->call();
			return new ilECSResult($res);
	 	}
	 	catch(ilCurlConnectionException $exc)
	 	{
	 		throw new ilECSConnectorException('Error calling ECS service: '.$exc->getMessage());
	 	}
	 	
	}
	
	///////////////////////////////////////////////////////
	// membership methods
	///////////////////////////////////////////////////////

	/**
	 * 
	 *
	 * @access public
	 * @param int membership id
	 * @throw ilECSConnectorException
	 */
	public function getMemberships($a_mid = 0)
	{
	 	global $ilLog;
		
		$ilLog->write(__METHOD__.': Get existing memberships');

	 	$this->path_postfix = '/sys/memberships';
	 	if($a_mid)
	 	{
			$ilLog->write(__METHOD__.': Read membership with id: '.$a_mid);
	 		$this->path_postfix .= ('/'.(int) $a_mid);
	 	}
	 	try 
	 	{
	 		$this->prepareConnection();
			$res = $this->call();
			
			return new ilECSResult($res);
	 	}
	 	catch(ilCurlConnectionException $exc)
	 	{
	 		throw new ilECSConnectorException('Error calling ECS service: '.$exc->getMessage());
	 	}
	}

	/**
	 * prepare connection
	 *
	 * @access private
	 * @throws ilCurlConnectionException
	 */
	private function prepareConnection()
	{
	 	try
	 	{
	 		$this->curl = new ilCurlConnection($this->settings->getServerURI().$this->path_postfix);
 			$this->curl->init();
	 		if($this->settings->getProtocol() == ilECSSetting::PROTOCOL_HTTPS)
	 		{
	 			$this->curl->setOpt(CURLOPT_HTTPHEADER,array(0 => 'Accept: application/json'));
	 			$this->curl->setOpt(CURLOPT_SSL_VERIFYPEER,1);
	 			$this->curl->setOpt(CURLOPT_SSL_VERIFYHOST,1);
	 			$this->curl->setOpt(CURLOPT_RETURNTRANSFER,1);
	 			$this->curl->setOpt(CURLOPT_VERBOSE,1);
	 			$this->curl->setOpt(CURLOPT_CAINFO,$this->settings->getCACertPath());
	 			$this->curl->setOpt(CURLOPT_SSLCERT,$this->settings->getClientCertPath());
	 			$this->curl->setOpt(CURLOPT_SSLKEY,$this->settings->getKeyPath());
	 			$this->curl->setOpt(CURLOPT_SSLKEYPASSWD,$this->settings->getKeyPassword());
				
	 		}
	 	}
		catch(ilCurlConnectionException $exc)
		{
			throw($exc);
		}
	}
	
	/**
	 * call peer
	 *
	 * @access private
	 * @throws ilCurlConnectionException 
	 */
	private function call()
	{
 		try
 		{
 			$res = $this->curl->exec();
 			return $res;
 		}	 	
		catch(ilCurlConnectionException $exc)
		{
			throw($exc);
		}
	}
}
?>