<?php
/* Copyright (c) 1998-2009 ILIAS open source, Extended GPL, see docs/LICENSE */


/**
 * Class ilChatroom
 *
 * @author Jan Posselt <jposselt@databay.de>
 * @version $Id$
 *
 * @ingroup ModulesChatroom
 */
class ilChatroom
{

	private $settings						= array();
	private static $settingsTable			= 'chatroom_settings';
	private static $historyTable			= 'chatroom_history';
	private static $userTable				= 'chatroom_users';
	private static $sessionTable			= 'chatroom_sessions';
	private static $banTable				= 'chatroom_bans';
	private static $privateRoomsTable		= 'chatroom_prooms';
	private static $privateSessionsTable	= 'chatroom_psessions';
	private static $uploadTable				= 'chatroom_uploads';
	private static $privateRoomsAccessTable = 'chatroom_proomaccess';

	/**
	 * Each value of this array describes a setting with the internal type.
	 * The type must be a type wich can be set by the function settype
	 *
	 * @see http://php.net/manual/de/function.settype.php
	 * @var array string => string
	 */
	private $availableSettings		= array(
			'object_id' 				=> 'integer',
			'allow_anonymous' 			=> 'boolean',
			'allow_custom_usernames' 	=> 'boolean',
			'enable_history' 			=> 'boolean',
			'restrict_history' 			=> 'boolean',
			'autogen_usernames' 		=> 'string',
			'allow_private_rooms' 		=> 'integer',
	);
	private $roomId;

	private $object;

	public function getTitle() 
	{
		if( !$this->object ) 
		{
			$this->object = ilObjectFactory::getInstanceByObjId($this->getSetting('object_id'));
		}

		return $this->object->getTitle();
	}

	public function getDescription() {
		if (!$this->object) {
			$this->object = ilObjectFactory::getInstanceByObjId($this->getSetting('object_id'));
		}

		return $this->object->getDescription();
	}

	/**
	 * Returns setting from $this->settings array by given name.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function getSetting($name)
	{
		return $this->settings[$name];
	}

	/**
	 * Sets given name and value as setting into $this->settings array.
	 *
	 * @param string $name
	 * @param mixed $value
	 */
	public function setSetting($name, $value)
	{
		$this->settings[$name] = $value;
	}

	/**
	 * Saves settings using $this->settings
	 */
	public function save()
	{
		$this->saveSettings( $this->settings );
	}

	/**
	 * Inserts entry into historyTable.
	 *
	 * @todo $recipient, $publicMessage speichern
	 *
	 * @global ilDBMySQL $ilDB
	 * @param string $message
	 * @param string $recipient
	 * @param boolean $publicMessage
	 */
	public function addHistoryEntry($message, $recipient = null, $publicMessage = true)
	{
		global $ilDB;

		$ilDB->insert(
		self::$historyTable,
		array(
					'room_id'	=> array('integer', $this->roomId),
					'message'	=> array('text', $message),
					'timestamp' => array('integer', time()),
		)
		);
	}

	/**
	 * Connects user by inserting userdata into userTable.
	 *
	 * Checks if user is already connected by using the given $user object
	 * for selecting the userId from userTable. If no entry is found, matching
	 * userId and roomId, the userdata is inserted into the userTable to
	 * connect the user.
	 *
	 * @global ilDBMySQL $ilDB
	 * @param ilChatroomUser $user
	 * @return boolean
	 */
	public function connectUser(ilChatroomUser $user)
	{
		global $ilDB;

		$userdata = array(
			'login' => $user->getUsername(),
			'id' => $user->getUserId()
		);

		$query = 'SELECT user_id FROM ' . self::$userTable .
				 ' WHERE room_id = %s AND user_id = %s';

		$types	= array('integer', 'integer');
		$values = array($this->roomId, $user->getUserId());

		if( !$ilDB->fetchAssoc( $ilDB->queryF( $query, $types, $values ) ) )
		{
			$ilDB->insert(
			self::$userTable,
			array(
						'room_id'	=> array('integer', $this->roomId),
						'user_id'	=> array('integer', $user->getUserId()),
						'userdata'	=> array('text', json_encode( $userdata )),
						'connected' => array('integer', time()),
			)
			);

			return true;
		}

		return false;
	}

	/**
	 * Returns an array of connected users.
	 *
	 * Returns an array of user objects containing all users having an entry
	 * in userTable, matching the roomId.
	 *
	 * @global ilDBMySQL $ilDB
	 * @return array
	 */
	public function getConnectedUsers()
	{
		global $ilDB;

		$query	= 'SELECT userdata FROM ' . self::$userTable . ' WHERE room_id = %s';
		$types	= array('integer');
		$values = array($this->roomId);
		$rset	= $ilDB->queryF( $query, $types, $values );
		$users	= array();

		while( $row = $ilDB->fetchAssoc( $rset ) )
		{
			$users[] = json_decode( $row['userdata'] );
		}

		return $users;
	}

	/**
	 * Creates userId array by given $user object and calls disconnectUsers
	 * method.
	 *
	 * @param ilObjUser $user
	 */
	public function disconnectUser(ilObjUser $user)
	{
		$this->disconnectUsers( array($user->getId()) );
	}

	/**
	 * Disconnects users by deleting userdata from userTable using given userId array.
	 *
	 * Deletes entrys from userTable, matching roomId and userId if existing and
	 * inserts userdata and disconnection time into sessionTable.
	 *
	 * @global ilDB $ilDB
	 * @param array $userIds
	 */
	public function disconnectUsers(array $userIds)
	{
		global $ilDB;

		$query = 'SELECT * FROM ' . self::$userTable . ' WHERE room_id = %s AND ' .
		$ilDB->in( 'user_id', $userIds, false, 'integer' );

		$types	= array('integer');
		$values = array($this->roomId);
		$rset	= $ilDB->queryF( $query, $types, $values );

		if( $row = $ilDB->fetchAssoc( $rset ) )
		{
			$query = 'SELECT proom_id FROM ' . self::$privateRoomsTable . ' WHERE parent_id = %s';
			$rset_prooms = $ilDB->queryF($query, array('integer'), array($this->roomId));

			$prooms = array();

			while($row_prooms  = $ilDB->fetchAssoc($rset_prooms)) {
				$prooms[] = $row_prooms['proom_id'];
			}

			if ($this->getSetting( 'enable_history' )) {
				$query = 'UPDATE ' . self::$privateSessionsTable . ' SET disconnected = %s WHERE ' . $ilDB->in('user_id', $userIds, false, 'integer') . ' AND ' . $ilDB->in('proom_id', $prooms, false, 'integer');
				$ilDB->manipulateF($query, array('integer'), array(time()));
			}
			else {
				$query = 'DELETE FROM ' . self::$privateSessionsTable . ' WHERE ' . $ilDB->in('user_id', $userIds, false, 'integer') . ' AND ' . $ilDB->in('proom_id', $prooms, false, 'integer');
				$ilDB->manipulate($query);
			}

			$query = 'DELETE FROM ' . self::$userTable . ' WHERE room_id = %s AND ' .
			$ilDB->in( 'user_id', $userIds, false, 'integer' );
				
			$types	= array('integer');
			$values = array($this->roomId);
			$ilDB->manipulateF( $query, $types, $values );

			do
			{
				if ($this->getSetting( 'enable_history' )) {
					$ilDB->insert(
					self::$sessionTable,
					array(
								'room_id'		=> array('integer', $this->roomId),
								'user_id'		=> array('integer', $row['user_id']),
								'userdata'		=> array('text', $row['userdata']),
								'connected'		=> array('integer', $row['connected']),
								'disconnected'	=> array('integer', time()),
					)
					);
				}
			}
			while( $row = $ilDB->fetchAssoc( $rset ) );
		}

	}

	private function phpTypeToMDBType($type) {
		switch($type) {
			case 'string':
				return 'text';
			default:
				return $type;
		}

	}

	/**
	 * Saves settings into settingsTable using given settings array.
	 *
	 * @global ilDBMySQL $ilDB
	 * @param array $settings
	 */
	public function saveSettings(array $settings)
	{
		global $ilDB;

		$localSettings = array();

		foreach( $this->availableSettings as $setting => $type )
		{
			if( isset( $settings[$setting] ) ) {
				$localSettings[$setting] = array($this->phpTypeToMDBType($type), $settings[$setting]);
			}
		}

		if( $this->roomId )
		{
			$ilDB->update(
			self::$settingsTable,
			$localSettings,
			array( 'room_id' => array('integer', $this->roomId) )
			);
		}
		else
		{
			$this->roomId = $ilDB->nextId( self::$settingsTable );

			$localSettings['room_id'] = array(
			$this->availableSettings['room_id'], $this->roomId
			);

			$ilDB->insert( self::$settingsTable, $localSettings );
		}
	}

	/**
	 * Returns $this->settings array.
	 *
	 * @return array
	 */
	public function getSettings()
	{
		return $this->settings;
	}

	/**
	 * Returns ilChatroom object by given $object_id.
	 *
	 * @global ilDBMySQL $ilDB
	 * @param integer $object_id
	 * @return ilChatroom
	 */
	public static function byObjectId($object_id)
	{
		global $ilDB;
		//var_dump($object_id);
		$query	= 'SELECT * FROM ' . self::$settingsTable . ' WHERE object_id = %s';
		$types	= array('integer');
		$values = array($object_id);
		$rset	= $ilDB->queryF( $query, $types, $values );

		if( $row = $ilDB->fetchAssoc( $rset ) )
		{
			$room = new self();
			$room->initialize( $row );
			return $room;
		}
	}

	/**
	 * Returns ilChatroom by given $room_id
	 *
	 * @global ilDBMySQL $ilDB
	 * @param integer $room_id
	 * @return ilChatroom
	 */
	public static function byRoomId($room_id)
	{
		global $ilDB;

		$query = 'SELECT * FROM ' . self::$settingsTable . ' WHERE room_id = %s';

		$types = array('integer');
		$values = array($room_id);

		$rset = $ilDB->queryF( $query, $types, $values );

		if( $row = $ilDB->fetchAssoc( $rset ) )
		{
			$room = new self();
			$room->initialize( $row );
			return $room;
		}
	}

	/**
	 * Sets $this->roomId by given array $rowdata and calls setSetting method
	 * foreach available setting in $this->availableSettings.
	 *
	 * @param array $rowdata
	 */
	public function initialize(array $rowdata)
	{
		$this->roomId = $rowdata['room_id'];

		foreach( $this->availableSettings as $setting => $type )
		{
			if( isset($rowdata[$setting]) )
			{
				settype($rowdata[$setting], $this->availableSettings[$setting]);
				$this->setSetting( $setting, $rowdata[$setting] );
			}
		}
	}

	/**
	 * Returns roomID from $this->roomId
	 *
	 * @return integer
	 */
	public function getRoomId()
	{
		return $this->roomId;
	}

	/**
	 * Returns true if entry exists in userTable matching given $chat_userid
	 * and $this->roomId.
	 *
	 * @global ilDBMySQL $ilDB
	 * @param integer $chat_userid
	 * @return boolean
	 */
	public function isSubscribed($chat_userid)
	{
		global $ilDB;

		$query = 'SELECT count(user_id) as cnt FROM ' . self::$userTable .
				 ' WHERE room_id = %s AND user_id = %s';

		$types	= array('integer', 'integer');
		$values = array($this->roomId, $chat_userid);
		$rset	= $ilDB->queryF( $query, $types, $values );

		if( $rset && ($row = $ilDB->fetchAssoc( $rset )) && $row['cnt'] == 1 )
		return true;

		return false;
	}

	public function isAllowedToEnterPrivateRoom($chat_userid, $proom_id) {
		//echo call_user_func_array('sprintf', array_merge(array($query), $values));
		global $ilDB;

		$query = 'SELECT count(user_id) cnt FROM ' . self::$privateRoomsAccessTable .
				 ' WHERE proom_id = %s AND user_id = %s';

		$types	= array('integer', 'integer');
		$values = array($proom_id, $chat_userid);
		$rset	= $ilDB->queryF( $query, $types, $values );

		if( $rset && ($row = $ilDB->fetchAssoc( $rset )) && $row['cnt'] == 1 )
		return true;

		$query = 'SELECT count(*) cnt FROM ' . self::$privateRoomsTable .
				 ' WHERE proom_id = %s AND owner = %s';

		$types	= array('integer', 'integer');
		$values = array($proom_id, $chat_userid);
		$rset	= $ilDB->queryF( $query, $types, $values );

		if( $rset && ($row = $ilDB->fetchAssoc( $rset )) && $row['cnt'] == 1 )
		return true;

		return false;
	}

	/**
	 * Deletes all entrys from userTable.
	 *
	 * @global ilDBMySQL $ilDB
	 */
	public function disconnectAllUsersFromAllRooms()
	{
		global $ilDB;

		$ilDB->manipulate( 'DELETE FROM ' . self::$userTable );
		$ilDB->manipulate( 'UPDATE ' . self::$privateRoomsTable . ' SET closed = ' . time() . ' WHERE closed = 0 OR closed IS NULL');
		$ilDB->manipulate( 'UPDATE ' . self::$privateSessionsTable . ' SET disconnected = ' . time() . ' WHERE disconnected = 0 OR disconnected IS NULL');
		/**
		 * @todo nicht nur löschen, auch in Session Tabelle nachpflegen
		 */
	}

	/**
	 * Returns array containing history data selected from historyTable by given
	 * ilDateTime, $restricted_session_userid and matching roomId.
	 *
	 * @global ilDBMySQL $ilDB
	 * @param ilDateTime $from
	 * @param ilDateTime $to
	 * @param integer $restricted_session_userid
	 * @return array
	 */
	public function getHistory(ilDateTime $from = null, ilDateTime $to = null, $restricted_session_userid = null)
	{
		global $ilDB;

		$join = '';

		if( !is_null( $restricted_session_userid ) )
		{
			$join = ' INNER JOIN ' . self::$sessionTable .
					' sessionTable ON sessionTable.room_id = historyTable.room_id AND user_id = ' .
			$ilDB->quote( $restricted_session_userid, 'integer' ) .
					' AND timestamp >= connected AND timestamp <= disconnected ';
		}

		$query = 'SELECT historyTable.* FROM ' . self::$historyTable . ' historyTable ' .
		$join . ' WHERE historyTable.room_id = ' . $this->getRoomId();

		$filter = array();

		if( $from != null )
		{
			$filter[] = 'timestamp >= ' . $ilDB->quote( $from->getUnixTime(), 'integer' );
		}

		if( $to != null )
		{
			$filter[] = 'timestamp <= ' . $ilDB->quote( $to->getUnixTime(), 'integer' );
		}

		if( $filter )
		$query .= ' AND ' . join( ' AND ', $filter );

		$rset	= $ilDB->query( $query );
		$result = array();

		while( $row = $ilDB->fetchAssoc( $rset ) )
		{
			$row['message'] = json_decode( $row['message'] );
			$row['message']->timestamp = $row['timestamp'];
			$result[] = $row;
		}
		return $result;
	}

	/**
	 * Saves information about file uploads in DB.
	 *
	 * @global ilDBMySQL $ilDB
	 * @param integer $user_id
	 * @param string $filename
	 * @param string $type
	 */
	public function saveFileUploadToDb($user_id, $filename, $type)
	{
		global $ilDB;

		$upload_id	= $ilDB->nextId( self::$uploadTable );

		$ilDB->insert(
		self::$uploadTable,
		array(
					'upload_id'	=> array('integer', $upload_id),
					'room_id'	=> array('integer', $this->roomId),
					'user_id'	=> array('integer', $user_id),
					'filename'	=> array('text', $filename),
					'filetype'	=> array('text', $type),
					'timestamp'	=> array('integer', time())
		)
		);
	}

	/**
	 * Inserts user into banTable, using given $user_id
	 *
	 * @global ilDBMySQL $ilDB
	 * @param integer $user_id
	 * @param string $comment
	 */
	public function banUser($user_id, $comment = '')
	{
		global $ilDB;

		$ilDB->insert(
		self::$banTable,
		array(
					'room_id'	=> array('integer', $this->roomId),
					'user_id'	=> array('integer', $user_id),
					'timestamp' => array('integer', time()),
					'remark'	=> array('text', $comment),
		)
		);
	}

	/**
	 * Deletes entry from banTable matching roomId and given $user_id and
	 * returns true if sucessful.
	 *
	 * @global ilDBMySQL $ilDB
	 * @param mixed $user_id
	 * @return boolean
	 */
	public function unbanUser($user_id)
	{
		global $ilDB;

		if( !is_array( $user_id ) )
		$user_id = array($user_id);

		$query = 'DELETE FROM ' . self::$banTable . ' WHERE room_id = %s AND ' .
		$ilDB->in( 'user_id', $user_id, false, 'integer' );

		$types	= array('integer');
		$values = array($this->getRoomId());

		return $ilDB->manipulateF( $query, $types, $values );
	}

	/**
	 * Returns true if there's an entry in banTable matching roomId and given
	 * $user_id
	 *
	 * @global ilDBMySQL $ilDB
	 * @param integer $user_id
	 * @return boolean
	 */
	public function isUserBanned($user_id)
	{
		global $ilDB;

		$query = 'SELECT count(user_id) cnt FROM ' . self::$banTable .
				' WHERE user_id = %s AND room_id = %s';

		$types	= array('integer', 'integer');
		$values = array($user_id, $this->getRoomId());

		$rset = $ilDB->queryF( $query, $types, $values );

		if( $rset && ($row = $ilDB->fetchAssoc( $rset )) && $row['cnt'] )
		return true;

		return false;
	}

	/**
	 * Returns an multidimensional array containing userdata from users
	 * having an entry in banTable with matching roomId.
	 *
	 * @global ilDBMySQL $ilDB
	 * @return array
	 */
	public function getBannedUsers()
	{
		global $ilDB;

		$query	= 'SELECT * FROM ' . self::$banTable . ' WHERE room_id = %s ';
		$types	= array('integer');
		$values = array($this->getRoomId());
		$rset	= $ilDB->queryF( $query, $types, $values );
		$result = array();

		if( $rset )
		{
			while( $row = $ilDB->fetchAssoc( $rset ) )
			{
				if( $row['user_id'] > 0 )
				{
					$user = new ilObjUser( $row['user_id'] );
					$userdata = array(
						'user_id'	=> $user->getId(),
						'firstname' => $user->getFirstname(),
						'lastname'	=> $user->getLastname(),
						'login'		=> $user->getLogin(),
						'remark'	=> $row['remark']
					);

					$result[] = $userdata;
				}
				else
				{
					//@todo anonymous user
				}
			}
		}

		return $result;
	}

	/**
	 * Returns last session from user.
	 *
	 * Returns row from sessionTable where user_id matches userId from given
	 * $user object.
	 *
	 * @global ilDBMySQL $ilDB
	 * @param ilChatroomUser $user
	 * @return array
	 */
	public function getLastSession(ilChatroomUser $user)
	{
		global $ilDB;

		$query = 'SELECT * FROM ' . self::$sessionTable . ' WHERE user_id = ' .
		$ilDB->quote( $user->getUserId(), 'integer' ) .
				 ' ORDER BY connected DESC';

		$ilDB->setLimit( 1 );
		$rset = $ilDB->query( $query );

		if( $row = $ilDB->fetchAssoc( $rset ) )
		{
			return $row;
		}
	}

	/**
	 * Returns all session from user
	 *
	 * Returns all from sessionTable where user_id matches userId from given
	 * $user object.
	 *
	 * @global ilDBMySQL $ilDB
	 * @param ilChatroomUser $user
	 * @return array
	 */
	public function getSessions(ilChatroomUser $user)
	{
		global $ilDB;

		$query = 'SELECT * FROM ' . self::$sessionTable . ' WHERE user_id = ' .
		$ilDB->quote( $user->getUserId(), 'integer' ) . ' AND room_id = '.
		$ilDB->quote( $this->getRoomId(), 'integer' ) .
				 ' ORDER BY connected DESC';

		$rset = $ilDB->query( $query );

		$result = array();

		while( $row = $ilDB->fetchAssoc( $rset ) )
		{
			$result[] = $row;
		}

		return $result;
	}

	public function addPrivateRoom($title, ilChatroomUser $owner, $settings)
	{
		global $ilDB;

		$nextId = $ilDB->nextId('chatroom_prooms');

		$ilDB->insert(
		self::$privateRoomsTable,
		array(
					'proom_id'	=> array('integer', $nextId),
					'parent_id'	=> array('integer', $this->roomId),
					'title'	=> array('text', $title),
					'owner'	=> array('integer', $owner->getUserId()),
					'created' => array('integer', time()),
					'is_public' => array('integer', $settings['public']),
		)
		);

		return $nextId;
	}

	public function closePrivateRoom($id)
	{
		global $ilDB;

		$ilDB->manipulateF(
			'UPDATE ' . self::$privateRoomsTable . ' SET closed = %s WHERE proom_id = %s',
		array('integer', 'integer'),
		array(time(), $id)
		);
	}

	public function isOwnerOfPrivateRoom($user_id, $proom_id) {
		global $ilDB;

		$query = 'SELECT proom_id FROM ' . self::$privateRoomsTable . ' WHERE proom_id = %s AND owner = %s';
		$types = array('integer', 'integer');
		$values = array($proom_id, $user_id);

		$rset = $ilDB->queryF($query, $types, $values);

		if ($rset && $ilDB->fetchAssoc($rset)) {
			return true;
		}
		return false;
	}

	public function inviteUserToPrivateRoom($user_id, $proom_id) {
		global $ilDB;

		$query = 'DELETE FROM ' . self::$privateRoomsAccessTable . ' WHERE user_id = %s AND proom_id = %s';
		$types = array('integer', 'integer');
		$values = array($user_id, $proom_id);

		$ilDB->manipulateF($query, $types, $values);

		$ilDB->insert(self::$privateRoomsAccessTable, array(
		    'user_id' => array('integer', $user_id),
		    'proom_id' => array('integer', $proom_id)
		));

		$ilDB->insert(self::$privateSessionsTable, array(
		    'user_id' => array('integer', $user_id),
		    'proom_id' => array('integer', $proom_id),
		    'connected' => array('integer', time()),
		    'disconnected' => array('integer', 0),
		));
	}

	/**
	 *
	 * @global ilCtrl $ilCtrl
	 * @param <type> $gui
	 * @param <type> $scope_id
	 */
	public function getChatURL($gui, $scope_id = 0) {
		global $ilCtrl;

		if ($scope_id) {
			$ilCtrl->setParameter($gui, 'sub', $scope_id);
		}

		$link = ilUtil::_getHttpPath() . '/'. $ilCtrl->getLinkTarget($gui, 'view', '', false, false);

		$ilCtrl->clearParameters($gui);

		return $link;
	}

	public function sendInvitationNotification($gui, $sender_id, $recipient_id, $subScope = 0) {

		$invitationLink = $this->getChatURL($gui, $subScope);;

		if ($sender_id > 0 && $recipient_id > 0 && !in_array( ANONYMOUS_USER_ID, array($sender_id, $recipient_id) )) {

			$sender = ilObjectFactory::getInstanceByObjId($sender_id);

			$bodyParams = array(
			    'link' => $invitationLink,
			    'inviter_name' => $sender->getPublicName(),
			    'room_name' => $this->getTitle()
			);

			if ($subScope) {
				$bodyParams['room_name'] .= ' - ' . self::lookupPrivateRoomTitle($subScope);
			}

			require_once 'Services/Notifications/classes/class.ilNotificationConfig.php';
			$notification = new ilNotificationConfig('chat_invitation');
			$notification->setTitleVar('chat_invitation',$bodyParams, 'chatroom');
			$notification->setShortDescriptionVar('chat_invitation_short',$bodyParams,'chatroom');
			$notification->setLongDescriptionVar('chat_invitation_long',$bodyParams,'chatroom');
			$notification->setAutoDisable(false);
			$notification->setLink($invitationLink);
			$notification->setIconPath('templates/default/images/icon_chtr_s.gif');
			$notification->setValidForSeconds(0);

			$notification->setHandlerParam('mail.sender', $sender_id);
				
			$notification->notifyByUsers(array($recipient_id));
		}
	}



	public function inviteUserToPrivateRoomByLogin($login, $proom_id) {
		global $ilDB;
		$user_id = ilObjUser::_lookupId($login);
		$this->inviteUserToPrivateRoom($user_id, $proom_id);
	}

	public static function lookupPrivateRoomTitle($proom_id) {
		global $ilDB;

		$query = 'SELECT title FROM ' . self::$privateRoomsTable . ' WHERE proom_id = %s';
		$types = array('integer');
		$values = array($proom_id);

		$rset = $ilDB->queryF($query, $types, $values);

		if ($row = $ilDB->fetchAssoc($rset)) {
			return $row['title'];
		}

		return 'unkown';
	}

	public function getActivePrivateRooms($userid)
	{
		global $ilDB;

		$query	= '
			SELECT roomtable.title, roomtable.proom_id, accesstable.user_id id, roomtable.owner owner FROM ' . self::$privateRoomsTable . ' roomtable
			LEFT JOIN '.self::$privateRoomsAccessTable.' accesstable ON roomtable.proom_id = accesstable.proom_id AND accesstable.user_id = %s
			WHERE parent_id = %s AND (closed = 0 OR closed IS NULL) AND (accesstable.user_id IS NOT NULL OR roomtable.owner = %s)';
		$types	= array('integer', 'integer', 'integer');
		$values = array($userid, $this->roomId, $userid);
		$rset	= $ilDB->queryF( $query, $types, $values );
		$rooms = array();

		while( $row = $ilDB->fetchAssoc( $rset ) )
		{
			$row['active_users'] = $this->listUsersInPrivateRoom($row['id']);
			$row['owner'] = $row['owner'];
			$rooms[$row['proom_id']] = $row;
		}

		return $rooms;
	}

	public function listUsersInPrivateRoom($private_room_id) {
		global $ilDB;

		$query	= 'SELECT user_id FROM ' . self::$privateSessionsTable . ' WHERE proom_id = %s AND disconnected = 0 OR disconnected IS NULL';
		$types	= array('integer');
		$values = array($private_room_id);
		$rset	= $ilDB->queryF( $query, $types, $values );

		$users = array();

		while ($row = $ilDB->fetchAssoc($rset)) {
			$users[] = $row['user_id'];
		}

		return $users;
	}

	public function userIsInPrivateRoom($room_id, $user_id)
	{
		global $ilDB;

		$query	= 'SELECT proom_id id FROM ' . self::$privateSessionsTable . ' WHERE user_id = %s AND proom_id = %s AND disconnected = 0 OR disconnected IS NULL';
		$types	= array('integer', 'integer');
		$values = array($user_id, $room_id);
		$rset	= $ilDB->queryF( $query, $types, $values );
		if ($ilDB->fetchAssoc($rset))
		return true;
		return false;
	}

	public function subscribeUserToPrivateRoom($room_id, $user_id)
	{
		global $ilDB;

		if (!$this->userIsInPrivateRoom($room_id, $user_id)) {
			$ilDB->insert(
			self::$privateSessionsTable,
			array(
                            'proom_id' => array('integer', $room_id),
                            'user_id' => array('integer', $user_id),
                            'connected' => array('integer', time()),
                            'disconnected' => array('integer', 0),
			)
			);
		}
	}

	/**
	 *
	 * @global ilDB $ilDB
	 * @param integer $room_id
	 * @param integer $user_id
	 */
	public function unsubscribeUserFromPrivateRoom($room_id, $user_id)
	{
		global $ilDB;

		$ilDB->update(
		self::$privateSessionsTable,
		array(
			'disconnected' => array('integer', time())
		),
		array(
			'proom_id' => array('integer', $room_id),
			'user_id' => array('integer', $user_id),
		)
		);
	}

	public function countActiveUsers() {
		global $ilDB;

		$query = 'SELECT count(user_id) as cnt FROM ' . self::$userTable .
				 ' WHERE room_id = %s';

		$types	= array('integer');
		$values = array($this->roomId);
		$rset	= $ilDB->queryF( $query, $types, $values );

		if( $rset && ($row = $ilDB->fetchAssoc( $rset )) && $row['cnt'] == 1 )
		return $row['cnt'];

		return 0;
	}

	public function getUniquePrivateRoomTitle($title) {
		global $ilDB;

		$query = 'SELECT title FROM ' . self::$privateRoomsTable . ' WHERE parent_id = %s and closed = 0';
		$rset = $ilDB->queryF($query, array('integer'), array($this->roomId));

		$titles = array();

		while($row = $ilDB->fetchAssoc($rset)) {
			$titles[] = $row['title'];
		}

		$suffix = '';
		$i = 0;
		do {
			if(!in_array($title . $suffix, $titles)) {
				$title .= $suffix;
				break;
			}

			++$i;

			$suffix = ' (' . $i . ')';
		} while(true);

		return $title;
	}

	public static function findDeletablePrivateRooms() {
		global $ilDB;

		$query = 'SELECT private_rooms.proom_id id, MIN(disconnected) min_disconnected, MAX(disconnected) max_disconnected FROM ' . self::$privateSessionsTable . ' private_sessions INNER JOIN '.self::$privateRoomsTable.' private_rooms ON private_sessions.proom_id = private_rooms.proom_id WHERE closed = 0 GROUP BY private_rooms.proom_id HAVING min_disconnected > 0 AND max_disconnected < %s';
		$rset = $ilDB->queryF(
		$query,
		array('integer'),
		array(time() + 60 * 5)
		);

		$rooms = array();

		while ($row = $ilDB->fetchAssoc($rset)) {
			$rooms[] = $row['id'];
		}

		$query = 'SELECT DISTINCT proom_id, room_id, object_id FROM ' . self::$privateRoomsTable
		. ' INNER JOIN ' . self::$settingsTable . ' ON parent_id = room_id '
		. ' WHERE ' . $ilDB->in('proom_id', $rooms, false, 'integer');

		$rset = $ilDB->query($query);
		$rooms = array();
		while($row = $ilDB->fetchAssoc($rset)) {
			$rooms[] = array(
                    'proom_id' => $row['proom_id'],
                    'room_id' => $row['room_id'],
                    'object_id' => $row['object_id']
			);
		}

		return $rooms;
	}
}

?>