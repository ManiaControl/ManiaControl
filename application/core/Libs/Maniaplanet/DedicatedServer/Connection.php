<?php
/**
 * ManiaPlanet dedicated server Xml-RPC client
 *
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 */

namespace Maniaplanet\DedicatedServer;

/**
 * Dedicated Server Connection Instance
 * Methods returns nothing if $multicall = true
 */
class Connection
{
	/**
	 * @var Connection[]
	 */
	static protected $instances = array();

	/**
	 * XML-RPC client instance
	 * @var Xmlrpc\GbxRemote
	 */
	protected $xmlrpcClient;

	/**
	 * @param string $host
	 * @param int $port
	 * @param int $timeout (in ms)
	 * @param string $user
	 * @param string $password
	 * @return Connection
	 */
	static function factory($host = '127.0.0.1', $port = 5000, $timeout = 50, $user = 'SuperAdmin', $password = 'SuperAdmin')
	{
		$key = $host.':'.$port;
		if(!isset(self::$instances[$key]))
		{
			self::$instances[$key] = new self($host, $port, $timeout, $user, $password);
		}
		return self::$instances[$key];
	}

	/**
	 * @param string $host
	 * @param int $port
	 */
	static function delete($host, $port)
	{
		$key = $host.':'.$port;
		if(isset(self::$instances[$key]))
		{
			self::$instances[$key]->terminate();
			unset(self::$instances[$key]);
		}
	}

	/**
	 * Change client timeouts
	 * @param int $read read timeout (in ms), null or 0 to leave unchanged
	 * @param int $write write timeout (in ms), null or 0 to leave unchanged
	 */
	function setTimeouts($read=null, $write=null)
	{
		$this->xmlrpcClient->setTimeouts($read, $write);
	}

	/**
	 * @return int Network idle time in seconds
	 */
	function getIdleTime()
	{
		return $this->xmlrpcClient->getIdleTime();
	}

	/**
	 * @param string $host
	 * @param int $port
	 * @param int $timeout
	 * @param string $user
	 * @param string $password
	 */
	protected function __construct($host, $port, $timeout, $user, $password)
	{
		$this->xmlrpcClient = new Xmlrpc\GbxRemote($host, $port, array('open' => $timeout));
		$this->authenticate($user, $password);
		$this->setApiVersion('2013-04-16');
	}

	/**
	 * Close the current socket connexion
	 * Never call this method, use instead DedicatedApi::delete($host, $port)
	 */
	protected function terminate()
	{
		$this->xmlrpcClient->terminate();
	}

	/**
	 *
	 * Read a Call back on the DedicatedServer and call the method if handle
	 * @param array $methods if empty, every methods will be called on call back, otherwise only the method declared inside. The metho name must be the name of the interface's method
	 */
	function executeCallbacks()
	{
		return $this->xmlrpcClient->getCallbacks();
	}

	/**
	 * Execute the calls in queue and return the result
	 * TODO Prendre en compte les retours du mutliQuery (via un handler ?)
	 */
	function executeMulticall()
	{
		$this->xmlrpcClient->multiquery();
	}

	/**
	 * Add a call in queur. It will be executed by the next Call from the user to executemulticall
	 * @param string $methodName
	 * @param string $authLevel
	 * @param array $params
	 */
	protected function execute($methodName, $params = array(), $multicall = false)
	{
		if($multicall)
		{
			$this->xmlrpcClient->addCall($methodName, $params);
		}
		else
		{
			return $this->xmlrpcClient->query($methodName, $params);
		}
	}

	/**
	 * Given the name of a method, return an array of legal signatures.
	 * Each signature is an array of strings.
	 * The first item of each signature is the return type, and any others items are parameter types.
	 * @param string $methodName
	 * @return array
	 */
	function methodSignature($methodName)
	{
		return $this->execute('system.methodSignature', array($methodName));
	}

	/**
	 * Change the password for the specified login/user.
	 * @param string $username
	 * @param string $password
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function changeAuthPassword($username, $password)
	{
		if(!is_string($password))
		{
			throw new InvalidArgumentException('password = '.print_r($password, true));
		}
		if($username != 'User' && $username != 'Admin' && $username != 'SuperAdmin')
		{
			throw new InvalidArgumentException('username = '.print_r($username, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($username, $password), false);
	}

	/**
	 * Allow the GameServer to call you back.
	 * @param bool $enable
	 * @param bool $multicall
	 * @return bool
	 */
	function enableCallbacks($enable, $multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array((bool) $enable), $multicall);
	}

	/**
	 * Define the wanted api.
	 * @param string $version
	 * @param bool $multicall
	 * @return bool
	 */
	function setApiVersion($version, $multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array((string) $version), $multicall);
	}

	/**
	 * Returns a struct with the Name, Version, Build and ApiVersion of the application remotely controled.
	 * @return Structures\Version
	 * @throws InvalidArgumentException
	 */
	function getVersion()
	{
		$result = $this->execute(ucfirst(__FUNCTION__));
		return Structures\Version::fromArray($result);
	}

	function authenticate($username, $password)
	{
		return $this->execute(ucfirst(__FUNCTION__), array($username, $password), false);
	}

	/**
	 * Call a vote for a cmd. The command is a XML string corresponding to an XmlRpc request.
	 * You can additionally specifiy specific parameters for this vote: a ratio, a time out
	 * and who is voting. Special timeout values: a timeout of '0' means default, '1' means
	 * indefinite; a ratio of '-1' means default; Voters values: '0' means only active players,
	 * '1' means any player, '2' is for everybody, pure spectators included.
	 * @param Structures\Vote $vote
	 * @param double $ratio -1 means default, else ration should be between 0 and 1
	 * @param int $timeout time to vote in millisecondes, '0' means default
	 * @param int $voters Voters values: '0' means only active players, '1' means any player, '2' is for everybody, pure spectators included
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 */
	function callVote(Structures\Vote $vote, $ratio = 0.5, $timeout = 0, $voters = 1, $multicall = false)
	{
		if(is_null($vote))
		{
			throw new InvalidArgumentException('vote must be set');
		}
		if(!is_float($ratio))
		{
			throw new InvalidArgumentException('ratio = '.print_r($ratio, true));
		}
		if(!is_int($timeout))
		{
			throw new InvalidArgumentException('timeout = '.print_r($timeout, true));
		}
		if(!is_int($voters))
		{
			throw new InvalidArgumentException('voters = '.print_r($voters, true));
		}
		if(!is_array($vote->cmdParam))
		{
			throw new InvalidArgumentException('vote->cmdParam = '.print_r($vote->cmdParam, true));
		}

		$tmpCmd = Xmlrpc\Request::encode($vote->cmdName, $vote->cmdName);

		return $this->execute(ucfirst(__FUNCTION__).'Ex', array($tmpCmd, $ratio, $timeout, $voters), $multicall);
	}

	/**
	 * Call a vote to kick a player.
	 * You can additionally specifiy specific parameters for this vote: a ratio, a time out
	 * and who is voting. Special timeout values: a timeout of '0' means default, '1' means
	 * indefinite; a ratio of '-1' means default; Voters values: '0' means only active players,
	 * '1' means any player, '2' is for everybody, pure spectators included.
	 * @param Structures\Player|string $player Structures\Player or string
	 * @param double $ratio -1 means default, else ration should be between 0 and 1
	 * @param int $timeout time to vote in millisecondes, '0' means default
	 * @param int $voters Voters values: '0' means only active players, '1' means any player, '2' is for everybody, pure spectators included
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 */
	function callVoteKick($player, $ratio = 0.5, $timeout = 0, $voters = 1, $multicall = false)
	{
		if(!($login = $this->getLogin($player)))
		{
			throw new InvalidArgumentException('player must be set');
		}
		if(!is_float($ratio))
		{
			throw new InvalidArgumentException('ratio = '.print_r($ratio, true));
		}
		if(!is_int($timeout))
		{
			throw new InvalidArgumentException('timeout = '.print_r($timeout, true));
		}
		if(!is_int($voters))
		{
			throw new InvalidArgumentException('voters = '.print_r($voters, true));
		}

		$tmpCmd = Xmlrpc\Request::encode('Kick', array($login));

		return $this->execute('CallVoteEx', array($tmpCmd, $ratio, $timeout, $voters), $multicall);
	}

	/**
	 * Call a vote to ban a player.
	 * You can additionally specifiy specific parameters for this vote: a ratio, a time out
	 * and who is voting. Special timeout values: a timeout of '0' means default, '1' means
	 * indefinite; a ratio of '-1' means default; Voters values: '0' means only active players,
	 * '1' means any player, '2' is for everybody, pure spectators included.
	 * @param Structures\Player|string $player
	 * @param double $ratio -1 means default, else ration should be between 0 and 1
	 * @param int $timeout time to vote in millisecondes, '0' means default
	 * @param int $voters Voters values: '0' means only active players, '1' means any player, '2' is for everybody, pure spectators included
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 */
	function callVoteBan($player, $ratio = 0.6, $timeout = 0, $voters = 1, $multicall = false)
	{
		if(!($login = $this->getLogin($player)))
		{
			throw new InvalidArgumentException('player must be set');
		}
		if(!is_float($ratio))
		{
			throw new InvalidArgumentException('ratio = '.print_r($ratio, true));
		}
		if(!is_int($timeout))
		{
			throw new InvalidArgumentException('timeout = '.print_r($timeout, true));
		}
		if(!is_int($voters))
		{
			throw new InvalidArgumentException('voters = '.print_r($voters, true));
		}

		$tmpCmd = Xmlrpc\Request::encode('Ban', array($login));

		return $this->execute('CallVoteEx', array($tmpCmd, $ratio, $timeout, $voters), $multicall);
	}

	/**
	 * Call a vote to restart the current Map.
	 * You can additionally specifiy specific parameters for this vote: a ratio, a time out
	 * and who is voting. Special timeout values: a timeout of '0' means default, '1' means
	 * indefinite; a ratio of '-1' means default; Voters values: '0' means only active players,
	 * '1' means any player, '2' is for everybody, pure spectators included.
	 * @param double $ratio -1 means default, else ration should be between 0 and 1
	 * @param int $timeout time to vote in millisecondes, '0' means default
	 * @param int $voters Voters values: '0' means only active players, '1' means any player, '2' is for everybody, pure spectators included
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 */
	function callVoteRestartMap($ratio = 0.5, $timeout = 0, $voters = 1, $multicall = false)
	{
		if(!is_float($ratio))
		{
			throw new InvalidArgumentException('ratio = '.print_r($ratio, true));
		}
		if(!is_int($timeout))
		{
			throw new InvalidArgumentException('timeout = '.print_r($timeout, true));
		}
		if(!is_int($voters))
		{
			throw new InvalidArgumentException('voters = '.print_r($voters, true));
		}

		$tmpCmd = Xmlrpc\Request::encode('RestartMap', array());

		return $this->execute('CallVoteEx', array($tmpCmd, $ratio, $timeout, $voters), $multicall);
	}

	/**
	 * Call a vote to go to the next Map.
	 * You can additionally specifiy specific parameters for this vote: a ratio, a time out
	 * and who is voting. Special timeout values: a timeout of '0' means default, '1' means
	 * indefinite; a ratio of '-1' means default; Voters values: '0' means only active players,
	 * '1' means any player, '2' is for everybody, pure spectators included.
	 * @param double $ratio -1 means default, else ration should be between 0 and 1
	 * @param int $timeout time to vote in millisecondes, '0' means default
	 * @param int $voters Voters values: '0' means only active players, '1' means any player, '2' is for everybody, pure spectators included
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 */
	function callVoteNextMap($ratio = 0.5, $timeout = 0, $voters = 1, $multicall = false)
	{
		if(!is_float($ratio))
		{
			throw new InvalidArgumentException('ratio = '.print_r($ratio, true));
		}
		if(!is_int($timeout))
		{
			throw new InvalidArgumentException('timeout = '.print_r($timeout, true));
		}
		if(!is_int($voters))
		{
			throw new InvalidArgumentException('voters = '.print_r($voters, true));
		}

		$tmpCmd = Xmlrpc\Request::encode('NextMap', array());

		return $this->execute('CallVoteEx', array($tmpCmd, $ratio, $timeout, $voters), $multicall);
	}

	/**
	 * 	Used internaly by game.
	 *  @param bool $multicall
	 * 	@return bool
	 */
	protected function internalCallVote($multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Cancel the current vote.
	 * @param bool $multicall
	 * @return bool
	 */
	function cancelVote($multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Returns the vote currently in progress.
	 * The returned structure is { CallerLogin, CmdName, CmdParam }.
	 * @return Structures\Vote
	 */
	function getCurrentCallVote()
	{
		return Structures\Vote::fromArray($this->execute(ucfirst(__FUNCTION__)));
	}

	/**
	 * Set a new timeout for waiting for votes. A zero value disables callvote.
	 * Requires a map restart to be taken into account
	 * @param int $timeout time to vote in millisecondes, '0' disables callvote
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setCallVoteTimeOut($timeout, $multicall = false)
	{
		if(!is_int($timeout))
		{
			throw new InvalidArgumentException('timeout = '.print_r($timeout, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($timeout), $multicall);
	}

	/**
	 * Get the current and next timeout for waiting for votes.
	 * The struct returned contains two fields 'CurrentValue' and 'NextValue'.
	 * @return array
	 */
	function getCallVoteTimeOut()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new default ratio for passing a vote.
	 * Must lie between 0 and 1.
	 * @param double $ratio
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setCallVoteRatio($ratio, $multicall = false)
	{
		if($ratio !== -1. && !(is_float($ratio) && $ratio >= 0 && $ratio <= 1))
		{
			throw new InvalidArgumentException('ratio = '.print_r($ratio, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($ratio), $multicall);
	}

	/**
	 * Get the current default ratio for passing a vote.
	 * This value lies between 0 and 1.
	 * @return double
	 */
	function getCallVoteRatio()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set new ratios for passing specific votes.
	 * The parameter is an array of associative arrays
	 * {string votecommand, double ratio}, ratio is in [0,1] or -1. for vote disabled.
	 * @example setCallVoteRatios(array(array('Command' => 'Kick', 'Ratio' => -1. ));
	 * @param array $ratios
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setCallVoteRatios(array $ratios, $multicall = false)
	{
		return $this->setCallVoteRatiosEx(true, $ratios, $multicall);
	}

	/**
	 * Get the current ratios for passing votes.
	 * @return array
	 */
	function getCallVoteRatios()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set the ratios list for passing specific votes, extended version with parameters matching.
	 * The parameters, a boolean ReplaceAll (or else, only modify specified ratios, leaving the previous ones unmodified)
	 * and an array of structs {string Command, string Param, double Ratio},
	 * ratio is in [0,1] or -1 for vote disabled.
	 * Param is matched against the vote parameters to make more specific ratios, leave empty to match all votes for the command.
	 * Only available to Admin.
	 * @param bool $replaceAll
	 * @param array[array[]]|Structures\VoteRatio[] $ratios
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setCallVoteRatiosEx($replaceAll, array $ratios, $multicall = false)
	{
		if(!is_array($ratios))
		{
			throw new InvalidArgumentException('ratios = '.print_r($ratios, true));
		}

		foreach($ratios as $i => $ratio)
		{
			if($ratio instanceof Structures\VoteRatio)
			{
				$ratios[$i] = $ratio->toArray();
			}
			else
			{
				if(!is_array($ratio) && !array_key_exists('Command', $ratio) && !array_key_exists('Ratio', $ratio))
				{
					throw new InvalidArgumentException('ratios['.$i.'] = '.print_r($ratio, true));
				}
				if(!is_string($ratio['Command']))
				{
					throw new InvalidArgumentException('ratios['.$i.'][Command] = '.print_r($ratios['Command'], true));
				}
				if($ratio['Ratio'] !== -1. && !(is_float($ratio['Ratio']) && $ratio['Ratio'] >= 0 && $ratio['Ratio'] <= 1))
				{
					throw new InvalidArgumentException('ratios['.$i.'][Ratio] = '.print_r($ratio['Ratio'], true));
				}
				if(array_key_exists('Param', $ratio) && !is_string($ratio['Param']))
				{
					throw new InvalidArgumentException('ratios['.$i.'][Param] = '.print_r($ratio['Param'], true));
				}
				elseif(!array_key_exists('Param', $ratio))
				{
					$ratio['Param'] = '';
					$ratios[$i] = $ratio;
				}
			}
		}

		return $this->execute(ucfirst(__FUNCTION__), array($replaceAll, $ratios), $multicall);
	}

	/**
	 * Get the current ratios for passing votes, extended version with parameters matching.
	 * @return Structures\VoteRatio[]
	 */
	function getCallVoteRatiosEx()
	{
		return Structures\VoteRatio::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__)));
	}

	/**
	 * Send a localised text message to specied clients.
	 * The parameter is an array of structures {Lang='??', Text='...'}.
	 * If no matching language is found, the last text in the array is used.
	 * @param array $messages
	 * @param Structures\Player|string|mixed[] $receiver Structures\Player(s) who will receive the message, put null to send the message to everyone
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function chatSendServerMessageToLanguage(array $messages, $receiver = null, $multicall = false)
	{
		if(!is_array($messages))
		{
			throw new InvalidArgumentException('messages = '.print_r($messages, true));
		}
		if(is_null($receiver))
		{
			$receiverString = '';
		}
		else if(!($receiverString = $this->getLogins($receiver)))
		{
			throw new InvalidArgumentException('receiver = '.print_r($receiver, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($messages, $receiverString), $multicall);
	}

	/**
	 * Send a text message without the server login to everyone if players is null.
	 * Players can be a Structures\Player object or an array of Structures\Player
	 * @param string $message
	 * @param Structures\Player|string|mixed[] $receiver Structures\Player(s) who will receive the message, put null to send the message to everyone
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function chatSendServerMessage($message, $receiver = null, $multicall = false)
	{
		if(!is_string($message))
		{
			throw new InvalidArgumentException('message = '.print_r($message, true));
		}

		$params = array($message);
		$method = 'ChatSendServerMessage';
		if(!is_null($receiver))
		{
			if(!($logins = $this->getLogins($receiver)))
			{
				throw new InvalidArgumentException('receiver = '.print_r($receiver, true));
			}
			$params[] = $logins;
			$method .= 'ToLogin';
		}

		return $this->execute($method, $params, $multicall);
	}

	/**
	 * Send a localised text message to selected clients.
	 * The parameter is an array of structures {Lang='??', Text='...'}.
	 * If no matching language is found, the last text in the array is used.
	 * @param array $messages
	 * @param Structures\Player|string|mixed[] $receiver Structures\Player(s) who will receive the message, put null to send the message to everyone
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function chatSendToLanguage(array $messages, $receiver = null, $multicall = false)
	{
		if(!is_array($messages)) throw new InvalidArgumentException('messages = '.print_r($messages, true));

		if($receiver == null)
		{
			$receiverString = '';
		}
		else if(!($receiverString = $this->getLogins($receiver)))
		{
			throw new InvalidArgumentException('receiver = '.print_r($receiver, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($messages, $receiverString), $multicall);
	}

	/**
	 * Send a text message to every Structures\Player or the a specified player(s).
	 * If Structures\Player is null, the message will be delivered to every Structures\Player
	 * @param string $message
	 * @param Structures\Player|string|mixed[] $receiver Structures\Player(s) who will receive the message, put null to send the message to everyone
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function chatSend($message, $receiver, $multicall = false)
	{
		if(!is_string($message)) throw new InvalidArgumentException('message = '.print_r($message, true));

		$params = array($message);
		$method = 'ChatSend';
		if(!is_null($receiver))
		{
			if(!($logins = $this->getLogins($receiver)))
			{
				throw new InvalidArgumentException('players = '.print_r($receiver, true));
			}
			$params[] = $logins;
			$method .= 'ToLogin';
		}

		return $this->execute($method, $params, $multicall);
	}

	/**
	 * Returns the last chat lines. Maximum of 40 lines.
	 * @return array
	 */
	function getChatLines()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * The chat messages are no longer dispatched to the players, they only go to the rpc callback
	 * and the controller has to manually forward them. The second (optional) parameter allows all
	 * messages from the server to be automatically forwarded.
	 * @param bool $enable
	 * @param bool $serverAutomaticForward
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function chatEnableManualRouting($enable, $serverAutomaticForward = false, $multicall = false)
	{
		if(!is_bool($enable))
		{
			throw new InvalidArgumentException('enable = '.print_r($enable, true));
		}
		if(!is_bool($serverAutomaticForward))
		{
			throw new InvalidArgumentException('serverAutomaticForward = '.print_r($serverAutomaticForward, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($enable, $serverAutomaticForward), $multicall);
	}

	/**
	 * (Text, SenderLogin, DestLogin) Send a text message to the specified DestLogin (or everybody if empty)
	 * on behalf of SenderLogin. DestLogin can be a single login or a list of comma-separated logins.
	 * Only available if manual routing is enabled.
	 * @param string $message
	 * @param Structures\Player|string $sender
	 * @param Structures\Player|string $receiver
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function chatForwardToLogin($message, $sender, $receiver = null, $multicall = false)
	{
		if(!is_string($message))
		{
			throw new InvalidArgumentException('message = '.print_r($message, true));
		}
		if(!($senderLogin = $this->getLogin($sender)))
		{
			throw new InvalidArgumentException('sender must be set');
		}
		$receiverLogin = $this->getLogin($receiver) ? : '';

		return $this->execute(ucfirst(__FUNCTION__), array($message, $senderLogin, $receiverLogin), $multicall);
	}

	/**
	 * Display a notice on the client with the specified UId.
	 * The parameters are :
	 * the Uid of the client to whom the notice is sent,
	 * the text message to display,
	 * the UId of the avatar to display next to it (or '255' for no avatar),
	 * an optional 'max duration' in seconds (default: 3).
	 * @param string $message
	 * @param Structures\Player|string|mixed[] $receiver
	 * @param Structures\Player|string $player
	 * @param int $variant 0, 1 or 2
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function sendNotice($receiver, $message, $player = null, $variant = 0, $multicall = false)
	{
		if(!is_string($message))
		{
			throw new InvalidArgumentException('message = '.print_r($message, true));
		}

		$params = array();
		$method = 'SendNotice';
		if(!is_null($receiver))
		{
			if(!($login = $this->getLogins($receiver)))
					throw new InvalidArgumentException('receiver = '.print_r($receiver, true));
			else $params[] = $login;

			$method .= 'ToLogin';
		}

		$params[] = $message;
		$params[] = $this->getLogin($player) ? : '';
		$params[] = $variant;
		return $this->execute($method, $params, $multicall);
	}

	/**
	 * Display a manialink page on the client of the specified Structures\Player(s).
	 * The first parameter is the login of the player,
	 * the other are identical to 'SendDisplayManialinkPage'.
	 * The players can be an object of player Type or an array of Structures\Player object
	 * @param null|Structures\Player|string|mixed[] $playerLogin
	 * @param string $manialink
	 * @param int $timeout
	 * @param bool $hideOnClick
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function sendDisplayManialinkPage($players, $manialink, $timeout, $hideOnClick, $multicall = false)
	{
		$params = array();
		$method = 'SendDisplayManialinkPage';
		if(!is_null($players))
		{
			if(!($login = $this->getLogins($players))) throw new InvalidArgumentException('players = '.print_r($players, true));
			else $params[] = $login;

			$method .= 'ToLogin';
		}

		if(!is_string($manialink))
		{
			throw new InvalidArgumentException('manialink = '.print_r($manialink, true));
		}
		if(!is_int($timeout))
		{
			throw new InvalidArgumentException('timeout = '.print_r($timeout, true));
		}
		if(!is_bool($hideOnClick))
		{
			throw new InvalidArgumentException('hideOnClick = '.print_r($hideOnClick, true));
		}
		$params[] = $manialink;
		$params[] = $timeout;
		$params[] = $hideOnClick;

		return $this->execute($method, $params, $multicall);
	}

	/**
	 * Hide the displayed manialink page on the client with the specified login.
	 * Login can be a single login or a list of comma-separated logins.
	 * @param null|Structures\Player|string|mixed[] $players
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function sendHideManialinkPage($players = null, $multicall = false)
	{
		$params = array();
		$method = 'SendHideManialinkPage';
		if(!is_null($players))
		{
			if(!($login = $this->getLogins($players))) throw new InvalidArgumentException('players = '.print_r($players, true));
			else $params[] = $login;

			$method .= 'ToLogin';
		}

		return $this->execute($method, $params, $multicall);
	}

	/**
	 * Returns the latest results from the current manialink page,
	 * as an array of structs {string Login, int PlayerId, int Result}
	 * Result==0 -> no answer, Result>0.... -> answer from the player.
	 * @return array
	 */
	function getManialinkPageAnswers()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Opens a link in the client with the specified players.
	 * The parameters are the login of the client to whom the link to open is sent, the link url, and the 'LinkType'
	 * (0 in the external browser, 1 in the internal manialink browser).
	 * Login can be a single login or a list of comma-separated logins. Only available to
	 * @param Structures\Player|string|mixed[] $player
	 * @param string $link
	 * @param int $linkType
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function sendOpenLink($player, $link, $linkType, $multicall = false)
	{
		if(!($login = $this->getLogins($player)))
		{
			throw new InvalidArgumentException('player must be set');
		}
		if(!is_string($link))
		{
			throw new InvalidArgumentException('link = '.print_r($link, true));
		}
		if($linkType !== 0 && $linkType !== 1)
		{
			throw new InvalidArgumentException('linkType = '.print_r($linkType, true));
		}

		return $this->execute('SendOpenLinkToLogin', array($login, $link, $linkType), $multicall);
	}

	/**
	 * Kick the player with an optional message.
	 * @param Structures\Player|string $playerLogin
	 * @param string $message
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function kick($player, $message = '', $multicall = false)
	{
		if(!($login = $this->getLogin($player)))
		{
			throw new InvalidArgumentException('player must be set');
		}
		if(!is_string($message))
		{
			throw new InvalidArgumentException('message = '.print_r($message, true));
		}

		return $this->execute('Kick', array($login, $message), $multicall);
	}

	/**
	 * Ban the player with an optional message.
	 * @param Structures\Player|string $player
	 * @param string $message
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function ban($player, $message = '', $multicall = false)
	{
		if(!($login = $this->getLogin($player)))
		{
			throw new InvalidArgumentException('player must be set');
		}
		if(!is_string($message))
		{
			throw new InvalidArgumentException('message = '.print_r($message, true));
		}

		return $this->execute('Ban', array($login, $message), $multicall);
	}

	/**
	 * Ban the player with a message.
	 * Add it to the black list, and optionally save the new list.
	 * @param Structures\Player|string $player
	 * @param string $message
	 * @param bool $saveList
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function banAndBlackList($player, $message, $saveList = false, $multicall = false)
	{
		if(!($login = $this->getLogin($player)))
		{
			throw new InvalidArgumentException('player must be set');
		}
		if(!is_string($message) || !$message)
		{
			throw new InvalidArgumentException('message = '.print_r($message, true));
		}
		if(!is_bool($saveList))
		{
			throw new InvalidArgumentException('saveList = '.print_r($saveList, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($login, $message, $saveList), $multicall);
	}

	/**
	 * Unban the player
	 * @param Structures\Player|string $player
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function unBan($player, $multicall = false)
	{
		if(!($login = $this->getLogin($player)))
		{
			throw new InvalidArgumentException('player must be set');
		}

		return $this->execute(ucfirst(__FUNCTION__), array($login), $multicall);
	}

	/**
	 * Clean the ban list of the server.
	 * @param bool $multicall
	 * @return bool
	 */
	function cleanBanList($multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Returns the list of banned players. This method takes two parameters.
	 * The first parameter specifies the maximum number of infos to be returned,
	 * the second one the starting index in the list. The list is an array of structures.
	 * Each structure contains the following fields : Login, ClientName and IPAddress.
	 * @param int $length specifies the maximum number of infos to be returned
	 * @param int $offset specifies the starting index in the list
	 * @return Structures\Player[] The list is an array of Structures\Player
	 * @throws InvalidArgumentException
	 */
	function getBanList($length, $offset)
	{
		if(!is_int($length)) throw new InvalidArgumentException('length = '.print_r($length, true));
		if(!is_int($offset)) throw new InvalidArgumentException('offset = '.print_r($offset, true));

		$result = $this->execute(ucfirst(__FUNCTION__), array($length, $offset));
		return Structures\Player::fromArrayOfArray($result);
	}

	/**
	 * Blacklist the player
	 * @param Structures\Player|string $player
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function blackList($player, $multicall = false)
	{
		if(!($login = $this->getLogin($player)))
		{
			throw new InvalidArgumentException('player must be set');
		}
		return $this->execute(ucfirst(__FUNCTION__), array($login), $multicall);
	}

	/**
	 * UnBlackList the player
	 * @param Structures\Player|string $player
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function unBlackList($player, $multicall = false)
	{
		if(!($login = $this->getLogin($player)))
		{
			throw new InvalidArgumentException('player must be set');
		}
		return $this->execute(ucfirst(__FUNCTION__), array($login), $multicall);
	}

	/**
	 * Clean the blacklist of the server.
	 * @param bool $multicall
	 * @return bool
	 */
	function cleanBlackList($multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Returns the list of blacklisted players.
	 * This method takes two parameters.
	 * The first parameter specifies the maximum number of infos to be returned,
	 * the second one the starting index in the list. The list is an array of structures.
	 * Each structure contains the following fields : Login.
	 * @param int $length specifies the maximum number of infos to be returned
	 * @param int $offset specifies the starting index in the list
	 * @return Structures\Player[] The list is an array of structures. Each structure contains the following fields : Login.
	 * @throws InvalidArgumentException
	 */
	function getBlackList($length, $offset)
	{
		if(!is_int($length)) throw new InvalidArgumentException('length = '.print_r($length, true));
		if(!is_int($offset)) throw new InvalidArgumentException('offset = '.print_r($offset, true));

		$result = $this->execute(ucfirst(__FUNCTION__), array($length, $offset));
		return Structures\Player::fromArrayOfArray($result);
	}

	/**
	 * Load the black list file with the specified file name.
	 * @param string $filename blackList file name
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function loadBlackList($filename, $multicall = false)
	{
		if(!is_string($filename)) throw new InvalidArgumentException('filename = '.print_r($filename, true));

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Save the black list in the file with specified file name.
	 * @param string $filename blackList filename
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function saveBlackList($filename, $multicall = false)
	{
		if(!is_string($filename)) throw new InvalidArgumentException('filename = '.print_r($filename, true));

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Add the player to the guest list.
	 * @param Structures\Player|string $player
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function addGuest($player, $multicall = false)
	{
		if(!($login = $this->getLogin($player)))
		{
			throw new InvalidArgumentException('player must be set');
		}
		return $this->execute(ucfirst(__FUNCTION__), array($login), $multicall);
	}

	/**
	 * Remove the player from the guest list.
	 * @param Structures\Player|string $player
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function removeGuest($player, $multicall = false)
	{
		if(!($login = $this->getLogin($player)))
		{
			throw new InvalidArgumentException('player must be set');
		}
		return $this->execute(ucfirst(__FUNCTION__), array($login), $multicall);
	}

	/**
	 * Clean the guest list of the server.
	 * @param bool $multicall
	 * @return bool
	 */
	function cleanGuestList($multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Returns the list of players on the guest list.
	 * This method takes two parameters.
	 * The first parameter specifies the maximum number of infos to be returned,
	 * the second one the starting index in the list. The list is an array of structures.
	 * Each structure contains the following fields : Login.
	 * @param int $length specifies the maximum number of infos to be returned
	 * @param int $offset specifies the starting index in the list
	 * @return Structures\Player[] The list is an array of structures. Each structure contains the following fields : Login.
	 * @throws InvalidArgumentException
	 */
	function getGuestList($length, $offset)
	{
		if(!is_int($length))
		{
			throw new InvalidArgumentException('length = '.print_r($length, true));
		}
		if(!is_int($offset))
		{
			throw new InvalidArgumentException('offset = '.print_r($offset, true));
		}

		$result = $this->execute(ucfirst(__FUNCTION__), array($length, $offset));
		return Structures\Player::fromArrayOfArray($result);
	}

	/**
	 *
	 * Load the guest list file with the specified file name.
	 * @param string $filename blackList file name
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function loadGuestList($filename, $multicall = false)
	{
		if(!is_string($filename))
		{
			throw new InvalidArgumentException('filename = '.print_r($filename, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Save the guest list in the file with specified file name.
	 * @param string $filename blackList file name
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function saveGuestList($filename, $multicall = false)
	{
		if(!is_string($filename))
		{
			throw new InvalidArgumentException('filename = '.print_r($filename, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Sets whether buddy notifications should be sent in the chat.
	 * login is the login of the player, or '' for global setting,
	 * enabled is the value.
	 * @param null|Structures\Player|string $player the player, or null for global setting
	 * @param bool $enable the value.
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setBuddyNotification($player, $enable, $multicall = false)
	{
		if(!is_bool($enable))
		{
			throw new InvalidArgumentException('enable = '.print_r($enable, true));
		}

		$player = $this->getLogin($player) ? : '';

		return $this->execute(ucfirst(__FUNCTION__), array($player, $enable), $multicall);
	}

	/**
	 * Gets whether buddy notifications are enabled for login, or '' to get the global setting.
	 * @param null|Structures\Player|string $player the player, or null for global setting
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function getBuddyNotification($player)
	{
		$player = $this->getLogin($player) ? : '';

		return $this->execute(ucfirst(__FUNCTION__), array($player));
	}

	/**
	 * Write the data to the specified file. The filename is relative to the Tracks path
	 * @param string $filename The file to be written
	 * @param string $localFilename The file to be read to obtain the data
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function writeFile($filename, $localFilename, $multicall = false)
	{
		if(!is_string($filename))
		{
			throw new InvalidArgumentException('filename = '.print_r($filename, true));
		}
		if(!file_exists($localFilename))
		{
			throw new InvalidArgumentException('localFilename = '.print_r($localFilename, true));
		}

		$inputData = file_get_contents($localFilename);
		$data = new Xmlrpc\Base64($inputData);

		return $this->execute(ucfirst(__FUNCTION__), array($filename, $data), $multicall);
	}

	/**
	 * Write the data to the specified file. The filename is relative to the Tracks path
	 * @param string $filename The file to be written
	 * @param string $data the data to be written
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function writeFileFromString($filename, $data, $multicall = false)
	{
		if(!is_string($filename))
		{
			throw new InvalidArgumentException('filename = '.print_r($filename, true));
		}

		$data = new Xmlrpc\Base64($data);

		return $this->execute('WriteFile', array($filename, $data), $multicall);
	}

	/**
	 * Send the data to the specified player.
	 * Login can be a single login or a list of comma-separated logins.
	 * @param Structures\Player|string|mixed[] $players
	 * @param string $filename
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function tunnelSendData($players, $filename, $multicall = false)
	{
		if(!($login = $this->getLogins($players)))
		{
			throw new InvalidArgumentException('players = '.print_r($players, true));
		}
		if(!file_exists($filename))
		{
			throw new InvalidArgumentException('filename = '.print_r($filename, true));
		}

		$inputData = file_get_contents($filename);
		$data = new Xmlrpc\Base64($inputData);

		return $this->execute('TunnelSendDataToLogin', array($login, $data), $multicall);
	}

	/**
	 * Send the data to the specified player.
	 * Login can be a single login or a list of comma-separated logins.
	 * @param Structures\Player|string|mixed[] $players
	 * @param string $data
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function tunnelSendDataFromString($players, $data, $multicall = false)
	{
		if(!($login = $this->getLogins($players)))
		{
			throw new InvalidArgumentException('players = '.print_r($players, true));
		}

		$data = new Xmlrpc\Base64($data);

		return $this->execute('TunnelSendDataToLogin', array($login, $data), $multicall);
	}

	/**
	 * Just log the parameters and invoke a callback.
	 * Can be used to talk to other xmlrpc clients connected, or to make custom votes.
	 * If used in a callvote, the first parameter will be used as the vote message on the clients.
	 * @param string $message the message to log
	 * @param string $callback optionnal callback name
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function dedicatedEcho($message, $callback = '', $multicall = false)
	{
		if(!is_string($message))
		{
			throw new InvalidArgumentException('message = '.print_r($message, true));
		}
		if(!is_string($callback))
		{
			throw new InvalidArgumentException('callback = '.print_r($callback, true));
		}

		return $this->execute('Echo', array($message, $callback), $multicall);
	}

	/**
	 * Ignore the specified Structures\Player.
	 * @param Structures\Player|string $player
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function ignore($player, $multicall = false)
	{
		if(!($login = $this->getLogin($player)))
		{
			throw new InvalidArgumentException('player must be set');
		}
		return $this->execute(ucfirst(__FUNCTION__), array($login), $multicall);
	}

	/**
	 * Unignore the specified player.
	 * @param Structures\Player|string $player
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function unIgnore($player, $multicall = false)
	{
		if(!($login = $this->getLogin($player)))
		{
			throw new InvalidArgumentException('player must be set');
		}
		return $this->execute(ucfirst(__FUNCTION__), array($login), $multicall);
	}

	/**
	 * Clean the ignore list of the server.
	 * @param bool $multicall
	 * @return bool
	 */
	function cleanIgnoreList($multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Returns the list of ignored players. This method takes two parameters.
	 * The first parameter specifies the maximum number of infos to be returned,
	 * the second one the starting index in the list. The list is an array of structures.
	 * Each structure contains the following fields : Login.
	 * @param int $length specifies the maximum number of infos to be returned
	 * @param int $offset specifies the starting index in the list
	 * @return Structures\Player[] The list is an array of structures. Each structure contains the following fields : Login.
	 * @throws InvalidArgumentException
	 */
	function getIgnoreList($length, $offset)
	{
		if(!is_int($length))
		{
			throw new InvalidArgumentException('length = '.print_r($length, true));
		}
		if(!is_int($offset))
		{
			throw new InvalidArgumentException('offset = '.print_r($offset, true));
		}

		$result = $this->execute(ucfirst(__FUNCTION__), array($length, $offset));
		return Structures\Player::fromArrayOfArray($result);
	}

	/**
	 * Pay coppers from the server account to a player, returns the BillId.
	 * This method takes three parameters:
	 * Login of the payee,
	 * Coppers to pay and
	 * Label to send with the payment.
	 * The creation of the transaction itself may cost coppers,
	 * so you need to have coppers on the server account.
	 * @param Structures\Player|string $player
	 * @param int $amount
	 * @param string $label
	 * @param bool $multicall
	 * @return int The Bill Id
	 * @throws InvalidArgumentException
	 */
	function pay($player, $amount, $label, $multicall = false)
	{
		if(!($login = $this->getLogin($player)))
		{
			throw new InvalidArgumentException('player must be set');
		}
		if(!is_int($amount) || $amount < 1)
		{
			throw new InvalidArgumentException('amount = '.print_r($amount, true));
		}
		if(!is_string($label))
		{
			throw new InvalidArgumentException('label = '.print_r($label, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($login, $amount, $label), $multicall);
	}

	/**
	 * Create a bill, send it to a player, and return the BillId.
	 * This method takes four parameters:
	 * LoginFrom of the payer,
	 * Coppers the player has to pay,
	 * Label of the transaction and
	 * optional LoginTo of the payee (if empty string, then the server account is used).
	 * The creation of the transaction itself may cost coppers,
	 * so you need to have coppers on the server account.
	 * @param Structures\Player|string $fromPlayer
	 * @param int $amount
	 * @param string $label
	 * @param Structures\Player|string|null $toPlayer
	 * @param bool $multicall
	 * @return int
	 * @throws InvalidArgumentException
	 */
	function sendBill($fromPlayer, $amount, $label, $toPlayer = null, $multicall = false)
	{

		if(!is_int($amount) || $amount < 1)
		{
			throw new InvalidArgumentException('amount = '.print_r($amount, true));
		}
		if(!is_string($label))
		{
			throw new InvalidArgumentException('label = '.print_r($label, true));
		}
		if(!($from = $this->getLogin($fromPlayer)))
		{
			throw new InvalidArgumentException('fromPlayer must be set');
		}

		$to = $this->getLogin($toPlayer) ? : '';

		return $this->execute(ucfirst(__FUNCTION__), array($from, $amount, $label, $to), $multicall);
	}

	/**
	 * Returns the current state of a bill.
	 * This method takes one parameter, the BillId.
	 * Returns a struct containing
	 * State, StateName and TransactionId.
	 * Possible enum values are: CreatingTransaction, Issued, ValidatingPayement, Payed, Refused, Error.
	 * @param int $billId
	 * @return Structures\Bill
	 * @throws InvalidArgumentException
	 */
	function getBillState($billId)
	{
		if(!is_int($billId))
		{
			throw new InvalidArgumentException('billId = '.print_r($billId, true));
		}

		$result = $this->execute(ucfirst(__FUNCTION__), array($billId));
		return Structures\Bill::fromArray($result);
	}

	/**
	 * Returns the current number of planets on the server account.
	 * @return int
	 */
	function getServerPlanets()
	{
		return $this->execute('GetServerPlanets');
	}

	/**
	 * Get some system infos.
	 * Return a struct containing:
	 * PublishedIp, Port, P2PPort, ServerLogin, ServerPlayerId
	 * @return Structures\SystemInfos
	 */
	function getSystemInfo()
	{
		$result = $this->execute(ucfirst(__FUNCTION__));
		return Structures\SystemInfos::fromArray($result);
	}

	/**
	 * Sets up- and download speed for the server in kbps.
	 * @param int $downloadRate the download rate in kbps
	 * @param int $uploadRate the upload rate in kbps
	 * @param bool $multicall
	 * @return bool
	 */
	function setConnectionRates($downloadRate, $uploadRate, $multicall = false)
	{
		if(!is_int($downloadRate))
		{
			throw new InvalidArgumentException('downloadRate = '.print_r($downloadRate, true));
		}
		if(!is_int($uploadRate))
		{
			throw new InvalidArgumentException('uploadRate = '.print_r($uploadRate, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($downloadRate, $uploadRate), $multicall);
	}

	/**
	 * Returns the list of tags and associated values set on this server.
	 * The list is an array of structures {string Name, string Value}.
	 * Only available to Admin.
	 * @return array
	 */
	function getServerTags()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a tag and its value on the server. This method takes two parameters.
	 * The first parameter specifies the name of the tag, and the second one its value.
	 * Only available to Admin.
	 * @param string $key
	 * @param string $value
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setServerTag($key, $value, $multicall = false)
	{
		if(!is_string($key))
		{
			throw new InvalidArgumentException('key = '.print_r($key, true));
		}
		if(!is_string($value))
		{
			throw new InvalidArgumentException('value = '.print_r($value, true));
		}
		return $this->execute(ucfirst(__FUNCTION__), array($key, $value), $multicall);
	}

	/**
	 * Unset the tag with the specified name on the server.
	 * Only available to Admin.
	 * @param string $key
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function unsetServerTag($key, $multicall = false)
	{
		if(!is_string($key))
		{
			throw new InvalidArgumentException('key = '.print_r($key, true));
		}
		return $this->execute(ucfirst(__FUNCTION__), array($key), $multicall);
	}

	/**
	 * Reset all tags on the server.
	 * Only available to Admin.
	 * @param bool $multicall
	 * @return bool
	 */
	function resetServerTags($multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Set a new server name in utf8 format.
	 * @param string $serverName
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setServerName($serverName, $multicall = false)
	{
		if(!is_string($serverName))
		{
			throw new InvalidArgumentException('serverName = '.print_r($serverName, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($serverName), $multicall);
	}

	/**
	 * Get the server name in utf8 format.
	 * @return string
	 */
	function getServerName()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new server comment in utf8 format.
	 * @param string $serverComment
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setServerComment($serverComment, $multicall = false)
	{
		if(!is_string($serverComment))
		{
			throw new InvalidArgumentException('serverComment = '.print_r($serverComment, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($serverComment), $multicall);
	}

	/**
	 * Get the server comment in utf8 format.
	 * @return string
	 */
	function getServerComment()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set whether the server should be hidden from the public server list
	 * (0 = visible, 1 = always hidden, 2 = hidden from nations).
	 * @param int $visibility
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setHideServer($visibility, $multicall = false)
	{
		if($visibility !== 0 && $visibility !== 1 && $visibility !== 2)
		{
			throw new InvalidArgumentException('visibility = '.print_r($visibility, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($visibility), $multicall);
	}

	/**
	 * Get whether the server wants to be hidden from the public server list.
	 * @return string
	 */
	function getHideServer()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Returns true if this is a relay server.
	 * @return bool
	 */
	function isRelayServer()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new password for the server.
	 * @param string $serverPassword
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setServerPassword($serverPassword, $multicall = false)
	{
		if(!is_string($serverPassword))
		{
			throw new InvalidArgumentException('serverPassword = '.print_r($serverPassword, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($serverPassword), $multicall);
	}

	/**
	 * Get the server password if called as Admin or Super Admin, else returns if a password is needed or not.
	 * Get the server name in utf8 format.
	 * @return bool|string
	 */
	function getServerPassword()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new password for the spectator mode.
	 * @param string $serverPassword
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setServerPasswordForSpectator($serverPassword, $multicall = false)
	{
		if(!is_string($serverPassword))
		{
			throw new InvalidArgumentException('serverPassword = '.print_r($serverPassword, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($serverPassword), $multicall);
	}

	/**
	 * Get the password for spectator mode if called as Admin or Super Admin, else returns if a password is needed or not.
	 * @return bool|string
	 */
	function getServerPasswordForSpectator()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new maximum number of players.
	 * Requires a map restart to be taken into account.
	 * @param int $maxPlayers
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setMaxPlayers($maxPlayers, $multicall = false)
	{
		if(!is_int($maxPlayers))
		{
			throw new InvalidArgumentException('maxPlayers = '.print_r($maxPlayers, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($maxPlayers), $multicall);
	}

	/**
	 * Get the current and next maximum number of players allowed on server.
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getMaxPlayers()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new maximum number of spectators.
	 * Requires a map restart to be taken into account.
	 * @param int $maxSpectators
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setMaxSpectators($maxSpectators, $multicall = false)
	{
		if(!is_int($maxSpectators))
		{
			throw new InvalidArgumentException('maxPlayers = '.print_r($maxSpectators, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($maxSpectators), $multicall);
	}

	/**
	 * Get the current and next maximum number of spectators allowed on server.
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getMaxSpectators()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Declare if the server is a lobby, the number and maximum number of players currently managed by it, and the average level of the players.
	 * Only available to Admin.
	 * @param bool $isLobby
	 * @param int $lobbyPlayers
	 * @param int $maxPlayers
	 * @param double $lobbyPlayersLevel
	 * @param bool $multicall
	 * @return bool
	 */
	function setLobbyInfo($isLobby, $lobbyPlayers, $maxPlayers, $lobbyPlayersLevel, $multicall = false)
	{
		if(!is_bool($isLobby))
		{
			throw new InvalidArgumentException('isLobby = '.print_r($isLobby, true));
		}
		if(!is_int($lobbyPlayers))
		{
			throw new InvalidArgumentException('lobbyPlayers = '.print_r($lobbyPlayers, true));
		}
		if(!is_int($maxPlayers))
		{
			throw new InvalidArgumentException('maxPlayers = '.print_r($maxPlayers, true));
		}
		if(!is_double($lobbyPlayersLevel))
		{
			throw new InvalidArgumentException('lobbyPlayersLevel = '.print_r($lobbyPlayersLevel, true));
		}
		return $this->execute(ucfirst(__FUNCTION__), array($isLobby, $lobbyPlayers, $maxPlayers, $lobbyPlayersLevel), $multicall);
	}

	/**
	 * Get whether the server if a lobby, the number and maximum number of players currently managed by it.
	 * The struct returned contains 4 fields IsLobby, LobbyPlayers, LobbyMaxPlayers, and LobbyPlayersLevel.
	 * @return Structures\LobbyInfo
	 */
	function getLobbyInfo()
	{
		$result = $this->execute(ucfirst(__FUNCTION__));
		return Structures\LobbyInfo::fromArray($result);
	}

	/**
	 * Customize the clients 'leave server' dialog box.
	 * Parameters are: ManialinkPage, SendToServer url '#qjoin=login@title',
	 * ProposeAddToFavorites and DelayQuitButton (in milliseconds).
	 * Only available to Admin.
	 * @param string $manialinkPage
	 * @param string $sendToServer
	 * @param bool $proposeAddToFavorites
	 * @param bool $multicall
	 * @return bool
	 */
	function customizeQuitDialog($manialinkPage, $sendToServer, $proposeAddToFavorites, $delayQuitButton, $multicall = false)
	{
		if(!is_string($manialinkPage))
		{
			throw new InvalidArgumentException('manialinkPage = '.print_r($manialinkPage, true));
		}
		if(!is_string($sendToServer))
		{
			throw new InvalidArgumentException('sendToServer = '.print_r($sendToServer, true));
		}
		if(!is_bool($proposeAddToFavorites))
		{
			throw new InvalidArgumentException('proposeAddToFavorites = '.print_r($proposeAddToFavorites, true));
		}
		if(!is_int($delayQuitButton))
		{
			throw new InvalidArgumentException('delayQuitButton = '.print_r($delayQuitButton, true));
		}
		return $this->execute(ucfirst(__FUNCTION__), array($manialinkPage, $sendToServer, $proposeAddToFavorites, $delayQuitButton), $multicall);
	}

	/**
	 * Set whether, when a player is switching to spectator, the server should still consider him a player
	 * and keep his player slot, or not. Only available to Admin.
	 * @param bool $keep
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function keepPlayerSlots($keep, $multicall = false)
	{
		if(!is_bool($keep))
		{
			throw new InvalidArgumentException('keep = '.print_r($keep, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($keep), $multicall);
	}

	/**
	 * Get whether the server keeps player slots when switching to spectator.
	 * @return bool
	 */
	function isKeepingPlayerSlots()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Enable or disable peer-to-peer upload from server.
	 * @param bool $enable
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function enableP2PUpload($enable, $multicall = false)
	{
		if(!is_bool($enable))
		{
			throw new InvalidArgumentException('enable = '.print_r($enable, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($enable), $multicall);
	}

	/**
	 * Returns if the peer-to-peer upload from server is enabled.
	 * @return bool
	 */
	function isP2PUpload()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Enable or disable peer-to-peer download from server.
	 * @param bool $enable
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function enableP2PDownload($enable, $multicall = false)
	{
		if(!is_bool($enable))
		{
			throw new InvalidArgumentException('enable = '.print_r($enable, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($enable), $multicall);
	}

	/**
	 * Returns if the peer-to-peer download from server is enabled.
	 * @return bool
	 */
	function isP2PDownload()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Allow clients to download maps from the server.
	 * @param bool $allow
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function allowMapDownload($allow, $multicall = false)
	{
		if(!is_bool($allow))
		{
			throw new InvalidArgumentException('allow = '.print_r($allow, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($allow), $multicall);
	}

	/**
	 * Returns if clients can download maps from the server.
	 * @return bool
	 */
	function isMapDownloadAllowed()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Enable the autosaving of all replays (vizualisable replays with all players,
	 * but not validable) on the server.
	 * @param bool $enable
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function autoSaveReplays($enable, $multicall = false)
	{
		if(!is_bool($enable))
		{
			throw new InvalidArgumentException('enable = '.print_r($enable, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($enable), $multicall);
	}

	/**
	 * Returns if autosaving of all replays is enabled on the server.
	 * @return bool
	 */
	function isAutoSaveReplaysEnabled()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Enable the autosaving on the server of validation replays, every time a player makes a new time.
	 * @param bool $enable
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function autoSaveValidationReplays($enable, $multicall = false)
	{
		if(!is_bool($enable))
		{
			throw new InvalidArgumentException('enable = '.print_r($enable, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($enable), $multicall);
	}

	/**
	 * Returns if autosaving of validation replays is enabled on the server.
	 * @return bool
	 */
	function isAutoSaveValidationReplaysEnabled()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Saves the current replay (vizualisable replays with all players, but not validable).
	 * Pass a filename, or '' for an automatic filename.
	 * @param string $filename
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function saveCurrentReplay($filename = '', $multicall = false)
	{
		if(!is_string($filename))
		{
			throw new InvalidArgumentException('filename = '.print_r($filename, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Saves a replay with the ghost of all the players' best race.
	 * First parameter is the player object(or null for all players),
	 * Second parameter is the filename, or '' for an automatic filename.
	 * @param null|Structures\Player|string $player the player (or null for all players)
	 * @param string $filename is the filename, or '' for an automatic filename
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function saveBestGhostsReplay($player = null, $filename = '', $multicall = false)
	{
		if(!is_string($filename))
		{
			throw new InvalidArgumentException('filename = '.print_r($filename, true));
		}

		$playerLogin = $this->getLogin($player) ? : '';

		return $this->execute(ucfirst(__FUNCTION__), array($playerLogin, $filename), $multicall);
	}

	/**
	 * Returns a replay containing the data needed to validate the current best time of the player.
	 * The parameter is the login of the player.
	 * @param Structures\Player|string $player
	 * @return string
	 * @throws InvalidArgumentException
	 */
	function getValidationReplay($player)
	{
		if(!($login = $this->getLogin($player)))
		{
			throw new InvalidArgumentException('player must be set');
		}
		return $this->execute(ucfirst(__FUNCTION__), array($login))->scalar;
	}

	/**
	 * Set a new ladder mode between ladder disabled (0) and forced (1).
	 * Requires a map restart to be taken into account.
	 * @param int $mode
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setLadderMode($mode, $multicall = false)
	{
		if($mode !== 0 && $mode !== 1)
		{
			throw new InvalidArgumentException('mode = '.print_r($mode, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($mode), $multicall);
	}

	/**
	 * Get the current and next ladder mode on server.
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getLadderMode()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Get the ladder points limit for the players allowed on this server.
	 * The struct returned contains two fields LadderServerLimitMin and LadderServerLimitMax.
	 * @return array
	 */
	function getLadderServerLimits()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set the network vehicle quality to Fast (0) or High (1).
	 * Requires a map restart to be taken into account.
	 * @param int $quality
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setVehicleNetQuality($quality, $multicall = false)
	{
		if($quality !== 0 && $quality !== 1)
		{
			throw new InvalidArgumentException('quality = '.print_r($quality, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($quality), $multicall);
	}

	/**
	 * Get the current and next network vehicle quality on server.
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getVehicleNetQuality()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set new server options using the struct passed as parameters. This struct must contain the following fields :
	 * Name, Comment, Password, PasswordForSpectator, NextMaxPlayers, NextMaxSpectators, IsP2PUpload, IsP2PDownload,
	 * NextLadderMode, NextVehicleNetQuality, NextCallVoteTimeOut, CallVoteRatio, AllowMapDownload, AutoSaveReplays,
	 *
	 * and optionally for forever:
	 * RefereePassword, RefereeMode, AutoSaveValidationReplays, HideServer, UseChangingValidationSeed,
	 * ClientInputsMaxLatency, KeepPlayerSlots.
	 *
	 * Only available to Admin.
	 * A change of NextMaxPlayers, NextMaxSpectators, NextLadderMode, NextVehicleNetQuality, NextCallVoteTimeOut
	 * or UseChangingValidationSeed requires a map restart to be taken into account.
	 * @param array $options
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setServerOptions(array $options, $multicall = false)
	{
		if(!is_array($options) || !array_key_exists('Name', $options) || !array_key_exists('Comment', $options)
			|| !array_key_exists('Password', $options) || !array_key_exists('PasswordForSpectator', $options)
			|| !array_key_exists('NextCallVoteTimeOut', $options) || !array_key_exists('CallVoteRatio', $options)
			|| (array_key_exists('IsP2PUpload', $options) xor array_key_exists('IsP2PDownload', $options))
			|| (array_key_exists('NextMaxPlayer', $options) xor array_key_exists('NextMaxSpectator', $options))
			|| (array_key_exists('RefereePassword', $options) xor array_key_exists('RefereeMode', $options)))
		{
			throw new InvalidArgumentException('options = '.print_r($options, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($options), $multicall);
	}

	/**
	 * Returns a struct containing the server options:
	 * Name, Comment, Password, PasswordForSpectator, CurrentMaxPlayers, NextMaxPlayers, CurrentMaxSpectators,
	 * NextMaxSpectators, KeepPlayerSlots, IsP2PUpload, IsP2PDownload, CurrentLadderMode, NextLadderMode,
	 * CurrentVehicleNetQuality, NextVehicleNetQuality, CurrentCallVoteTimeOut, NextCallVoteTimeOut, CallVoteRatio,
	 * AllowMapDownload, AutoSaveReplays, RefereePassword, RefereeMode, AutoSaveValidationReplays, HideServer,
	 * CurrentUseChangingValidationSeed, NextUseChangingValidationSeed, ClientInputsMaxLatency.
	 * @return Structures\ServerOptions
	 * @throws InvalidArgumentException
	 */
	function getServerOptions()
	{
		return Structures\ServerOptions::fromArray($this->execute(ucfirst(__FUNCTION__)));
	}

	/**
	 * Set whether the players can choose their side or if the teams are forced by the server (using ForcePlayerTeam()).
	 * Only available to Admin.
	 * @param bool $enable
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setForcedTeams($enable, $multicall = false)
	{
		if(!is_bool($enable))
		{
			throw new InvalidArgumentException('enable = '.print_r($enable, true));
		}
		return $this->execute(ucfirst(__FUNCTION__), array($enable), $multicall);
	}

	/**
	 * Returns whether the players can choose their side or if the teams are forced by the server.
	 * @return bool
	 */
	function getForcedTeams()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Defines the packmask of the server. Can be 'United', 'Nations', 'Sunrise', 'Original',
	 * or any of the environment names. (Only maps matching the packmask will be
	 * allowed on the server, so that player connecting to it know what to expect.)
	 * Only available when the server is stopped.
	 * @param string $packMask
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setServerPackMask($packMask, $multicall = false)
	{
		if(!is_string($packMask))
		{
			throw new InvalidArgumentException('packMask = '.print_r($packMask, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($packMask), $multicall);
	}

	/**
	 * Get the packmask of the server.
	 * @return string
	 */
	function getServerPackMask()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set the mods to apply on the clients. Parameters:
	 * Override, if true even the maps with a mod will be overridden by the server setting;
	 * Mods, an array of structures [{EnvName, Url}, ...].
	 * Requires a map restart to be taken into account.
	 * @param bool $override
	 * @param array $mods
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setForcedMods($override, $mods, $multicall = false)
	{
		if(!is_bool($override))
		{
			throw new InvalidArgumentException('override = '.print_r($override, true));
		}
		if(is_array($mods))
		{
			$modList = array();
			foreach($mods as $mod)
			{
				if(!($mod instanceof Structures\Mod)) throw new InvalidArgumentException('mods = '.print_r($mods, true));
				else $modList[] = $mod->toArray();
			}
		}
		elseif($mods instanceof Structures\Mod) $modList = array($mods->toArray());
		else throw new InvalidArgumentException('mods = '.print_r($mods, true));

		return $this->execute(ucfirst(__FUNCTION__), array($override, $modList), $multicall);
	}

	/**
	 * Get the mods settings.
	 * @return array the first value is a boolean which indicate if the mods override existing mods, the second is an array of objet of Structures\Mod type
	 */
	function getForcedMods()
	{
		$result = $this->execute(ucfirst(__FUNCTION__));
		$result['Mods'] = Structures\Mod::fromArrayOfArray($result['Mods']);
		return $result;
	}

	/**
	 * Set the music to play on the clients. Parameters:
	 * Override, if true even the maps with a custom music will be overridden by the server setting,
	 * UrlOrFileName for the music.
	 * Requires a map restart to be taken into account
	 * @param bool $override
	 * @param string $music
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 */
	function setForcedMusic($override, $music, $multicall = false)
	{
		if(!is_bool($override))
		{
			throw new InvalidArgumentException('override = '.print_r($override, true));
		}
		if(!is_string($music))
		{
			throw new InvalidArgumentException('music = '.print_r($music, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($override, $music), $multicall);
	}

	/**
	 * Get the music setting.
	 * @return Structures\Music
	 */
	function getForcedMusic()
	{
		return Structures\Music::fromArray($this->execute(ucfirst(__FUNCTION__)));
	}

	/**
	 * Defines a list of remappings for player skins. It expects a list of structs Orig, Name, Checksum, Url.
	 * Orig is the name of the skin to remap, or '*' for any other. Name, Checksum, Url define the skin to use.
	 * (They are optional, you may set value '' for any of those. All 3 null means same as Orig).
	 * Will only affect players connecting after the value is set.
	 * @param Structures\Skin[] $skins
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setForcedSkins(array $skins, $multicall = false)
	{
		if(!is_array($skins))
		{
			throw new InvalidArgumentException('skins = '.print_r($skins, true));
		}

		$skinParameter = array();
		foreach($skins as $key => $skin)
		{
			if($skin instanceof Structures\Skin)
			{
				$skinParameter[$key] = array();
				$skinParameter[$key]['Orig'] = $skin->orig;
				$skinParameter[$key]['Name'] = $skin->name;
				$skinParameter[$key]['Checksum'] = $skin->checksum;
				$skinParameter[$key]['Url'] = $skin->url;
			}
			elseif(!is_array($skin) || !array_key_exists('Orig', $skin) && !array_key_exists('Name', $skin) && !array_key_exists('Checksum',
					$skin) && !array_key_exists('Url', $skin))
			{
				throw new InvalidArgumentException('skins['.$key.'] = '.print_r($skins[$key], true));
			}
			else
			{
				$skinParameter[$key] = $skin;
			}
		}

		return $this->execute(ucfirst(__FUNCTION__), array($skinParameter), $multicall);
	}

	/**
	 * Get the current forced skins.
	 * @return Structures\Skin[]
	 */
	function getForcedSkins()
	{
		return Structures\Skin::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__)));
	}

	/**
	 * Returns the last error message for an internet connection.
	 * @return string
	 */
	function getLastConnectionErrorMessage()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new password for the referee mode.
	 * @param string $refereePassword
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setRefereePassword($refereePassword, $multicall = false)
	{
		if(!is_string($refereePassword))
		{
			throw new InvalidArgumentException('refereePassword = '.print_r($refereePassword, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($refereePassword), $multicall);
	}

	/**
	 * Get the password for referee mode if called as Admin or Super Admin,
	 * else returns if a password is needed or not.
	 * @return bool|string
	 */
	function getRefereePassword()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set the referee validation mode. 0 = validate the top3 players, 1 = validate all players. Only available to Admin.
	 * @param int $refereeMode
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setRefereeMode($refereeMode, $multicall = false)
	{
		if($refereeMode !== 0 && $refereeMode !== 1)
		{
			throw new InvalidArgumentException('refereeMode = '.print_r($refereeMode, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($refereeMode), $multicall);
	}

	/**
	 * Get the referee validation mode.
	 * @return bool|string
	 */
	function getRefereeMode()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set whether the game should use a variable validation seed or not.
	 * Requires a map restart to be taken into account.
	 * @param bool $enable
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setUseChangingValidationSeed($enable, $multicall = false)
	{
		if(!is_bool($enable))
		{
			throw new InvalidArgumentException('enable = '.print_r($enable, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($enable), $multicall);
	}

	/**
	 * Get the current and next value of UseChangingValidationSeed.
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getUseChangingValidationSeed()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set the maximum time the server must wait for inputs from the clients before dropping data, or '0' for auto-adaptation.
	 * Only used by ShootMania. Only available to Admin.
	 * @param int $latency
	 * @param bool $multicall
	 * @return bool
	 */
	function setClientInputsMaxLatency($latency, $multicall = false)
	{
		if(!is_int($latency))
		{
			throw new InvalidArgumentException('latency = '.print_r($latency, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($latency), $multicall);
	}

	/**
	 * Get the current ClientInputsMaxLatency. Only used by ShootMania.
	 * @return int
	 */
	function getClientInputsMaxLatency()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Sets whether the server is in warm-up phase or not.
	 * @param bool $enable
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setWarmUp($enable, $multicall = false)
	{
		if(!is_bool($enable))
		{
			throw new InvalidArgumentException('enable = '.print_r($enable, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($enable), $multicall);
	}

	/**
	 * Returns whether the server is in warm-up phase.
	 * @return bool
	 */
	function getWarmUp()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Get the current rules script.
	 * TODO Check if correct
	 * @return string
	 */
	function getModeScriptText()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set the rules script and restart.
	 * Only available to Admin.
	 * TODO Check if correct
	 * @param string $script
	 * @param bool $multicall
	 * @return bool
	 */
	function setModeScriptText($script, $multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array($script), $multicall);
	}

	/**
	 * Get the xml-rpc variables of the mode script.
	 * Only available to Admin.
	 * @return array
	 */
	function getModeScriptVariables()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set the variables of the rules script. Only available to Admin.
	 * @param array $variables
	 * @param bool $multicall
	 */
	function setModeScriptVariables($variables, $multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array($variables), $multicall);
	}

	/**
	 * Send an event to the mode script. Only available to Admin.
	 * @param string $param1
	 * @param string $param2
	 * @param bool $multicall
	 * @return bool
	 */
	function triggerModeScriptEvent($param1, $param2, $multicall = false)
	{
		if(!is_string($param1))
		{
			throw new InvalidArgumentException('param1 = '.print_r($param1, true));
		}
		if(!is_string($param2))
		{
			throw new InvalidArgumentException('param2 = '.print_r($param2, true));
		}
		return $this->execute(ucfirst(__FUNCTION__), array($param1, $param2), $multicall);
	}

	/**
	 * Send an event to the mode script. Only available to Admin.
	 * @param string $param1
	 * @param mixed[] $param2
	 * @param bool $multicall
	 * @return bool
	 */
	function triggerModeScriptEventArray($param1, $param2, $multicall = false)
	{
		if(!is_string($param1))
		{
			throw new InvalidArgumentException('param1 = '.print_r($param1, true));
		}
		if(!is_array($param2))
		{
			throw new InvalidArgumentException('param2 = '.print_r($param2, true));
		}
		return $this->execute(ucfirst(__FUNCTION__), array($param1, $param2), $multicall);
	}

	/**
	 * Returns the description of the current rules script,
	 * as a structure containing: Name, CompatibleTypes,
	 * Description and the settings available.
	 * @return Structures\ScriptInfo
	 */
	function getModeScriptInfo()
	{
		return Structures\ScriptInfo::fromArray($this->execute(ucfirst(__FUNCTION__)));
	}

	/**
	 * Returns the current parameters of the mode script.
	 * @return array
	 */
	function getModeScriptSettings()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set the parameters of the rules script. Only available to Admin.
	 * @param array $rules
	 * @param bool $multicall
	 * @return bool
	 */
	function setModeScriptSettings($rules, $multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array($rules), $multicall);
	}

	/**
	 * Send commands to the mode script.
	 * Only available to Admin.
	 * @param array $commands
	 * @param bool $multicall
	 * @return bool
	 */
	function sendModeScriptCommands(array $commands, $multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array($commands), $multicall);
	}

	/**
	 * Change the settings and send commands to the mode script.
	 * Only available to Admin.
	 * @param array $settings
	 * @param array $commands
	 * @param bool $multicall
	 * @return bool
	 */
	function setModeScriptSettingsAndCommands(array $settings, array $commands, $multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array($settings, $commands), $multicall);
	}

	/**
	 * Restarts the map, with an optional boolean parameter DontClearCupScores (only available in cup mode).
	 * @param bool $dontClearCupScores
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function restartMap($dontClearCupScores = false, $multicall = false)
	{
		if(!is_bool($dontClearCupScores))
		{
			throw new InvalidArgumentException('dontClearCupScores = '.print_r($dontClearCupScores, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($dontClearCupScores), $multicall);
	}

	/**
	 * Switch to next map, with an optional boolean parameter DontClearCupScores (only available in cup mode).
	 * @param bool $dontClearCupScores
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function nextMap($dontClearCupScores = false, $multicall = false)
	{
		if(!is_bool($dontClearCupScores))
		{
			throw new InvalidArgumentException('dontClearCupScores = '.print_r($dontClearCupScores, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($dontClearCupScores), $multicall);
	}

	/**
	 * Attempt to balance teams. Only available to Admin.
	 * @return bool
	 */
	function autoTeamBalance()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Stop the server.
	 * @param bool $multicall
	 * @return bool
	 */
	function stopServer($multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * In Rounds or Laps mode, force the end of round without waiting for all players to giveup/finish.
	 * @param bool $multicall
	 * @return bool
	 */
	function forceEndRound($multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Set new game settings using the struct passed as parameters.
	 * This struct must contain the following fields :
	 * GameMode, ChatTime, RoundsPointsLimit, RoundsUseNewRules, RoundsForcedLaps, TimeAttackLimit,
	 * TimeAttackSynchStartPeriod, TeamPointsLimit, TeamMaxPoints, TeamUseNewRules, LapsNbLaps, LapsTimeLimit,
	 * FinishTimeout, and optionally: AllWarmUpDuration, DisableRespawn, ForceShowAllOpponents, RoundsPointsLimitNewRules,
	 * TeamPointsLimitNewRules, CupPointsLimit, CupRoundsPerMap, CupNbWinners, CupWarmUpDuration.
	 * Requires a map restart to be taken into account.
	 * @param Structures\GameInfos $gameInfos
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setGameInfos(Structures\GameInfos $gameInfos, $multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array($gameInfos->toArray()), $multicall);
	}

	/**
	 * Optional parameter for compatibility:
	 * struct version (0 = united, 1 = forever).
	 * Returns a struct containing the current game settings, ie:
	 * GameMode, ChatTime, NbMap, RoundsPointsLimit, RoundsUseNewRules, RoundsForcedLaps,
	 * TimeAttackLimit, TimeAttackSynchStartPeriod, TeamPointsLimit, TeamMaxPoints, TeamUseNewRules,
	 * LapsNbLaps, LapsTimeLimit, FinishTimeout,
	 * additionally for version 1: AllWarmUpDuration, DisableRespawn, ForceShowAllOpponents, RoundsPointsLimitNewRules,
	 * TeamPointsLimitNewRules, CupPointsLimit, CupRoundsPerMap, CupNbWinners, CupWarmUpDuration.
	 * @param int $compatibility
	 * @return Structures\GameInfos
	 * @throws InvalidArgumentException
	 */
	function getCurrentGameInfo($compatibility = 1)
	{
		if($compatibility !== 1 && $compatibility != 0)
		{
			throw new InvalidArgumentException('compatibility = '.print_r($compatibility, true));
		}

		return Structures\GameInfos::fromArray($this->execute(ucfirst(__FUNCTION__), array($compatibility)));
	}

	/**
	 * Optional parameter for compatibility:
	 * struct version (0 = united, 1 = forever).
	 * Returns a struct containing the game settings for the next map, ie:
	 * GameMode, ChatTime, NbMap, RoundsPointsLimit, RoundsUseNewRules, RoundsForcedLaps,
	 * TimeAttackLimit, TimeAttackSynchStartPeriod, TeamPointsLimit, TeamMaxPoints, TeamUseNewRules,
	 * LapsNbLaps, LapsTimeLimit, FinishTimeout,
	 * additionally for version 1: AllWarmUpDuration, DisableRespawn, ForceShowAllOpponents, RoundsPointsLimitNewRules,
	 * TeamPointsLimitNewRules, CupPointsLimit, CupRoundsPerMap, CupNbWinners, CupWarmUpDuration.
	 * @param int $compatibility
	 * @return Structures\GameInfos
	 * @throws InvalidArgumentException
	 */
	function getNextGameInfo($compatibility = 1)
	{
		if($compatibility !== 1 && $compatibility != 0)
		{
			throw new InvalidArgumentException('compatibility = '.print_r($compatibility, true));
		}

		return Structures\GameInfos::fromArray($this->execute(ucfirst(__FUNCTION__), array($compatibility)));
	}

	/**
	 * Optional parameter for compatibility: struct version (0 = united, 1 = forever).
	 * Returns a struct containing two other structures,
	 * the first containing the current game settings and the second the game settings for next map.
	 * The first structure is named CurrentGameInfos and the second NextGameInfos.
	 * @param int $compatibility
	 * @return Structures\GameInfos[]
	 * @throws InvalidArgumentException
	 */
	function getGameInfos($compatibility = 1)
	{
		if($compatibility !== 1 && $compatibility != 0)
		{
			throw new InvalidArgumentException('compatibility = '.print_r($compatibility, true));
		}

		return Structures\GameInfos::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__), array($compatibility)));
	}

	/**
	 * Set a new game mode between Rounds (0), TimeAttack (1), Team (2), Laps (3), Stunts (4) and Cup (5).
	 * Requires a map restart to be taken into account.
	 * @param int $gameMode
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setGameMode($gameMode, $multicall = false)
	{
		if(!is_int($gameMode) && ($gameMode < 0 || $gameMode > 5))
		{
			throw new InvalidArgumentException('gameMode = '.print_r($gameMode, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($gameMode), $multicall);
	}

	/**
	 * Get the current game mode.
	 * @return int
	 */
	function getGameMode()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new chat time value in milliseconds (actually 'chat time' is the duration of the end race podium, 0 means no podium displayed.).
	 * @param int $chatTime
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setChatTime($chatTime, $multicall = false)
	{
		if(!is_int($chatTime))
		{
			throw new InvalidArgumentException('chatTime = '.print_r($chatTime, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($chatTime), $multicall);
	}

	/**
	 * Get the current and next chat time. The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getChatTime()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new finish timeout (for rounds/laps mode) value in milliseconds.
	 * 0 means default. 1 means adaptative to the duration of the map.
	 * Requires a map restart to be taken into account.
	 * @param int $finishTimeout
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setFinishTimeout($finishTimeout, $multicall = false)
	{
		if(!is_int($finishTimeout))
		{
			throw new InvalidArgumentException('chatTime = '.print_r($finishTimeout, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($finishTimeout), $multicall);
	}

	/**
	 * Get the current and next FinishTimeout. The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getFinishTimeout()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set whether to enable the automatic warm-up phase in all modes.
	 * 0 = no, otherwise it's the duration of the phase, expressed in number of rounds (in rounds/team mode),
	 * or in number of times the gold medal time (other modes).
	 * Requires a map restart to be taken into account.
	 * @param int $warmUpDuration
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setAllWarmUpDuration($warmUpDuration, $multicall = false)
	{
		if(!is_int($warmUpDuration))
		{
			throw new InvalidArgumentException('warmUpDuration = '.print_r($warmUpDuration, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($warmUpDuration), $multicall);
	}

	/**
	 * Get whether the automatic warm-up phase is enabled in all modes. The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getAllWarmUpDuration()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set whether to disallow players to respawn.
	 * Requires a map restart to be taken into account.
	 * @param bool $disableRespawn
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setDisableRespawn($disableRespawn, $multicall = false)
	{
		if(!is_bool($disableRespawn))
		{
			throw new InvalidArgumentException('disableRespawn = '.print_r($disableRespawn, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($disableRespawn), $multicall);
	}

	/**
	 * Get whether players are disallowed to respawn. The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getDisableRespawn()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set whether to override the players preferences and always display all opponents
	 * 0=no override, 1=show all, other value=minimum number of opponents.
	 * Requires a map restart to be taken into account.
	 * @param int $forceShowAllOpponents
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setForceShowAllOpponents($forceShowAllOpponents, $multicall = false)
	{
		if(!is_int($forceShowAllOpponents))
		{
			throw new InvalidArgumentException('forceShowAllOpponents = '.print_r($forceShowAllOpponents, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($forceShowAllOpponents), $multicall);
	}

	/**
	 * Get whether players are forced to show all opponents. The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getForceShowAllOpponents()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new rules script name for script mode. Only available to Admin.
	 * Requires a map restart to be taken into account.
	 * @param string $scriptName
	 * @param bool $multicall
	 * @return bool
	 */
	function setScriptName($scriptName, $multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array($scriptName), $multicall);
	}

	/**
	 * Get the current and next rules script name for script mode.
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getScriptName()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new time limit for time attack mode.
	 * Requires a map restart to be taken into account.
	 * @param int $timeAttackLimit
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setTimeAttackLimit($timeAttackLimit, $multicall = false)
	{
		if(!is_int($timeAttackLimit))
		{
			throw new InvalidArgumentException('timeAttackLimit = '.print_r($timeAttackLimit, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($timeAttackLimit), $multicall);
	}

	/**
	 * Get the current and next time limit for time attack mode. The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getTimeAttackLimit()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new synchronized start period for time attack mode.
	 * Requires a map restart to be taken into account.
	 * @param int $timeAttackSynchPeriod
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setTimeAttackSynchStartPeriod($timeAttackSynchPeriod, $multicall = false)
	{
		if(!is_int($timeAttackSynchPeriod))
		{
			throw new InvalidArgumentException('timeAttackSynchPeriod = '.print_r($timeAttackSynchPeriod, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($timeAttackSynchPeriod), $multicall);
	}

	/**
	 * Get the current and synchronized start period for time attack mode.
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getTimeAttackSynchStartPeriod()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new time limit for laps mode.
	 * Requires a map restart to be taken into account.
	 * @param int $lapsTimeLimit
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setLapsTimeLimit($lapsTimeLimit, $multicall = false)
	{
		if(!is_int($lapsTimeLimit))
		{
			throw new InvalidArgumentException('lapsTimeLimit = '.print_r($lapsTimeLimit, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($lapsTimeLimit), $multicall);
	}

	/**
	 * Get the current and next time limit for laps mode.
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getLapsTimeLimit()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new number of laps for laps mode.
	 * Requires a map restart to be taken into account.
	 * @param int $nbLaps
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setNbLaps($nbLaps, $multicall = false)
	{
		if(!is_int($nbLaps))
		{
			throw new InvalidArgumentException('nbLaps = '.print_r($nbLaps, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($nbLaps), $multicall);
	}

	/**
	 * Get the current and next number of laps for laps mode.
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getNbLaps()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new number of laps for rounds mode
	 * 0 = default, use the number of laps from the maps,
	 * otherwise forces the number of rounds for multilaps maps.
	 * Requires a map restart to be taken into account.
	 * @param int $roundForcedLaps
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setRoundForcedLaps($roundForcedLaps, $multicall = false)
	{
		if(!is_int($roundForcedLaps))
		{
			throw new InvalidArgumentException('roundForcedLaps = '.print_r($roundForcedLaps, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($roundForcedLaps), $multicall);
	}

	/**
	 * Get the current and next number of laps for rounds mode.
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getRoundForcedLaps()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new points limit for rounds mode (value set depends on UseNewRulesRound).
	 * Requires a map restart to be taken into account.
	 * @param int $roundPointsLimit
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setRoundPointsLimit($roundPointsLimit, $multicall = false)
	{
		if(!is_int($roundPointsLimit))
		{
			throw new InvalidArgumentException('roundPointsLimit = '.print_r($roundPointsLimit, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($roundPointsLimit), $multicall);
	}

	/**
	 * Get the current and next points limit for rounds mode (values returned depend on UseNewRulesRound).
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getRoundPointsLimit()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set the points used for the scores in rounds mode.
	 * Points is an array of decreasing integers for the players from the first to last.
	 * And you can add an optional boolean to relax the constraint checking on the scores.
	 * @param array $roundCustomPoints
	 * @param bool $relaxChecking
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setRoundCustomPoints(array $roundCustomPoints, $relaxChecking = false, $multicall = false)
	{
		if(!is_array($roundCustomPoints))
		{
			throw new InvalidArgumentException('roundCustomPoints = '.print_r($roundCustomPoints, true));
		}
		if(!is_bool($relaxChecking))
		{
			throw new InvalidArgumentException('relaxChecking = '.print_r($relaxChecking, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($roundCustomPoints, $relaxChecking), $multicall);
	}

	/**
	 * Gets the points used for the scores in rounds mode.
	 * @param bool $multicall
	 * @return array
	 */
	function getRoundCustomPoints()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set the points used for the scores in rounds mode.
	 * Points is an array of decreasing integers for the players from the first to last.
	 * And you can add an optional boolean to relax the constraint checking on the scores.
	 * @param bool $useNewRulesRound
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setUseNewRulesRound($useNewRulesRound, $multicall = false)
	{
		if(!is_bool($useNewRulesRound))
		{
			throw new InvalidArgumentException('useNewRulesRound = '.print_r($useNewRulesRound, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($useNewRulesRound), $multicall);
	}

	/**
	 * Gets the points used for the scores in rounds mode.
	 * @return array
	 */
	function getUseNewRulesRound()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new points limit for team mode (value set depends on UseNewRulesTeam).
	 * Requires a map restart to be taken into account.
	 * @param int $teamPointsLimit
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setTeamPointsLimit($teamPointsLimit, $multicall = false)
	{
		if(!is_int($teamPointsLimit))
		{
			throw new InvalidArgumentException('teamPointsLimit = '.print_r($teamPointsLimit, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($teamPointsLimit), $multicall);
	}

	/**
	 * Get the current and next points limit for team mode (values returned depend on UseNewRulesTeam).
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getTeamPointsLimit()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new number of maximum points per round for team mode.
	 * Requires a map restart to be taken into account.
	 * @param int $maxPointsTeam
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setMaxPointsTeam($maxPointsTeam, $multicall = false)
	{
		if(!is_int($maxPointsTeam))
		{
			throw new InvalidArgumentException('maxPointsTeam = '.print_r($maxPointsTeam, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($maxPointsTeam), $multicall);
	}

	/**
	 * Get the current and next number of maximum points per round for team mode.
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getMaxPointsTeam()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set if new rules are used for team mode.
	 * Requires a map restart to be taken into account.
	 * @param bool $useNewRulesTeam
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setUseNewRulesTeam($useNewRulesTeam, $multicall = false)
	{
		if(!is_bool($useNewRulesTeam))
		{
			throw new InvalidArgumentException('useNewRulesTeam = '.print_r($useNewRulesTeam, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($useNewRulesTeam), $multicall);
	}

	/**
	 * Get if the new rules are used for team mode (Current and next values).
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getUseNewRulesTeam()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set the points needed for victory in Cup mode.
	 * Requires a map restart to be taken into account.
	 * @param int $pointsLimit
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setCupPointsLimit($pointsLimit, $multicall = false)
	{
		if(!is_int($pointsLimit))
		{
			throw new InvalidArgumentException('pointsLimit = '.print_r($pointsLimit, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($pointsLimit), $multicall);
	}

	/**
	 * Get the points needed for victory in Cup mode.
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getCupPointsLimit()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Sets the number of rounds before going to next map in Cup mode.
	 * Requires a map restart to be taken into account.
	 * @param int $roundsPerMap
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setCupRoundsPerMap($roundsPerMap, $multicall = false)
	{
		if(!is_int($roundsPerMap))
		{
			throw new InvalidArgumentException('roundsPerMap = '.print_r($roundsPerMap, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($roundsPerMap), $multicall);
	}

	/**
	 * Get the number of rounds before going to next map in Cup mode.
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @param bool $multicall
	 * @return array
	 */
	function getCupRoundsPerMap()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set whether to enable the automatic warm-up phase in Cup mode.
	 * 0 = no, otherwise it's the duration of the phase, expressed in number of rounds.
	 * Requires a map restart to be taken into account.
	 * @param int $warmUpDuration
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setCupWarmUpDuration($warmUpDuration, $multicall = false)
	{
		if(!is_int($warmUpDuration))
		{
			throw new InvalidArgumentException('warmUpDuration = '.print_r($warmUpDuration, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($warmUpDuration), $multicall);
	}

	/**
	 * Get whether the automatic warm-up phase is enabled in Cup mode.
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getCupWarmUpDuration()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set the number of winners to determine before the match is considered over.
	 * Requires a map restart to be taken into account.
	 * @param int $nbWinners
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setCupNbWinners($nbWinners, $multicall = false)
	{
		if(!is_int($nbWinners))
		{
			throw new InvalidArgumentException('nbWinners = '.print_r($nbWinners, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($nbWinners), $multicall);
	}

	/**
	 * Get the number of winners to determine before the match is considered over.
	 * The struct returned contains two fields CurrentValue and NextValue.
	 * @return array
	 */
	function getCupNbWinners()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Returns the current map index in the selection, or -1 if the map is no longer in the selection.
	 * @return int
	 */
	function getCurrentMapIndex()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Returns the map index in the selection that will be played next (unless the current one is restarted...)
	 * @return int
	 */
	function getNextMapIndex()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Sets the map index in the selection that will be played next (unless the current one is restarted...)
	 * @param int $nextMapIndex
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function setNextMapIndex($nextMapIndex, $multicall = false)
	{
		if(!is_int($nextMapIndex))
		{
			throw new InvalidArgumentException('nextMapIndex = '.print_r($nextMapIndex, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($nextMapIndex), $multicall);
	}

	/**
	 * Immediately jumps to the map designated by the index in the selection
	 * @param int $nextMapIndex
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function jumpToMapIndex($mapIndex, $multicall = false)
	{
		if(!is_int($mapIndex))
		{
			throw new InvalidArgumentException('mapIndex = '.print_r($mapIndex, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($mapIndex), $multicall);
	}

	/**
	 * Set Team names and colors. Only available to Admin.
	 * @param string $teamName1
	 * @param float $teamColor1
	 * @param string $team1Country
	 * @param string $teamName2
	 * @param float $teamColor2
	 * @param string $team2Country
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 * @deprecated since version 2013-04-11
	 */
	function setTeamInfo($teamName1, $teamColor1, $team1Country, $teamName2, $teamColor2, $team2Country, $multicall = false)
	{
		if(!is_float($teamColor1))
		{
			throw new InvalidArgumentException('teamColor1 = '.print_r($teamColor1, true));
		}
		if(!is_float($teamColor2))
		{
			throw new InvalidArgumentException('teamColor2 = '.print_r($teamColor2, true));
		}
		if(!is_string($teamName1))
		{
			throw new InvalidArgumentException('teamName1 = '.print_r($teamName1, true));
		}
		if(!is_string($teamName2))
		{
			throw new InvalidArgumentException('teamName2 = '.print_r($teamName2, true));
		}
		return $this->execute(ucfirst(__FUNCTION__),
				array('unused', 0., 'World', $teamName1, $teamColor1, $team1Country, $teamName2, $teamColor2, $team2Country),
				$multicall);
	}

	/**
	 * Return Team info for a given clan (0 = no clan, 1, 2).
	 * The structure contains: name, zonePath, city, emblemUrl, huePrimary, hueSecondary, rGB, clubLinkUrl.
	 * Only available to Admin.
	 * @param int $teamId
	 * @return Structures\Team
	 * @throws InvalidArgumentException
	 */
	function getTeamInfo($teamId)
	{
		if(!is_int($teamId))
		{
			throw new InvalidArgumentException('teamId = '.print_r($teamId, true));
		}
		return Structures\Team::fromArray($this->execute(ucfirst(__FUNCTION__), array($teamId)));
	}

	/**
	 * Set the clublinks to use for the two clans.
	 * Only available to Admin.
	 * @param string $team1ClubLink
	 * @param string $team2ClubLink
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setForcedClubLinks($team1ClubLink, $team2ClubLink, $multicall = false)
	{
		if(!is_string($team1ClubLink))
		{
			throw new InvalidArgumentException('team1ClubLink = '.print_r($team1ClubLink, true));
		}
		if(!is_string($team2ClubLink))
		{
			throw new InvalidArgumentException('team2ClubLink = '.print_r($team2ClubLink, true));
		}
		return $this->execute(ucfirst(__FUNCTION__), array($team1ClubLink, $team2ClubLink), $multicall);
	}

	/**
	 * Get the forced clublinks.
	 * @return array
	 */
	function getForcedClubLinks()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * (debug tool) Connect a fake player to the server and returns the login.
	 * Only available to Admin.
	 * @param bool $multicall
	 * @return string
	 */
	function connectFakePlayer($multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * (debug tool) Disconnect a fake player, or all the fake players if login is '*'.
	 * Only available to Admin.
	 * @param string $fakePlayerLogin
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function disconnectFakePlayer($fakePlayerLogin, $multicall = false)
	{
		if(!is_string($fakePlayerLogin))
		{
			throw new InvalidArgumentException('fakePlayerLogin = '.print_r($fakePlayerLogin, true));
		}
		return $this->execute(ucfirst(__FUNCTION__), array($fakePlayerLogin), $multicall);
	}

	/**
	 * Returns the token infos for a player.
	 * The returned structure is { TokenCost, CanPayToken }.
	 * @param Structures\Player|string $player
	 * @return array
	 */
	function getDemoTokenInfosForPlayer($player)
	{
		$login = $this->getLogin($player);
		return (object) $this->execute(ucfirst(__FUNCTION__), array($login));
	}

	/**
	 * Disable player horns. Only available to Admin.
	 * @param bool $disable
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function disableHorns($disable, $multicall = false)
	{
		if(!is_bool($disable))
		{
			throw new InvalidArgumentException('disable = '.print_r($disable, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($disable), $multicall);
	}

	/**
	 * Returns whether the horns are disabled.
	 * @return bool
	 */
	function areHornsDisabled()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Disable the automatic mesages when a player connects/disconnects from the server.
	 * Only available to Admin.
	 * @param bool $disable
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function disableServiceAnnounces($disable = true, $multicall = false)
	{
		if(!is_bool($disable))
		{
			throw new InvalidArgumentException('disable = '.print_r($disable, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($disable), $multicall);
	}

	/**
	 * Returns whether the automatic mesages are disabled.
	 * @return bool
	 */
	function areServiceAnnouncesDisabled()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Returns a struct containing the infos for the current map.
	 * The struct contains the following fields : Name, UId, FileName,
	 * Author, Environnement, Mood, BronzeTime, SilverTime, GoldTime,
	 * AuthorTime, CopperPrice, LapRace, NbLaps and NbCheckpoints.
	 * @return Structures\Map
	 */
	function getCurrentMapInfo()
	{
		return Structures\Map::fromArray($this->execute(ucfirst(__FUNCTION__)));
	}

	/**
	 * Returns a struct containing the infos for the next map.
	 * The struct contains the following fields : Name, UId, FileName,
	 * Author, Environnement, Mood, BronzeTime, SilverTime, GoldTime,
	 * AuthorTime, CopperPrice, LapRace, NbLaps and NbCheckpoints.
	 * (NbLaps and NbCheckpoints are also present but always set to -1)
	 * @return Structures\Map
	 */
	function getNextMapInfo()
	{
		return Structures\Map::fromArray($this->execute(ucfirst(__FUNCTION__)));
	}

	/**
	 * Returns a struct containing the infos for the map with the specified filename.
	 * The struct contains the following fields : Name, UId, FileName,
	 * Author, Environnement, Mood, BronzeTime, SilverTime, GoldTime,
	 * AuthorTime, CopperPrice, LapRace, NbLaps and NbCheckpoints.
	 * (NbLaps and NbCheckpoints are also present but always set to -1)
	 * @param string $filename
	 * @return Structures\Map
	 * @throws InvalidArgumentException
	 */
	function getMapInfo($filename)
	{
		if(!is_string($filename))
		{
			throw new InvalidArgumentException('filename = '.print_r($filename, true));
		}

		$temp = $this->execute(ucfirst(__FUNCTION__), array($filename));
		return Structures\Map::fromArray($temp);
	}

	/**
	 * Returns a boolean if the map with the specified filename matches the current server settings.
	 * @param string $filename
	 * @return bool
	 */
	function checkMapForCurrentServerParams($filename)
	{
		if(!is_string($filename))
		{
			throw new InvalidArgumentException('filename = '.print_r($filename, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($filename));
	}

	/**
	 * Returns a list of maps among the current selection of the server.
	 * This method take two parameters.
	 * The first parameter specifies the maximum number of infos to be returned,
	 * the second one the starting index in the selection.
	 * The list is an array of structures. Each structure contains the following fields : Name, UId, FileName, Environnement, Author, GoldTime and CopperPrice.
	 * @param int $length specifies the maximum number of infos to be returned
	 * @param int $offset specifies the starting index in the list
	 * @return Structures\Map[] The list is an array of Map
	 * @throws InvalidArgumentException
	 */
	function getMapList($length, $offset)
	{
		if(!is_int($length))
		{
			throw new InvalidArgumentException('length = '.print_r($length, true));
		}
		if(!is_int($offset))
		{
			throw new InvalidArgumentException('offset = '.print_r($offset, true));
		}

		return Structures\Map::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__), array($length, $offset)));
	}

	/**
	 * Add the map with the specified filename at the end of the current selection.
	 * @param string $filename
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function addMap($filename, $multicall = false)
	{
		if(!is_string($filename))
		{
			throw new InvalidArgumentException('filename = '.print_r($filename, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Add the list of maps with the specified filename at the end of the current selection.
	 * @param array $filenames
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return int
	 */
	function addMapList(array $filenames, $multicall = false)
	{
		if(!is_array($filenames))
		{
			throw new InvalidArgumentException('filenames = '.print_r($filenames, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($filenames), $multicall);
	}

	/**
	 * Remove the map with the specified filename from the current selection.
	 * @param string $filename
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function removeMap($filename, $multicall = false)
	{
		if(!is_string($filename))
		{
			throw new InvalidArgumentException('filename = '.print_r($filename, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Remove the list of maps with the specified filenames from the current selection.
	 * The list of maps to remove is an array of strings.
	 * @param array $filenames
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return int
	 */
	function removeMapList(array $filenames, $multicall = false)
	{
		if(!is_array($filenames))
		{
			throw new InvalidArgumentException('filenames = '.print_r($filenames, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($filenames), $multicall);
	}

	/**
	 * Insert the map with the specified filename after the current map.
	 * @param string $filename
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function insertMap($filename, $multicall = false)
	{
		if(!is_string($filename))
		{
			throw new InvalidArgumentException('filename = '.print_r($filename, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Insert the list of maps with the specified filenames after the current map.
	 * The list of maps to remove is an array of strings.
	 * @param array $filenames
	 * @throws InvalidArgumentException
	 * @return int
	 */
	function insertMapList(array $filenames, $multicall = false)
	{
		if(!is_array($filenames))
		{
			throw new InvalidArgumentException('filenames = '.print_r($filenames, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($filenames), $multicall);
	}

	/**
	 * Set as next map the one with the specified filename, if it is present in the selection.
	 * @param string $filename
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return bool
	 */
	function chooseNextMap($filename, $multicall = false)
	{
		if(!is_string($filename))
		{
			throw new InvalidArgumentException('filename = '.print_r($filename, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Set as next maps the list of maps with the specified filenames, if they are present in the selection.
	 * The list of maps to remove is an array of strings.
	 * @param array $filenames
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return int
	 */
	function chooseNextMapList(array $filenames, $multicall = false)
	{
		if(!is_array($filenames))
		{
			throw new InvalidArgumentException('filenames = '.print_r($filenames, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($filenames), $multicall);
	}

	/**
	 * Set a list of maps defined in the playlist with the specified filename
	 * as the current selection of the server, and load the gameinfos from the same file.
	 * @param string $filename
	 * @throws InvalidArgumentException
	 * @return int
	 */
	function loadMatchSettings($filename, $multicall = false)
	{
		if(!is_string($filename))
		{
			throw new InvalidArgumentException('filename = '.print_r($filename, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Add a list of maps defined in the playlist with the specified filename at the end of the current selection.
	 * @param string $filename
	 * @throws InvalidArgumentException
	 * @return int
	 */
	function appendPlaylistFromMatchSettings($filename, $multicall = false)
	{
		if(!is_string($filename))
		{
			throw new InvalidArgumentException('filename = '.print_r($filename, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Save the current selection of map in the playlist with the specified filename, as well as the current gameinfos.
	 * @param string $filename
	 * @param bool $multicall
	 * @throws InvalidArgumentException
	 * @return int
	 */
	function saveMatchSettings($filename, $multicall = false)
	{
		if(!is_string($filename))
		{
			throw new InvalidArgumentException('filename = '.print_r($filename, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Insert a list of maps defined in the playlist with the specified filename after the current map.
	 * @param string $filename
	 * @throws InvalidArgumentException
	 * @return int
	 */
	function insertPlaylistFromMatchSettings($filename, $multicall = false)
	{
		if(!is_string($filename))
		{
			throw new InvalidArgumentException('filename = '.print_r($filename, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Returns the list of players on the server. This method take two parameters.
	 * The first parameter specifies the maximum number of infos to be returned,
	 * the second one the starting index in the list,
	 * an optional 3rd parameter is used for compatibility: struct version (0 = united, 1 = forever, 2 = forever, including the servers).
	 * The list is an array of Structures\Player.
	 * LadderRanking is 0 when not in official mode,
	 * Flags = ForceSpectator(0,1,2) + IsReferee * 10 + IsPodiumReady * 100 + IsUsingStereoscopy * 1000 +
	 * IsManagedByAnOtherServer * 10000 + IsServer * 100000 + HasPlayerSlot * 1000000
	 * SpectatorStatus = Spectator + TemporarySpectator * 10 + PureSpectator * 100 + AutoTarget * 1000 + CurrentTargetId * 10000
	 * @param int $length specifies the maximum number of infos to be returned
	 * @param int $offset specifies the starting index in the list
	 * @param int $compatibility
	 * @return Structures\Player[] The list is an array of Structures\Player
	 * @throws InvalidArgumentException
	 */
	function getPlayerList($length, $offset, $compatibility = 1)
	{
		if(!is_int($length))
		{
			throw new InvalidArgumentException('length = '.print_r($length, true));
		}
		if(!is_int($offset))
		{
			throw new InvalidArgumentException('offset = '.print_r($offset, true));
		}
		if(!is_int($compatibility))
		{
			throw new InvalidArgumentException('compatibility = '.print_r($compatibility, true));
		}

		return Structures\Player::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__), array($length, $offset, $compatibility)));
	}

	/**
	 * Returns a object of type Structures\Player containing the infos on the player with the specified login,
	 * with an optional parameter for compatibility: struct version (0 = united, 1 = forever).
	 * The structure is identical to the ones from GetPlayerList. Forever PlayerInfo struct is:
	 * Login, NickName, PlayerId, TeamId, SpectatorStatus, LadderRanking, and Flags.
	 * LadderRanking is 0 when not in official mode,
	 * Flags = ForceSpectator(0,1,2) + IsReferee * 10 + IsPodiumReady * 100 + IsUsingStereoscopy * 1000 +
	 * IsManagedByAnOtherServer * 10000 + IsServer * 100000 + HasPlayerSlot * 1000000
	 * SpectatorStatus = Spectator + TemporarySpectator * 10 + PureSpectator * 100 + AutoTarget * 1000 + CurrentTargetId * 10000
	 * @param int $playerLogin
	 * @param int $compatibility
	 * @return Structures\Player
	 * @throws InvalidArgumentException
	 */
	function getPlayerInfo($playerLogin, $compatibility = 1)
	{
		if(!is_string($playerLogin))
		{
			throw new InvalidArgumentException('playerLogin = '.print_r($playerLogin, true));
		}
		if(!is_int($compatibility))
		{
			throw new InvalidArgumentException('compatibility = '.print_r($compatibility, true));
		}

		return Structures\Player::fromArray($this->execute(ucfirst(__FUNCTION__), array($playerLogin, $compatibility)));
	}

	/**
	 * Returns an object of type Structures\Player containing the infos on the player with the specified login.
	 * The structure contains the following fields :
	 * Login, NickName, PlayerId, TeamId, IPAddress, DownloadRate, UploadRate, Language, IsSpectator,
	 * IsInOfficialMode, a structure named Avatar, an array of structures named Skins, a structure named LadderStats,
	 * HoursSinceZoneInscription and OnlineRights (0: nations account, 3: united account).
	 * Each structure of the array Skins contains two fields Environnement and a struct PackDesc.
	 * Each structure PackDesc, as well as the struct Avatar, contains two fields FileName and Checksum.
	 * @param int $playerLogin
	 * @return Structures\Player
	 * @throws InvalidArgumentException
	 */
	function getDetailedPlayerInfo($playerLogin)
	{
		if(!is_string($playerLogin))
		{
			throw new InvalidArgumentException('playerLogin = '.print_r($playerLogin, true));
		}

		return Structures\Player::fromArray($this->execute(ucfirst(__FUNCTION__), array($playerLogin)));
	}

	/**
	 * Returns an object of Structures\Player type containing the infos on the player with the specified login.
	 * The structure contains the following fields : Login, NickName, PlayerId, TeamId, IPAddress, DownloadRate, UploadRate,
	 * Language, IsSpectator, IsInOfficialMode, a structure named Avatar, an array of structures named Skins, a structure named LadderStats,
	 * HoursSinceZoneInscription and OnlineRights (0: nations account, 3: united account).
	 * Each structure of the array Skins contains two fields Environnement and a struct PackDesc.
	 * Each structure PackDesc, as well as the struct Avatar, contains two fields FileName and Checksum.
	 * @param int $compatibility
	 * @return Structures\Player
	 * @throws InvalidArgumentException
	 */
	function getMainServerPlayerInfo($compatibility = 1)
	{
		if(!is_int($compatibility))
		{
			throw new InvalidArgumentException('compatibility = '.print_r($compatibility, true));
		}

		return Structures\Player::fromArray($this->execute(ucfirst(__FUNCTION__), array($compatibility)));
	}

	/**
	 * Returns the current rankings for the race in progress.
	 * (in team mode, the scores for the two teams are returned.
	 * In other modes, it's the individual players' scores) This method take two parameters.
	 * The first parameter specifies the maximum number of infos to be returned,
	 * the second one the starting index in the ranking.
	 * The ranking returned is a list of Structures\Player.
	 * It also contains an array BestCheckpoints that contains the checkpoint times for the best race.
	 * @param int $length specifies the maximum number of infos to be returned
	 * @param int $offset specifies the starting index in the list
	 * @return Structures\Player[] The list is an array of Structures\Player.
	 * @throws InvalidArgumentException
	 */
	function getCurrentRanking($length, $offset)
	{
		if(!is_int($length))
		{
			throw new InvalidArgumentException('length = '.print_r($length, true));
		}
		if(!is_int($offset))
		{
			throw new InvalidArgumentException('offset = '.print_r($offset, true));
		}

		return Structures\Player::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__), array($length, $offset)));
	}

	/**
	 * Returns the current ranking for the race in progressof the player with the specified login (or list of comma-separated logins).
	 * The ranking returned is a list of structures that contains the following fields :
	 * Login, NickName, PlayerId, Rank, BestTime, Score, NbrLapsFinished and LadderScore.
	 * It also contains an array BestCheckpoints that contains the checkpoint times for the best race.
	 * @param Structures\Player|Structures\Player[] $player
	 * @throws InvalidArgumentException
	 * @return Structures\Player[] The list is an array of Structures\Player.
	 */
	function getCurrentRankingForLogin($player = null)
	{
		$login = $this->getLogin($player) ? : '';

		return Structures\Player::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__), array($login)));
	}

	/**
	 * Returns the current winning team for the race in progress. (-1: if not in team mode, or draw match)
	 * @return int -1, 0 or 1
	 */
	function getCurrentWinnerTeam()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Force the scores of the current game. Only available in rounds and team mode.
	 * You have to pass an array of structs {int PlayerId, int Score}. And a boolean SilentMode -
	 * if true, the scores are silently updated (only available for SuperAdmin), allowing an external controller to do its custom counting...
	 * @param array $scores
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function forceScores(array $scores, $silentMode = false, $multicall = false)
	{
		if(!is_array($scores))
		{
			throw new InvalidArgumentException('scores = '.print_r($scores, true));
		}

		for($i = 0; $i < count($scores); $i++)
		{
			if(!is_int($scores[$i]['PlayerId']))
			{
				throw new InvalidArgumentException('score['.$i.'][\'PlayerId\'] = '.print_r($scores[$i]['PlayerId'], true));
			}
			if(!is_int($scores[$i]['Score']))
			{
				throw new InvalidArgumentException('score['.$i.'][\'Score\'] = '.print_r($scores[$i]['Score'], true));
			}
		}

		return $this->execute(ucfirst(__FUNCTION__), array($scores, $silentMode), $multicall);
	}

	/**
	 * Force the team of the player. Only available in team mode. You have to pass the login and the team number (0 or 1).
	 * @param Structures\Player|string $player
	 * @param int $teamNumber
	 * @param bool $multicall
	 * @return bool
	 */
	function forcePlayerTeam($player, $teamNumber, $multicall = false)
	{
		if(!($login = $this->getLogin($player)))
		{
			throw new InvalidArgumentException('player must be set');
		}
		if($teamNumber !== 0 && $teamNumber !== 1)
		{
			throw new InvalidArgumentException('teamNumber = '.print_r($teamNumber, true));
		}

		return $this->execute('ForcePlayerTeam', array($login, $teamNumber), $multicall);
	}

	/**
	 * Force the spectating status of the player.
	 * You have to pass the login and the spectator mode
	 * (0: user selectable, 1: spectator, 2: player, 3: spectator but keep selectable).
	 * Only available to Admin.
	 * @param Structures\Player|string $player
	 * @param int $spectatorMode
	 * @param bool $multicall
	 * @return bool
	 */
	function forceSpectator($player, $spectatorMode, $multicall = false)
	{
		if(!($login = $this->getLogin($player)))
		{
			throw new InvalidArgumentException('player must be set');
		}
		if(array_search($spectatorMode, range(0, 3), true) === false)
		{
			throw new InvalidArgumentException('spectatorMode = '.print_r($spectatorMode, true));
		}

		return $this->execute('ForceSpectator', array($login, $spectatorMode), $multicall);
	}

	/**
	 * Force spectators to look at a specific player. You have to pass the login of the spectator (or '' for all) and
	 * the login of the target (or '' for automatic), and an integer for the camera type to use (-1 = leave unchanged, 0 = replay, 1 = follow, 2 = free).
	 * @param Structures\Player|string $player
	 * @param Structures\Player|string $target
	 * @param int $cameraType
	 * @param bool $multicall
	 * @return bool
	 */
	function forceSpectatorTarget($player, $target, $cameraType, $multicall = false)
	{
		if(!($playerLogin = $this->getLogin($player)))
		{
			throw new InvalidArgumentException('player must be set');
		}
		if(!($targetLogin = $this->getLogin($target)))
		{
			throw new InvalidArgumentException('target must be set');
		}
		if($cameraType !== -1 && $cameraType !== 0 && $cameraType !== 1 && $cameraType !== 2)
		{
			throw new InvalidArgumentException('cameraType = '.print_r($cameraType, true));
		}

		return $this->execute('ForceSpectatorTarget', array($playerLogin, $targetLogin, $cameraType), $multicall);
	}

	/**
	 * Pass the login of the spectator. A spectator that once was a player keeps his player slot, so that he can go back to race mode.
	 * Calling this function frees this slot for another player to connect.
	 * @param Structures\Player|string $player
	 * @param bool $multicall
	 * @return bool
	 */
	function spectatorReleasePlayerSlot($player, $multicall = false)
	{
		if(!($login = $this->getLogin($player)))
		{
			throw new InvalidArgumentException('player must be set');
		}
		return $this->execute('SpectatorReleasePlayerSlot', array($login), $multicall);
	}

	/**
	 * Enable control of the game flow: the game will wait for the caller to validate state transitions.
	 * @param bool $flowControlEnable
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function manualFlowControlEnable($flowControlEnable, $multicall = false)
	{
		if(!is_bool($flowControlEnable))
		{
			throw new InvalidArgumentException('flowControlEnable = '.print_r($flowControlEnable, true));
		}

		return $this->execute(ucfirst(__FUNCTION__), array($flowControlEnable), $multicall);
	}

	/**
	 * Allows the game to proceed.
	 * @param bool $multicall
	 * @return bool
	 */
	function manualFlowControlProceed($multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Returns whether the manual control of the game flow is enabled. 0 = no, 1 = yes by the xml-rpc client making the call, 2 = yes, by some other xml-rpc client.
	 * @return int
	 */
	function manualFlowControlIsEnabled()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Returns the transition that is currently blocked, or '' if none. (That's exactly the value last received by the callback.)
	 * @return string
	 */
	function manualFlowControlGetCurTransition()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Returns the current match ending condition.
	 * Return values are: 'Playing', 'ChangeMap' or 'Finished'.
	 * @param bool $multicall
	 * @return string
	 */
	function checkEndMatchCondition()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Returns an object Structures\NetworkStats.
	 * The structure contains the following fields : Uptime, NbrConnection, MeanConnectionTime, MeanNbrPlayer,
	 * RecvNetRate, SendNetRate, TotalReceivingSize, TotalSendingSize and an array of structures named PlayerNetInfos.
	 * Each structure of the array PlayerNetInfos is a Structures\Player object contains the following fields : Login, IPAddress, LastTransferTime, DeltaBetweenTwoLastNetState, PacketLossRate.
	 * @return Structures\NetworkStats
	 */
	function getNetworkStats()
	{
		return Structures\NetworkStats::fromArray($this->execute(ucfirst(__FUNCTION__)));
	}

	/**
	 * Start a server on lan, using the current configuration.
	 * @param bool $multicall
	 * @return bool
	 */
	function startServerLan($multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Start a server on internet using the 'Login' and 'Password' specified in the struct passed as parameters.
	 * @param array $ids
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function startServerInternet($multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Returns the current status of the server.
	 * @return Structures\Status
	 */
	function getStatus()
	{
		return Structures\Status::fromArray($this->execute(ucfirst(__FUNCTION__)));
	}

	/**
	 * Quit the application.
	 * @param bool $multicall
	 * @return bool
	 */
	function quitGame($multicall = false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Returns the path of the game datas directory.
	 * @return string
	 */
	function gameDataDirectory()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Returns the path of the maps directory.
	 * @return string
	 */
	function getMapsDirectory()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Returns the path of the skins directory.
	 * @return string
	 */
	function getSkinsDirectory()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Returns the login of the given player
	 * @param mixed $player Structures\Player or string
	 * @return string
	 */
	private function getLogin($player)
	{
		if(is_string($player)) return $player;
		if($player instanceof Structures\Player) return $player->login;
		return null;
	}

	/**
	 * Returns logins of given players
	 * @param mixed $player Structures\Player or string or array
	 * @return string
	 */
	private function getLogins($players)
	{
		if(is_array($players))
		{
			$logins = array();
			foreach($players as $player)
			{
				if(($login = $this->getLogin($player))) $logins[] = $login;
				else return null;
			}

			return implode(',', $logins);
		}
		return $this->getLogin($players);
	}

}

/**
 * Exception Dedicated to Invalid Argument Error on Request Call
 */
class InvalidArgumentException extends \Exception {}

?>
