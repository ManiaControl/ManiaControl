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
	const API_2011_08_01 = '2011-08-01';
	const API_2011_10_06 = '2011-10-06';
	const API_2012_06_19 = '2012-06-19';
	const API_2013_04_16 = '2013-04-16';

	/** @var Connection[] */
	protected static $instances = array();
	/** @var int[] */
	private static $levels = array(
		null => -1,
		'User' => 0,
		'Admin' => 1,
		'SuperAdmin' => 2
	);

	/** @var Xmlrpc\GbxRemote */
	protected $xmlrpcClient;
	/** @var string */
	protected $user;

	/**
	 * @param string $host
	 * @param int $port
	 * @param int $timeout (in s)
	 * @param string $user
	 * @param string $password
	 * @param string $apiVersion
	 * @return Connection
	 */
	static function factory($host='127.0.0.1', $port=5000, $timeout=5, $user='SuperAdmin', $password='SuperAdmin', $apiVersion=self::API_2013_04_16)
	{
		$key = $host.':'.$port;
		if(!isset(self::$instances[$key]))
			self::$instances[$key] = new self($host, $port, $timeout);
		self::$instances[$key]->authenticate($user, $password);
		self::$instances[$key]->setApiVersion($apiVersion);

		return self::$instances[$key];
	}

	/**
	 * @param Connection|string $hostOrConnection
	 * @param int $port
	 */
	static function delete($hostOrConnection, $port=null)
	{
		if($hostOrConnection instanceof Connection)
			$key = array_search($hostOrConnection, self::$instances);
		else
			$key = $hostOrConnection.':'.$port;
		if(isset(self::$instances[$key]))
		{
			self::$instances[$key]->terminate();
			unset(self::$instances[$key]);
		}
	}

	/**
	 * Change client timeouts
	 * @param int $read read timeout (in ms), 0 to leave unchanged
	 * @param int $write write timeout (in ms), 0 to leave unchanged
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
	 */
	protected function __construct($host, $port, $timeout)
	{
		$this->xmlrpcClient = new Xmlrpc\GbxRemote($host, $port, $timeout);
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
	 * Return pending callbacks
	 * @return mixed[]
	 */
	function executeCallbacks()
	{
		return $this->xmlrpcClient->getCallbacks();
	}

	/**
	 * Execute the calls in queue and return the result
	 * @return mixed
	 */
	function executeMulticall()
	{
		return $this->xmlrpcClient->multiquery();
	}

	/**
	 * Add a call in queue. It will be executed by the next Call from the user to executeMulticall
	 * @param string $methodName
	 * @param mixed[] $params
	 * @param bool $multicall True to queue the request or false to execute it immediately
	 * @return mixed
	 */
	protected function execute($methodName, $params=array(), $multicall=false)
	{
		if($multicall)
			$this->xmlrpcClient->addCall($methodName, $params);
		else
			return $this->xmlrpcClient->query($methodName, $params);
	}

	/**
	 * Allow user authentication by specifying a login and a password, to gain access to the set of functionalities corresponding to this authorization level.
	 * @param string $user
	 * @param string $password
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function authenticate($user, $password)
	{
		if(!is_string($user) || !isset(self::$levels[$user]))
			throw new InvalidArgumentException('user = '.print_r($user, true));
		if(self::$levels[$this->user] >= self::$levels[$user])
			return true;

		if(!is_string($password))
			throw new InvalidArgumentException('password = '.print_r($password, true));

		$res = $this->execute(ucfirst(__FUNCTION__), array($user, $password));
		if($res)
			$this->user = $user;
		return $res;
	}

	/**
	 * Change the password for the specified login/user.
	 * Only available to SuperAdmin.
	 * @param string $user
	 * @param string $password
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function changeAuthPassword($user, $password, $multicall=false)
	{
		if(!is_string($user) || !isset(self::$levels[$user]))
			throw new InvalidArgumentException('user = '.print_r($user, true));
		if(!is_string($password))
			throw new InvalidArgumentException('password = '.print_r($password, true));

		return $this->execute(ucfirst(__FUNCTION__), array($user, $password), $multicall);
	}

	/**
	 * Allow the GameServer to call you back.
	 * @param bool $enable
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function enableCallbacks($enable, $multicall=false)
	{
		if(!is_bool($enable))
			throw new InvalidArgumentException('enable = '.print_r($enable, true));

		return $this->execute(ucfirst(__FUNCTION__), array($enable), $multicall);
	}

	/**
	 * Define the wanted api.
	 * @param string $version
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setApiVersion($version, $multicall=false)
	{
		if(!is_string($version))
			throw new InvalidArgumentException('version = '.print_r($version, true));

		return $this->execute(ucfirst(__FUNCTION__), array($version), $multicall);
	}

	/**
	 * Returns a struct with the Name, TitleId, Version, Build and ApiVersion of the application remotely controlled.
	 * @return Structures\Version
	 */
	function getVersion()
	{
		return Structures\Version::fromArray($this->execute(ucfirst(__FUNCTION__)));
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
	 * Only available to SuperAdmin.
	 * @param bool $multicall
	 * @return bool
	 */
	function quitGame($multicall=false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Call a vote for a command.
	 * You can additionally supply specific parameters for this vote: a ratio, a time out and who is voting.
	 * Only available to Admin.
	 * @param Structures\Vote $vote
	 * @param float $ratio In range [0,1] or -1 for default ratio
	 * @param int $timeout In milliseconds, 0 for default timeout, 1 for indefinite
	 * @param int $voters 0: active players, 1: any player, 2: everybody including pure spectators
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function callVote($vote, $ratio=-1., $timeout=0, $voters=1, $multicall=false)
	{
		if(!($vote instanceof Structures\Vote && $vote->isValid()))
			throw new InvalidArgumentException('vote = '.print_r($vote, true));
		if(!Structures\VoteRatio::isRatio($ratio))
			throw new InvalidArgumentException('ratio = '.print_r($ratio, true));
		if(!is_int($timeout))
			throw new InvalidArgumentException('timeout = '.print_r($timeout, true));
		if(!is_int($voters) || $voters < 0 || $voters > 2)
			throw new InvalidArgumentException('voters = '.print_r($voters, true));

		$xml = Xmlrpc\Request::encode($vote->cmdName, $vote->cmdParam, false);
		return $this->execute(ucfirst(__FUNCTION__).'Ex', array($xml, $ratio, $timeout, $voters), $multicall);
	}

	/**
	 * Call a vote to kick a player.
	 * You can additionally supply specific parameters for this vote: a ratio, a time out and who is voting.
	 * Only available to Admin.
	 * @param mixed $player A player object or a login
	 * @param float $ratio In range [0,1] or -1 for default ratio
	 * @param int $timeout In milliseconds, 0 for default timeout, 1 for indefinite
	 * @param int $voters 0: active players, 1: any player, 2: everybody including pure spectators
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function callVoteKick($player, $ratio=0.5, $timeout=0, $voters=1, $multicall=false)
	{
		$login = $this->getLogin($player);
		if($login === false)
			throw new InvalidArgumentException('player = '.print_r($player, true));

		$vote = new Structures\Vote(Structures\VoteRatio::COMMAND_KICK, array($login));
		return $this->callVote($vote, $ratio, $timeout, $voters, $multicall);
	}

	/**
	 * Call a vote to ban a player.
	 * You can additionally supply specific parameters for this vote: a ratio, a time out and who is voting.
	 * Only available to Admin.
	 * @param mixed $player A player object or a login
	 * @param float $ratio In range [0,1] or -1 for default ratio
	 * @param int $timeout In milliseconds, 0 for default timeout, 1 for indefinite
	 * @param int $voters 0: active players, 1: any player, 2: everybody including pure spectators
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function callVoteBan($player, $ratio=0.6, $timeout=0, $voters=1, $multicall=false)
	{
		$login = $this->getLogin($player);
		if($login === false)
			throw new InvalidArgumentException('player = '.print_r($player, true));

		$vote = new Structures\Vote(Structures\VoteRatio::COMMAND_BAN, array($login));
		return $this->callVote($vote, $ratio, $timeout, $voters, $multicall);
	}

	/**
	 * Call a vote to restart the current map.
	 * You can additionally supply specific parameters for this vote: a ratio, a time out and who is voting.
	 * Only available to Admin.
	 * @param float $ratio In range [0,1] or -1 for default ratio
	 * @param int $timeout In milliseconds, 0 for default timeout, 1 for indefinite
	 * @param int $voters 0: active players, 1: any player, 2: everybody including pure spectators
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function callVoteRestartMap($ratio=0.5, $timeout=0, $voters=1, $multicall=false)
	{
		$vote = new Structures\Vote(Structures\VoteRatio::COMMAND_RESTART_MAP);
		return $this->callVote($vote, $ratio, $timeout, $voters, $multicall);
	}

	/**
	 * Call a vote to go to the next map.
	 * You can additionally supply specific parameters for this vote: a ratio, a time out and who is voting.
	 * Only available to Admin.
	 * @param float $ratio In range [0,1] or -1 for default ratio
	 * @param int $timeout In milliseconds, 0 for default timeout, 1 for indefinite
	 * @param int $voters 0: active players, 1: any player, 2: everybody including pure spectators
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function callVoteNextMap($ratio=0.5, $timeout=0, $voters=1, $multicall=false)
	{
		$vote = new Structures\Vote(Structures\VoteRatio::COMMAND_NEXT_MAP);
		return $this->callVote($vote, $ratio, $timeout, $voters, $multicall);
	}

	/**
	 * Cancel the current vote.
	 * Only available to Admin.
	 * @param bool $multicall
	 * @return bool
	 */
	function cancelVote($multicall=false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Returns the vote currently in progress.
	 * @return Structures\Vote
	 */
	function getCurrentCallVote()
	{
		return Structures\Vote::fromArray($this->execute(ucfirst(__FUNCTION__)));
	}

	/**
	 * Set a new timeout for waiting for votes.
	 * Only available to Admin.
	 * @param int $timeout In milliseconds, 0 to disable votes
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setCallVoteTimeOut($timeout, $multicall=false)
	{
		if(!is_int($timeout))
			throw new InvalidArgumentException('timeout = '.print_r($timeout, true));

		return $this->execute(ucfirst(__FUNCTION__), array($timeout), $multicall);
	}

	/**
	 * Get the current and next timeout for waiting for votes.
	 * @return int[] {int CurrentValue, int NextValue}
	 */
	function getCallVoteTimeOut()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new default ratio for passing a vote.
	 * Only available to Admin.
	 * @param float $ratio In range [0,1] or -1 to disable votes
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setCallVoteRatio($ratio, $multicall=false)
	{
		if(!Structures\VoteRatio::isRatio($ratio))
			throw new InvalidArgumentException('ratio = '.print_r($ratio, true));

		return $this->execute(ucfirst(__FUNCTION__), array($ratio), $multicall);
	}

	/**
	 * Get the current default ratio for passing a vote.
	 * @param bool $multicall
	 * @return float
	 */
	function getCallVoteRatio()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set the ratios list for passing specific votes, extended version with parameters matching.
	 * Only available to Admin.
	 * @param Structures\VoteRatio[] $ratios
	 * @param bool $replaceAll True to override the whole ratios list or false to modify only specified ratios
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setCallVoteRatios($ratios, $replaceAll=true, $multicall=false)
	{
		if(!is_array($ratios))
			throw new InvalidArgumentException('ratios = '.print_r($ratios, true));
		foreach($ratios as $i => &$ratio)
		{
			if(!($ratio instanceof Structures\VoteRatio && $ratio->isValid()))
				throw new InvalidArgumentException('ratios['.$i.'] = '.print_r($ratios, true));
			$ratio = $ratio->toArray();
		}
		if(!is_bool($replaceAll))
			throw new InvalidArgumentException('replaceAll = '.print_r($replaceAll, true));

		return $this->execute(ucfirst(__FUNCTION__).'Ex', array($replaceAll, $ratios), $multicall);
	}

	/**
	 * @deprecated
	 * @see setCallVoteRatios()
	 */
	function setCallVoteRatiosEx($replaceAll, $ratios, $multicall=false)
	{
		return $this->setCallVoteRatios($ratios, $replaceAll, $multicall);
	}

	/**
	 * Get the current ratios for passing votes, extended version with parameters matching.
	 * @param bool $multicall
	 * @return Structures\VoteRatio[]
	 */
	function getCallVoteRatios()
	{
		return Structures\VoteRatio::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__).'Ex'));
	}

	/**
	 * @deprecated
	 * @see getCallVoteRatios()
	 */
	function getCallVoteRatiosEx()
	{
		return $this->getCallVoteRatios();
	}

	/**
	 * @deprecated
	 * @see chatSend()
	 */
	function chatSendServerMessage($message, $recipient=null, $multicall=false)
	{
		return $this->chatSend($message, $recipient, true, $multicall);
	}

	/**
	 * @deprecated
	 * @see chatSend()
	 */
	function chatSendServerMessageToLanguage($messages, $recipient=null, $multicall=false)
	{
		return $this->chatSend($messages, $recipient, true, $multicall);
	}

	/**
	 * Send a text message, possibly localised to a specific login or to everyone.
	 * Only available to Admin.
	 * @param string|string[][] $message Single string or array of structures {Lang='xx', Text='...'}:
	 * if no matching language is found, the last text in the array is used
	 * @param mixed $recipient Login, player object or array; null for all
	 * @param bool $isServerMessage False to include server login
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function chatSend($message, $recipient=null, $isServerMessage=false, $multicall=false)
	{
		$logins = $this->getLogins($recipient, true);
		if($logins === false)
			throw new InvalidArgumentException('recipient = '.print_r($recipient, true));

		$method = ucfirst(__FUNCTION__);
		if(!is_bool($isServerMessage))
			throw new InvalidArgumentException('isServerMessage = '.print_r($isServerMessage, true));
		if($isServerMessage)
			$method .= 'ServerMessage';

		if(is_string($message))
		{
			if($logins === '')
				return $this->execute($method, array($message), $multicall);
			return $this->execute($method.'ToLogin', array($message, $logins), $multicall);
		}
		if(is_array($message))
			return $this->execute($method.'ToLanguage', array($message, $logins), $multicall);
		// else
		throw new InvalidArgumentException('message = '.print_r($message, true));
	}

	/**
	 * @deprecated
	 * @see chatSend()
	 */
	function chatSendToLanguage($messages, $recipient=null, $multicall=false)
	{
		return $this->chatSend($messages, $recipient, false, $multicall);
	}

	/**
	 * Returns the last chat lines. Maximum of 40 lines.
	 * Only available to Admin.
	 * @return string[]
	 */
	function getChatLines()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * The chat messages are no longer dispatched to the players, they only go to the rpc callback and the controller has to manually forward them.
	 * Only available to Admin.
	 * @param bool $enable
	 * @param bool $excludeServer Allows all messages from the server to be automatically forwarded.
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function chatEnableManualRouting($enable, $excludeServer=false, $multicall=false)
	{
		if(!is_bool($enable))
			throw new InvalidArgumentException('enable = '.print_r($enable, true));
		if(!is_bool($excludeServer))
			throw new InvalidArgumentException('excludeServer = '.print_r($excludeServer, true));

		return $this->execute(ucfirst(__FUNCTION__), array($enable, $excludeServer), $multicall);
	}

	/**
	 * Send a message to the specified recipient (or everybody if empty) on behalf of sender.
	 * Only available if manual routing is enabled.
	 * Only available to Admin.
	 * @param string $message
	 * @param mixed $sender Login or player object
	 * @param mixed $recipient Login, player object or array; empty for all
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function chatForward($message, $sender, $recipient=null, $multicall=false)
	{
		if(!is_string($message))
			throw new InvalidArgumentException('message = '.print_r($message, true));

		$senderLogin = $this->getLogin($sender);
		if($senderLogin === false)
			throw new InvalidArgumentException('sender = '.print_r($sender, true));
		$recipientLogins = $this->getLogins($recipient, true);
		if($recipientLogins === false)
			throw new InvalidArgumentException('recipient = '.print_r($recipient, true));

		return $this->execute(ucfirst(__FUNCTION__).'ToLogin', array($message, $senderLogin, $recipientLogins), $multicall);
	}

	/**
	 * @deprecated
	 * @see chatForward()
	 */
	function chatForwardToLogin($message, $sender, $recipient=null, $multicall=false)
	{
		return $this->chatForward($message, $sender, $recipient, $multicall);
	}

	/**
	 * Display a notice on all clients.
	 * Only available to Admin.
	 * @param mixed $recipient Login, player object or array; empty for all
	 * @param string $message
	 * @param mixed $avatar Login or player object whose avatar will be displayed; empty for none
	 * @param int $variant 0: normal, 1: sad, 2: happy
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function sendNotice($recipient, $message, $avatar=null, $variant=0, $multicall=false)
	{
		$logins = $this->getLogins($recipient, true);
		if($logins === false)
			throw new InvalidArgumentException('recipient = '.print_r($recipient, true));
		if(!is_string($message))
			throw new InvalidArgumentException('message = '.print_r($message, true));
		$avatarLogin = $this->getLogin($avatar, true);
		if($avatarLogin === false)
			throw new InvalidArgumentException('avatar = '.print_r($avatar, true));
		if(!is_int($variant) || $variant < 0 || $variant > 2)
			throw new InvalidArgumentException('variant = '.print_r($variant, true));

		if($logins === '')
			return $this->execute(ucfirst(__FUNCTION__), array($message, $avatar, $variant), $multicall);
		return $this->execute(ucfirst(__FUNCTION__).'ToLogin', array($logins, $message, $avatarLogin, $variant), $multicall);
	}

	/**
	 * Display a manialink page on all clients.
	 * Only available to Admin.
	 * @param mixed $recipient Login, player object or array; empty for all
	 * @param string $manialinks XML string
	 * @param int $timeout Seconds before autohide, 0 for permanent
	 * @param bool $hideOnClick Hide as soon as the user clicks on a page option
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function sendDisplayManialinkPage($recipient, $manialinks, $timeout=0, $hideOnClick=false, $multicall=false)
	{
		$logins = $this->getLogins($recipient, true);
		if($logins === false)
			throw new InvalidArgumentException('recipient = '.print_r($recipient, true));
		if(!is_string($manialinks))
			throw new InvalidArgumentException('manialinks = '.print_r($manialinks, true));
		if(!is_int($timeout))
			throw new InvalidArgumentException('timeout = '.print_r($timeout, true));
		if(!is_bool($hideOnClick))
			throw new InvalidArgumentException('hideOnClick = '.print_r($hideOnClick, true));

		if($logins === '')
			return $this->execute(ucfirst(__FUNCTION__), array($manialinks, $timeout, $hideOnClick), $multicall);
		return $this->execute(ucfirst(__FUNCTION__).'ToLogin', array($logins, $manialinks, $timeout, $hideOnClick), $multicall);
	}

	/**
	 * Hide the displayed manialink page.
	 * Only available to Admin.
	 * @param mixed $recipient Login, player object or array; empty for all
	 * @param bool $multicall
	 * @return bool
	 */
	function sendHideManialinkPage($recipient=null, $multicall=false)
	{
		$logins = $this->getLogins($recipient, true);
		if($logins === false)
			throw new InvalidArgumentException('recipient = '.print_r($recipient, true));

		if($logins === '')
			return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
		return $this->execute(ucfirst(__FUNCTION__).'ToLogin', array($logins), $multicall);
	}

	/**
	 * Returns the latest results from the current manialink page as an array of structs {string Login, int PlayerId, int Result}:
	 * - Result == 0 -> no answer
	 * - Result > 0 -> answer from the player.
	 * @param bool $multicall
	 * @return Structures\PlayerAnswer[]
	 */
	function getManialinkPageAnswers()
	{
		return Structures\PlayerAnswer::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__)));
	}

	/**
	 * Opens a link in the client with the specified login.
	 * Only available to Admin.
	 * @param mixed $recipient Login, player object or array
	 * @param string $link URL to open
	 * @param int $linkType 0: in the external browser, 1: in the internal manialink browser
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function sendOpenLink($recipient, $link, $linkType, $multicall=false)
	{
		$logins = $this->getLogins($recipient);
		if($logins === false)
			throw new InvalidArgumentException('recipient = '.print_r($recipient, true));
		if(!is_string($link))
			throw new InvalidArgumentException('link = '.print_r($link, true));
		if($linkType !== 0 && $linkType !== 1)
			throw new InvalidArgumentException('linkType = '.print_r($linkType, true));

		return $this->execute(ucfirst(__FUNCTION__).'ToLogin', array($logins, $link, $linkType), $multicall);
	}

	/**
	 * Kick the player with the specified login, with an optional message.
	 * Only available to Admin.
	 * @param mixed $player Login or player object
	 * @param string $message
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function kick($player, $message='', $multicall=false)
	{
		$login = $this->getLogin($player);
		if($login === false)
			throw new InvalidArgumentException('player = '.print_r($player, true));
		if(!is_string($message))
			throw new InvalidArgumentException('message = '.print_r($message, true));

		return $this->execute(ucfirst(__FUNCTION__), array($login, $message), $multicall);
	}

	/**
	 * Ban the player with the specified login, with an optional message.
	 * Only available to Admin.
	 * @param mixed $player Login or player object
	 * @param string $message
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function ban($player, $message='', $multicall=false)
	{
		$login = $this->getLogin($player);
		if($login === false)
			throw new InvalidArgumentException('player = '.print_r($player, true));
		if(!is_string($message))
			throw new InvalidArgumentException('message = '.print_r($message, true));

		return $this->execute(ucfirst(__FUNCTION__), array($login, $message), $multicall);
	}

	/**
	 * Ban the player with the specified login, with a message.
	 * Add it to the black list, and optionally save the new list.
	 * Only available to Admin.
	 * @param mixed $player Login or player object
	 * @param string $message
	 * @param bool $save
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function banAndBlackList($player, $message, $save=false, $multicall=false)
	{
		$login = $this->getLogin($player);
		if($login === false)
			throw new InvalidArgumentException('player = '.print_r($player, true));
		if(!is_string($message))
			throw new InvalidArgumentException('message = '.print_r($message, true));
		if(!is_bool($save))
			throw new InvalidArgumentException('save = '.print_r($save, true));

		return $this->execute(ucfirst(__FUNCTION__), array($player, $message, $save), $multicall);
	}

	/**
	 * Unban the player with the specified login.
	 * Only available to Admin.
	 * @param mixed $player Login or player object
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function unBan($player, $multicall=false)
	{
		$login = $this->getLogin($player);
		if($login === false)
			throw new InvalidArgumentException('player = '.print_r($player, true));

		return $this->execute(ucfirst(__FUNCTION__), array($login), $multicall);
	}

	/**
	 * Clean the ban list of the server.
	 * Only available to Admin.
	 * @param bool $multicall
	 * @return bool
	 */
	function cleanBanList($multicall=false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Returns the list of banned players.
	 * @param int $length Maximum number of infos to be returned
	 * @param int $offset Starting index in the list
	 * @return Structures\PlayerBan[]
	 * @throws InvalidArgumentException
	 */
	function getBanList($length, $offset)
	{
		if(!is_int($length))
			throw new InvalidArgumentException('length = '.print_r($length, true));
		if(!is_int($offset))
			throw new InvalidArgumentException('offset = '.print_r($offset, true));

		return Structures\PlayerBan::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__), array($length, $offset)));
	}

	/**
	 * Blacklist the player with the specified login.
	 * Only available to SuperAdmin.
	 * @param mixed $player Login or player object
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function blackList($player, $multicall=false)
	{
		$login = $this->getLogin($player);
		if($login === false)
			throw new InvalidArgumentException('player = '.print_r($player, true));

		return $this->execute(ucfirst(__FUNCTION__), array($login), $multicall);
	}

	/**
	 * UnBlackList the player with the specified login.
	 * Only available to SuperAdmin.
	 * @param mixed $player Login or player object
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function unBlackList($player, $multicall=false)
	{
		$login = $this->getLogin($player);
		if($login === false)
			throw new InvalidArgumentException('player = '.print_r($player, true));

		return $this->execute(ucfirst(__FUNCTION__), array($login), $multicall);
	}

	/**
	 * Clean the blacklist of the server.
	 * Only available to SuperAdmin.
	 * @param bool $multicall
	 * @return bool
	 */
	function cleanBlackList($multicall=false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Returns the list of blacklisted players.
	 * @param int $length Maximum number of infos to be returned
	 * @param int $offset Starting index in the list
	 * @return Structures\Player[]
	 * @throws InvalidArgumentException
	 */
	function getBlackList($length, $offset)
	{
		if(!is_int($length))
			throw new InvalidArgumentException('length = '.print_r($length, true));
		if(!is_int($offset))
			throw new InvalidArgumentException('offset = '.print_r($offset, true));

		return Structures\Player::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__), array($length, $offset)));
	}

	/**
	 * Load the black list file with the specified file name.
	 * Only available to Admin.
	 * @param string $filename
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function loadBlackList($filename, $multicall=false)
	{
		if(!is_string($filename))
			throw new InvalidArgumentException('filename = '.print_r($filename, true));

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Save the black list in the file with specified file name.
	 * Only available to Admin.
	 * @param string $filename
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function saveBlackList($filename, $multicall=false)
	{
		if(!is_string($filename))
			throw new InvalidArgumentException('filename = '.print_r($filename, true));

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Add the player with the specified login on the guest list.
	 * Only available to Admin.
	 * @param mixed $player Login or player object
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function addGuest($player, $multicall=false)
	{
		$login = $this->getLogin($player);
		if($login === false)
			throw new InvalidArgumentException('player = '.print_r($player, true));

		return $this->execute(ucfirst(__FUNCTION__), array($login), $multicall);
	}

	/**
	 * Remove the player with the specified login from the guest list.
	 * Only available to Admin.
	 * @param mixed $player Login or player object
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function removeGuest($player, $multicall=false)
	{
		$login = $this->getLogin($player);
		if($login === false)
			throw new InvalidArgumentException('player = '.print_r($player, true));

		return $this->execute(ucfirst(__FUNCTION__), array($login), $multicall);
	}

	/**
	 * Clean the guest list of the server.
	 * Only available to Admin.
	 * @param bool $multicall
	 * @return bool
	 */
	function cleanGuestList($multicall=false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Returns the list of players on the guest list.
	 * @param int $length Maximum number of infos to be returned
	 * @param int $offset Starting index in the list
	 * @return Structures\Player[]
	 * @return array
	 * @throws InvalidArgumentException
	 */
	function getGuestList($length, $offset)
	{
		if(!is_int($length))
			throw new InvalidArgumentException('length = '.print_r($length, true));
		if(!is_int($offset))
			throw new InvalidArgumentException('offset = '.print_r($offset, true));

		return Structures\Player::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__), array($length, $offset)));
	}

	/**
	 * Load the guest list file with the specified file name.
	 * Only available to Admin.
	 * @param string $filename
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function loadGuestList($filename, $multicall=false)
	{
		if(!is_string($filename))
			throw new InvalidArgumentException('filename = '.print_r($filename, true));

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Save the guest list in the file with specified file name.
	 * Only available to Admin.
	 * @param string $filename
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function saveGuestList($filename, $multicall=false)
	{
		if(!is_string($filename))
			throw new InvalidArgumentException('filename = '.print_r($filename, true));

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Sets whether buddy notifications should be sent in the chat.
	 * Only available to Admin.
	 * @param mixed $player Login or player object; empty for global setting
	 * @param bool $enable
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setBuddyNotification($player, $enable, $multicall=false)
	{
		$login = $this->getLogin($player, true);
		if($login === false)
			throw new InvalidArgumentException('player = '.print_r($player, true));
		if(!is_bool($enable))
			throw new InvalidArgumentException('enable = '.print_r($enable, true));

		return $this->execute(ucfirst(__FUNCTION__), array($login, $enable), $multicall);
	}

	/**
	 * Gets whether buddy notifications are enabled.
	 * @param mixed $player Login or player object; empty for global setting
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function getBuddyNotification($player)
	{
		$login = $this->getLogin($player, true);
		if($login === false)
			throw new InvalidArgumentException('player = '.print_r($player, true));

		return $this->execute(ucfirst(__FUNCTION__), array($login));
	}

	/**
	 * Write the data to the specified file. The filename is relative to the Maps path.
	 * Only available to Admin.
	 * @param string $filename
	 * @param string $data
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function writeFile($filename, $data, $multicall=false)
	{
		if(!is_string($filename))
			throw new InvalidArgumentException('filename = '.print_r($filename, true));
		if(!is_string($data))
			throw new InvalidArgumentException('data = '.print_r($data, true));

		$data = new Xmlrpc\Base64($data);
		return $this->execute(ucfirst(__FUNCTION__), array($filename, $data), $multicall);
	}

	/**
	 * Write the data to the specified file. The filename is relative to the Maps path.
	 * Only available to Admin.
	 * @param string $filename
	 * @param string $localFilename
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function writeFileFromFile($filename, $localFilename, $multicall=false)
	{
		if(!file_exists($localFilename))
			throw new InvalidArgumentException('localFilename = '.print_r($localFilename, true));

		$contents = file_get_contents($localFilename);
		return $this->writeFile($filename, $contents, $multicall);
	}

	/**
	 * Send the data to the specified player. Login can be a single login or a list of comma-separated logins.
	 * Only available to Admin.
	 * @param mixed $recipient Login, player object or array
	 * @param string $data
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function tunnelSendData($recipient, $data, $multicall=false)
	{
		$logins = $this->getLogins($recipient);
		if($logins === false)
			throw new InvalidArgumentException('recipient = '.print_r($recipient, true));
		if(!is_string($data))
			throw new InvalidArgumentException('data = '.print_r($data, true));

		$data = new Xmlrpc\Base64($data);
		return $this->execute(ucfirst(__FUNCTION__).'ToLogin', array($logins, $data), $multicall);
	}

	/**
	 * Send the data to the specified player. Login can be a single login or a list of comma-separated logins.
	 * Only available to Admin.
	 * @param mixed $recipient Login or player object or array
	 * @param string $filename
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function tunnelSendDataFromFile($recipient, $filename, $multicall=false)
	{
		if(!file_exists($filename))
			throw new InvalidArgumentException('filename = '.print_r($filename, true));

		$contents = file_get_contents($filename);
		return $this->tunnelSendData($recipient, $contents, $multicall);
	}

	/**
	 * Just log the parameters and invoke a callback.
	 * Can be used to talk to other xmlrpc clients connected, or to make custom votes.
	 * If used in a callvote, the first parameter will be used as the vote message on the clients.
	 * Only available to Admin.
	 * @param string $message
	 * @param string $callback
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function dedicatedEcho($message, $callback='', $multicall=false)
	{
		if(!is_string($message))
			throw new InvalidArgumentException('message = '.print_r($message, true));
		if(!is_string($callback))
			throw new InvalidArgumentException('callback = '.print_r($callback, true));

		return $this->execute('Echo', array($message, $callback), $multicall);
	}

	/**
	 * Ignore the player with the specified login.
	 * Only available to Admin.
	 * @param mixed $player Login or player object
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function ignore($player, $multicall=false)
	{
		$login = $this->getLogin($player);
		if($login === false)
			throw new InvalidArgumentException('player = '.print_r($player, true));

		return $this->execute(ucfirst(__FUNCTION__), array($login), $multicall);
	}

	/**
	 * Unignore the player with the specified login.
	 * Only available to Admin.
	 * @param mixed $player Login or player object
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function unIgnore($player, $multicall=false)
	{
		$login = $this->getLogin($player);
		if($login === false)
			throw new InvalidArgumentException('player = '.print_r($player, true));

		return $this->execute(ucfirst(__FUNCTION__), array($login), $multicall);
	}

	/**
	 * Clean the ignore list of the server.
	 * Only available to Admin.
	 * @param bool $multicall
	 * @return bool
	 */
	function cleanIgnoreList($multicall=false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Returns the list of ignored players.
	 * @param int $length Maximum number of infos to be returned
	 * @param int $offset Starting index in the list
	 * @return Structures\Player[]
	 * @throws InvalidArgumentException
	 */
	function getIgnoreList($length, $offset)
	{
		if(!is_int($length))
			throw new InvalidArgumentException('length = '.print_r($length, true));
		if(!is_int($offset))
			throw new InvalidArgumentException('offset = '.print_r($offset, true));

		return Structures\Player::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__), array($length, $offset)));
	}

	/**
	 * Pay planets from the server account to a player.
	 * The creation of the transaction itself may cost planets, so you need to have planets on the server account.
	 * Only available to Admin.
	 * @param mixed $payee Login or player object
	 * @param int $amount
	 * @param string $message
	 * @param bool $multicall
	 * @return int BillId
	 * @throws InvalidArgumentException
	 */
	function pay($payee, $amount, $message, $multicall=false)
	{
		$login = $this->getLogin($payee);
		if($login === false)
			throw new InvalidArgumentException('payee = '.print_r($payee, true));
		if(!is_int($amount) || $amount < 1)
			throw new InvalidArgumentException('amount = '.print_r($amount, true));
		if(!is_string($message))
			throw new InvalidArgumentException('message = '.print_r($message, true));

		return $this->execute(ucfirst(__FUNCTION__), array($login, $amount, $message), $multicall);
	}

	/**
	 * Create a bill, send it to a player, and return the BillId.
	 * The creation of the transaction itself may cost planets, so you need to have planets on the server account.
	 * Only available to Admin.
	 * @param mixed $payer Login or player object
	 * @param int $amount
	 * @param string $message
	 * @param mixed $payee Login or player object; empty for server account
	 * @param bool $multicall
	 * @return int BillId
	 * @throws InvalidArgumentException
	 */
	function sendBill($payer, $amount, $message, $payee, $multicall=false)
	{
		$payerLogin = $this->getLogin($payer);
		if($payerLogin === false)
			throw new InvalidArgumentException('payer = '.print_r($payer, true));
		if(!is_int($amount) || $amount < 1)
			throw new InvalidArgumentException('amount = '.print_r($amount, true));
		if(!is_string($message))
			throw new InvalidArgumentException('message = '.print_r($message, true));
		$payeeLogin = $this->getLogin($payee, true);
		if($payeeLogin === false)
			throw new InvalidArgumentException('payee = '.print_r($payee, true));

		return $this->execute(ucfirst(__FUNCTION__), array($payerLogin, $amount, $message, $payeeLogin), $multicall);
	}

	/**
	 * Returns the current state of a bill.
	 * @param int $billId
	 * @return Structures\Bill
	 * @throws InvalidArgumentException
	 */
	function getBillState($billId)
	{
		if(!is_int($billId))
			throw new InvalidArgumentException('billId = '.print_r($billId, true));

		return Structures\Bill::fromArray($this->execute(ucfirst(__FUNCTION__), array($billId)));
	}

	/**
	 * Returns the current number of planets on the server account.
	 * @return int
	 */
	function getServerPlanets()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Get some system infos, including connection rates (in kbps).
	 * @return Structures\SystemInfos
	 */
	function getSystemInfo()
	{
		return Structures\SystemInfos::fromArray($this->execute(ucfirst(__FUNCTION__)));
	}

	/**
	 * Set the download and upload rates (in kbps).
	 * @param int $downloadRate
	 * @param int $uploadRate
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setConnectionRates($downloadRate, $uploadRate, $multicall=false)
	{
		if(!is_int($downloadRate))
			throw new InvalidArgumentException('downloadRate = '.print_r($downloadRate, true));
		if(!is_int($uploadRate))
			throw new InvalidArgumentException('uploadRate = '.print_r($uploadRate, true));

		return $this->execute(ucfirst(__FUNCTION__), array($downloadRate, $uploadRate), $multicall);
	}

	/**
	 * Returns the list of tags and associated values set on this server.
	 * Only available to Admin.
	 * @param bool $multicall
	 * @return Structures\Tag[]
	 */
	function getServerTags()
	{
		return Structures\Tag::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__)));
	}

	/**
	 * Set a tag and its value on the server.
	 * Only available to Admin.
	 * @param string $key
	 * @param string $value
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setServerTag($key, $value, $multicall=false)
	{
		if(!is_string($key))
			throw new InvalidArgumentException('key = '.print_r($key, true));
		if(!is_string($value))
			throw new InvalidArgumentException('value = '.print_r($value, true));

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
	function unsetServerTag($key, $multicall=false)
	{
		if(!is_string($key))
			throw new InvalidArgumentException('key = '.print_r($key, true));

		return $this->execute(ucfirst(__FUNCTION__), array($key), $multicall);
	}

	/**
	 * Reset all tags on the server.
	 * Only available to Admin.
	 * @param bool $multicall
	 * @return bool
	 */
	function resetServerTags($multicall=false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Set a new server name in utf8 format.
	 * Only available to Admin.
	 * @param string $name
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setServerName($name, $multicall=false)
	{
		if(!is_string($name))
			throw new InvalidArgumentException('name = '.print_r($name, true));

		return $this->execute(ucfirst(__FUNCTION__), array($name), $multicall);
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
	 * Only available to Admin.
	 * @param string $comment
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setServerComment($comment, $multicall=false)
	{
		if(!is_string($comment))
			throw new InvalidArgumentException('comment = '.print_r($comment, true));

		return $this->execute(ucfirst(__FUNCTION__), array($comment), $multicall);
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
	 * Set whether the server should be hidden from the public server list.
	 * Only available to Admin.
	 * @param int $visibility 0: visible, 1: always hidden, 2: hidden from nations
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setHideServer($visibility, $multicall=false)
	{
		if(!is_int($visibility) || $visibility < 0 || $visibility > 2)
			throw new InvalidArgumentException('visibility = '.print_r($visibility, true));

		return $this->execute(ucfirst(__FUNCTION__), array($visibility), $multicall);
	}

	/**
	 * Get whether the server wants to be hidden from the public server list.
	 * @return int
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
	 * Only available to Admin.
	 * @param string $password
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setServerPassword($password, $multicall=false)
	{
		if(!is_string($password))
			throw new InvalidArgumentException('password = '.print_r($password, true));

		return $this->execute(ucfirst(__FUNCTION__), array($password), $multicall);
	}

	/**
	 * Get the server password if called as Admin or Super Admin, else returns if a password is needed or not.
	 * @return string|bool
	 */
	function getServerPassword()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new password for the spectator mode.
	 * Only available to Admin.
	 * @param string $password
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setServerPasswordForSpectator($password, $multicall=false)
	{
		if(!is_string($password))
			throw new InvalidArgumentException('password = '.print_r($password, true));

		return $this->execute(ucfirst(__FUNCTION__), array($password), $multicall);
	}

	/**
	 * Get the password for spectator mode if called as Admin or Super Admin, else returns if a password is needed or not.
	 * @return string|bool
	 */
	function getServerPasswordForSpectator()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new maximum number of players.
	 * Only available to Admin.
	 * Requires a map restart to be taken into account.
	 * @param int $maxPlayers
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setMaxPlayers($maxPlayers, $multicall=false)
	{
		if(!is_int($maxPlayers))
			throw new InvalidArgumentException('maxPlayers = '.print_r($maxPlayers, true));

		return $this->execute(ucfirst(__FUNCTION__), array($maxPlayers), $multicall);
	}

	/**
	 * Get the current and next maximum number of players allowed on server.
	 * @return int[] {int CurrentValue, int NextValue}
	 */
	function getMaxPlayers()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new maximum number of spectators.
	 * Only available to Admin.
	 * Requires a map restart to be taken into account.
	 * @param int $maxSpectators
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setMaxSpectators($maxSpectators, $multicall=false)
	{
		if(!is_int($maxSpectators))
			throw new InvalidArgumentException('maxSpectators = '.print_r($maxSpectators, true));

		return $this->execute(ucfirst(__FUNCTION__), array($maxSpectators), $multicall);
	}

	/**
	 * Get the current and next maximum number of Spectators allowed on server.
	 * @return int[] {int CurrentValue, int NextValue}
	 */
	function getMaxSpectators()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Declare if the server is a lobby, the number and maximum number of players currently managed by it, and the average level of the players.
	 * Only available to Admin.
	 * @param bool $isLobby
	 * @param int $currentPlayers
	 * @param int $maxPlayers
	 * @param float $averageLevel
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setLobbyInfo($isLobby, $currentPlayers, $maxPlayers, $averageLevel, $multicall=false)
	{
		if(!is_bool($isLobby))
			throw new InvalidArgumentException('isLobby = '.print_r($isLobby, true));
		if(!is_int($currentPlayers))
			throw new InvalidArgumentException('currentPlayers = '.print_r($currentPlayers, true));
		if(!is_int($maxPlayers))
			throw new InvalidArgumentException('maxPlayers = '.print_r($maxPlayers, true));
		if(!is_float($averageLevel))
			throw new InvalidArgumentException('averageLevel = '.print_r($averageLevel, true));

		return $this->execute(ucfirst(__FUNCTION__), array($isLobby, $currentPlayers, $maxPlayers, $averageLevel), $multicall);
	}

	/**
	 * Get whether the server if a lobby, the number and maximum number of players currently managed by it.
	 * @return Structures\LobbyInfo
	 */
	function getLobbyInfo()
	{
		return Structures\LobbyInfo::fromArray($this->execute(ucfirst(__FUNCTION__)));
	}

	/**
	 * Customize the clients 'leave server' dialog box.
	 * Only available to Admin.
	 * @param string $manialink
	 * @param string $sendToServer Server URL, eg. '#qjoin=login@title'
	 * @param bool $askFavorite
	 * @param int $quitButtonDelay In milliseconds
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function customizeQuitDialog($manialink, $sendToServer, $askFavorite, $quitButtonDelay, $multicall=false)
	{
		if(!is_string($manialink))
			throw new InvalidArgumentException('manialink = '.print_r($manialink, true));
		if(!is_string($sendToServer))
			throw new InvalidArgumentException('sendToServer = '.print_r($sendToServer, true));
		if(!is_bool($askFavorite))
			throw new InvalidArgumentException('askFavorite = '.print_r($askFavorite, true));
		if(!is_int($quitButtonDelay))
			throw new InvalidArgumentException('quitButtonDelay = '.print_r($quitButtonDelay, true));

		return $this->execute(ucfirst(__FUNCTION__), array($manialink, $sendToServer, $askFavorite, $quitButtonDelay), $multicall);
	}

	/**
	 * Set whether, when a player is switching to spectator, the server should still consider him a player and keep his player slot, or not.
	 * Only available to Admin.
	 * @param bool $keep
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function keepPlayerSlots($keep, $multicall=false)
	{
		if(!is_bool($keep))
			throw new InvalidArgumentException('keep = '.print_r($keep, true));

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
	 * Only available to Admin.
	 * @param bool $enable
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function enableP2PUpload($enable, $multicall=false)
	{
		if(!is_bool($enable))
			throw new InvalidArgumentException('enable = '.print_r($enable, true));

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
	 * Enable or disable peer-to-peer download for server.
	 * Only available to Admin.
	 * @param bool $enable
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function enableP2PDownload($enable, $multicall=false)
	{
		if(!is_bool($enable))
			throw new InvalidArgumentException('enable = '.print_r($enable, true));

		return $this->execute(ucfirst(__FUNCTION__), array($enable), $multicall);
	}

	/**
	 * Returns if the peer-to-peer download for server is enabled.
	 * @return bool
	 */
	function isP2PDownload()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Allow clients to download maps from the server.
	 * Only available to Admin.
	 * @param bool $allow
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function allowMapDownload($allow, $multicall=false)
	{
		if(!is_bool($allow))
			throw new InvalidArgumentException('allow = '.print_r($allow, true));

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
	 * Returns the path of the game datas directory.
	 * Only available to Admin.
	 * @return string
	 */
	function gameDataDirectory()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Returns the path of the maps directory.
	 * Only available to Admin.
	 * @return string
	 */
	function getMapsDirectory()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Returns the path of the skins directory.
	 * Only available to Admin.
	 * @return string
	 */
	function getSkinsDirectory()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * @deprecated since version 2013-04-11
	 * Set Team names and colors.
	 * Only available to Admin.
	 * @param string $name1
	 * @param float $color1
	 * @param string $path1
	 * @param string $name2
	 * @param float $color2
	 * @param string $path2
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setTeamInfo($name1, $color1, $path1, $name2, $color2, $path2, $multicall=false)
	{
		if(!is_string($name1))
			throw new InvalidArgumentException('name1 = '.print_r($name1, true));
		if(!is_float($color1))
			throw new InvalidArgumentException('color1 = '.print_r($color1, true));
		if(!is_string($path1))
			throw new InvalidArgumentException('path1 = '.print_r($path1, true));
		if(!is_string($name2))
			throw new InvalidArgumentException('name2 = '.print_r($name2, true));
		if(!is_float($color2))
			throw new InvalidArgumentException('color2 = '.print_r($color2, true));
		if(!is_string($path2))
			throw new InvalidArgumentException('path2 = '.print_r($path2, true));

		return $this->execute(ucfirst(__FUNCTION__), array('unused', 0., 'World', $name1, $color1, $path1, $name2, $color2, $path2), $multicall);
	}

	/**
	 * Return info for a given team.
	 * Only available to Admin.
	 * @param int $team 0: no clan, 1 or 2
	 * @return Structures\Team
	 * @throws InvalidArgumentException
	 */
	function getTeamInfo($team)
	{
		if(!is_int($team) || $team < 0 || $team > 2)
			throw new InvalidArgumentException('team = '.print_r($team, true));

		return Structures\Team::fromArray($this->execute(ucfirst(__FUNCTION__), array($team)));
	}

	/**
	 * Set the clublinks to use for the two teams.
	 * Only available to Admin.
	 * @param string $team1
	 * @param string $team2
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setForcedClubLinks($team1, $team2, $multicall=false)
	{
		if(!is_string($team1))
			throw new InvalidArgumentException('team1 = '.print_r($team1, true));
		if(!is_string($team2))
			throw new InvalidArgumentException('team2 = '.print_r($team2, true));

		return $this->execute(ucfirst(__FUNCTION__), array($team1, $team2), $multicall);
	}

	/**
	 * Get the forced clublinks.
	 * @return string[]
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
	function connectFakePlayer($multicall=false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * (debug tool) Disconnect a fake player.
	 * Only available to Admin.
	 * @param string $login Fake player login or '*' for all
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function disconnectFakePlayer($login, $multicall=false)
	{
		if(!is_string($login))
			throw new InvalidArgumentException('login = '.print_r($login, true));

		return $this->execute(ucfirst(__FUNCTION__), array($login), $multicall);
	}

	/**
	 * Returns the token infos for a player.
	 * @param mixed $player Login or player object
	 * @param bool $multicall
	 * @return array {int TokenCost, bool CanPayToken}
	 * @throws InvalidArgumentException
	 */
	function getDemoTokenInfosForPlayer($player)
	{
		$login = $this->getLogin($player);
		if($login === false)
			throw new InvalidArgumentException('player = '.print_r($player, true));

		return $this->execute(ucfirst(__FUNCTION__), array($login));
	}

	/**
	 * Disable player horns.
	 * Only available to Admin.
	 * @param bool $disable
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function disableHorns($disable, $multicall=false)
	{
		if(!is_bool($disable))
			throw new InvalidArgumentException('disable = '.print_r($disable, true));

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
	function disableServiceAnnounces($disable, $multicall=false)
	{
		if(!is_bool($disable))
			throw new InvalidArgumentException('disable = '.print_r($disable, true));

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
	 * Enable the autosaving of all replays (vizualisable replays with all players, but not validable) on the server.
	 * Only available to SuperAdmin.
	 * @param bool $enable
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function autoSaveReplays($enable, $multicall=false)
	{
		if(!is_bool($enable))
			throw new InvalidArgumentException('enable = '.print_r($enable, true));

		return $this->execute(ucfirst(__FUNCTION__), array($enable), $multicall);
	}

	/**
	 * Enable the autosaving on the server of validation replays, every time a player makes a new time.
	 * Only available to SuperAdmin.
	 * @param bool $enable
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function autoSaveValidationReplays($enable, $multicall=false)
	{
		if(!is_bool($enable))
			throw new InvalidArgumentException('enable = '.print_r($enable, true));

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
	 * Returns if autosaving of validation replays is enabled on the server.
	 * @return bool
	 */
	function isAutoSaveValidationReplaysEnabled()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Saves the current replay (vizualisable replays with all players, but not validable).
	 * Only available to Admin.
	 * @param string $filename Empty for automatic filename
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function saveCurrentReplay($filename='', $multicall=false)
	{
		if(!is_string($filename))
			throw new InvalidArgumentException('filename = '.print_r($filename, true));

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Saves a replay with the ghost of all the players' best race.
	 * Only available to Admin.
	 * @param mixed $player Login or player object; empty for all
	 * @param string $filename Empty for automatic filename
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function saveBestGhostsReplay($player=null, $filename='', $multicall=false)
	{
		$login = $this->getLogin($player, true);
		if($login === false)
			throw new InvalidArgumentException('player = '.print_r($player, true));
		if(!is_string($filename))
			throw new InvalidArgumentException('filename = '.print_r($filename, true));

		return $this->execute(ucfirst(__FUNCTION__), array($login, $filename), $multicall);
	}

	/**
	 * Returns a replay containing the data needed to validate the current best time of the player.
	 * @param mixed $player Login or player object
	 * @return string
	 * @throws InvalidArgumentException
	 */
	function getValidationReplay($player)
	{
		$login = $this->getLogin($player);
		if($login === false)
			throw new InvalidArgumentException('player = '.print_r($player, true));

		return $this->execute(ucfirst(__FUNCTION__), array($login))->scalar;
	}

	/**
	 * Set a new ladder mode.
	 * Only available to Admin.
	 * Requires a map restart to be taken into account.
	 * @param int $mode 0: disabled, 1: forced
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setLadderMode($mode, $multicall=false)
	{
		if($mode !== 0 && $mode !== 1)
			throw new InvalidArgumentException('mode = '.print_r($mode, true));

		return $this->execute(ucfirst(__FUNCTION__), array($mode), $multicall);
	}

	/**
	 * Get the current and next ladder mode on server.
	 * @return int[] {int CurrentValue, int NextValue}
	 */
	function getLadderMode()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Get the ladder points limit for the players allowed on this server.
	 * @return Structures\LadderLimits
	 */
	function getLadderServerLimits()
	{
		return Structures\LadderLimits::fromArray($this->execute(ucfirst(__FUNCTION__)));
	}

	/**
	 * Set the network vehicle quality.
	 * Only available to Admin.
	 * Requires a map restart to be taken into account.
	 * @param int $quality 0: fast, 1: high
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setVehicleNetQuality($quality, $multicall=false)
	{
		if($quality !== 0 && $quality !== 1)
			throw new InvalidArgumentException('quality = '.print_r($quality, true));

		return $this->execute(ucfirst(__FUNCTION__), array($quality), $multicall);
	}

	/**
	 * Get the current and next network vehicle quality on server.
	 * @return int[] {int CurrentValue, int NextValue}
	 */
	function getVehicleNetQuality()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set new server options using the struct passed as parameters.
	 * Mandatory fields:
	 *  Name, Comment, Password, PasswordForSpectator, CallVoteRatio
	 * Optional fields:
	 *  NextMaxPlayers, NextMaxSpectators, IsP2PUpload, IsP2PDownload, NextLadderMode,
	 *	NextVehicleNetQuality, NextCallVoteTimeOut, AllowMapDownload, AutoSaveReplays,
	 *  RefereePassword, RefereeMode, AutoSaveValidationReplays, HideServer, UseChangingValidationSeed,
	 *  ClientInputsMaxLatency, DisableHorns, DisableServiceAnnounces, KeepPlayerSlots.
	 * Only available to Admin.
	 * A change of NextMaxPlayers, NextMaxSpectators, NextLadderMode, NextVehicleNetQuality,
	 *  NextCallVoteTimeOut or UseChangingValidationSeed requires a map restart to be taken into account.
	 * @param struct $options
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setServerOptions($options, $multicall=false)
	{
		if(!is_array($options)
				|| !(isset($options['Name']) && is_string($options['Name']))
				|| !(isset($options['Comment']) && is_string($options['Comment']))
				|| !(isset($options['Password']) && is_string($options['Password']))
				|| !(isset($options['PasswordForSpectator']) && is_string($options['PasswordForSpectator']))
				|| !(isset($options['CallVoteRatio']) && Structures\VoteRatio::isRatio($options['CallVoteRatio'])))
			throw new InvalidArgumentException('options = '.print_r($options, true));

		return $this->execute(ucfirst(__FUNCTION__), array($options), $multicall);
	}

	/**
	 * Returns a struct containing the server options
	 * @return Structures\ServerOptions
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
	function setForcedTeams($enable, $multicall=false)
	{
		if(!is_bool($enable))
			throw new InvalidArgumentException('enable = '.print_r($enable, true));

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
	 * Defines the packmask of the server.
	 * Only maps matching the packmask will be allowed on the server, so that player connecting to it know what to expect.
	 * Only available when the server is stopped.
	 * Only available to Admin.
	 * @param string $packMask
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setServerPackMask($packMask, $multicall=false)
	{
		if(!is_string($packMask))
			throw new InvalidArgumentException('packMask = '.print_r($enable, true));

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
	 * Set the mods to apply on the clients.
	 * Only available to Admin.
	 * Requires a map restart to be taken into account.
	 * @param bool $override If true, even the maps with a mod will be overridden by the server setting
	 * @param Structures\Mod|Structures\Mod[] $mods Array of structures [{string Env, string Url}, ...]
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setForcedMods($override, $mods, $multicall=false)
	{
		if(!is_bool($override))
			throw new InvalidArgumentException('override = '.print_r($override, true));
		if(is_array($mods))
		{
			foreach($mods as $i => &$mod)
			{
				if(!($mod instanceof Structures\Mod))
					throw new InvalidArgumentException('mods['.$i.'] = '.print_r($mod, true));
				$mod = $mod->toArray();
			}
		}
		elseif($mods instanceof Structures\Mod)
			$mods = array($mods->toArray());
		else
			throw new InvalidArgumentException('mods = '.print_r($mods, true));

		return $this->execute(ucfirst(__FUNCTION__), array($override, $mods), $multicall);
	}

	/**
	 * Get the mods settings.
	 * @return array {bool Override, Structures\Mod[] Mods}
	 */
	function getForcedMods()
	{
		$result = $this->execute(ucfirst(__FUNCTION__));
		$result['Mods'] = Structures\Mod::fromArrayOfArray($result['Mods']);
		return $result;
	}

	/**
	 * Set the music to play on the clients.
	 * Only available to Admin.
	 * Requires a map restart to be taken into account.
	 * @param bool $override If true, even the maps with a custom music will be overridden by the server setting
	 * @param string $music Url or filename for the music
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setForcedMusic($override, $music, $multicall=false)
	{
		if(!is_bool($override))
			throw new InvalidArgumentException('override = '.print_r($override, true));
		if(!is_string($music))
			throw new InvalidArgumentException('music = '.print_r($music, true));

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
	 * Defines a list of remappings for player skins.
	 * Will only affect players connecting after the value is set.
	 * Only available to Admin.
	 * @param Structures\ForcedSkin|Structures\ForcedSkin[] $skins List of structs {Orig, Name, Checksum, Url}:
	 * - Orig is the name of the skin to remap, or '*' for any other
	 * - Name, Checksum, Url define the skin to use (you may set value '' for any of those, all 3 null means same as Orig).
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setForcedSkins($skins, $multicall=false)
	{
		if(is_array($skins))
		{
			foreach($skins as $i => &$skin)
			{
				if(!($skin instanceof Structures\ForcedSkin))
					throw new InvalidArgumentException('skins['.$i.'] = '.print_r($skin, true));
				$skin = $skin->toArray();
			}

		}
		elseif($skins instanceof Structures\ForcedSkin)
			$skins = array($skins->toArray());
		else
			throw new InvalidArgumentException('skins = '.print_r($skins, true));

		return $this->execute(ucfirst(__FUNCTION__), array($skins), $multicall);
	}

	/**
	 * Get the current forced skins.
	 * @return Structures\ForcedSkin[]
	 */
	function getForcedSkins()
	{
		return Structures\ForcedSkin::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__)));
	}

	/**
	 * Returns the last error message for an internet connection.
	 * Only available to Admin.
	 * @return string
	 */
	function getLastConnectionErrorMessage()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new password for the referee mode.
	 * Only available to Admin.
	 * @param string $password
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setRefereePassword($password, $multicall=false)
	{
		if(!is_string($password))
			throw new InvalidArgumentException('password = '.print_r($password, true));

		return $this->execute(ucfirst(__FUNCTION__), array($password), $multicall);
	}

	/**
	 * Get the password for referee mode if called as Admin or Super Admin, else returns if a password is needed or not.
	 * @return string|bool
	 */
	function getRefereePassword()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set the referee validation mode.
	 * Only available to Admin.
	 * @param int $mode 0: validate the top3 players, 1: validate all players
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setRefereeMode($mode, $multicall=false)
	{
		if($mode !== 0 && $mode !== 1)
			throw new InvalidArgumentException('mode = '.print_r($mode, true));

		return $this->execute(ucfirst(__FUNCTION__), array($mode), $multicall);
	}

	/**
	 * Get the referee validation mode.
	 * @return int
	 */
	function getRefereeMode()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set whether the game should use a variable validation seed or not.
	 * Only available to Admin.
	 * Requires a map restart to be taken into account.
	 * @param bool $enable
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setUseChangingValidationSeed($enable, $multicall=false)
	{
		if(!is_bool($enable))
			throw new InvalidArgumentException('enable = '.print_r($enable, true));

		return $this->execute(ucfirst(__FUNCTION__), array($enable), $multicall);
	}

	/**
	 * Get the current and next value of UseChangingValidationSeed.
	 * @return bool[] {bool CurrentValue, bool NextValue}
	 */
	function getUseChangingValidationSeed()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set the maximum time the server must wait for inputs from the clients before dropping data, or '0' for auto-adaptation.
	 * Only used by ShootMania.
	 * Only available to Admin.
	 * @param int $latency
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setClientInputsMaxLatency($latency, $multicall=false)
	{
		if(!is_int($latency))
			throw new InvalidArgumentException('latency = '.print_r($latency, true));

		return $this->execute(ucfirst(__FUNCTION__), array($latency), $multicall);
	}

	/**
	 * Get the current ClientInputsMaxLatency.
	 * Only used by ShootMania.
	 * @return int
	 */
	function getClientInputsMaxLatency()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Sets whether the server is in warm-up phase or not.
	 * Only available to Admin.
	 * @param bool $enable
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setWarmUp($enable, $multicall=false)
	{
		if(!is_bool($enable))
			throw new InvalidArgumentException('enable = '.print_r($enable, true));

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
	 * Get the current mode script.
	 * @return string
	 */
	function getModeScriptText()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set the mode script and restart.
	 * Only available to Admin.
	 * @param string $script
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setModeScriptText($script, $multicall=false)
	{
		if(!is_string($script))
			throw new InvalidArgumentException('script = '.print_r($script, true));

		return $this->execute(ucfirst(__FUNCTION__), array($script), $multicall);
	}

	/**
	 * Returns the description of the current mode script.
	 * @return Structures\ScriptInfo
	 */
	function getModeScriptInfo()
	{
		return Structures\ScriptInfo::fromArray($this->execute(ucfirst(__FUNCTION__)));
	}

	/**
	 * Returns the current settings of the mode script.
	 * @return array {mixed <setting name>, ...}
	 */
	function getModeScriptSettings()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Change the settings of the mode script.
	 * Only available to Admin.
	 * @param mixed[] $settings {mixed <setting name>, ...}
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setModeScriptSettings($settings, $multicall=false)
	{
		if(!is_array($settings))
			throw new InvalidArgumentException('settings = '.print_r($settings, true));

		return $this->execute(ucfirst(__FUNCTION__), array($settings), $multicall);
	}

	/**
	 * Send commands to the mode script.
	 * Only available to Admin.
	 * @param mixed[] $commands
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function sendModeScriptCommands($commands, $multicall=false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array($commands), $multicall);
	}

	/**
	 * Change the settings and send commands to the mode script.
	 * Only available to Admin.
	 * @param mixed[] $settings {mixed <setting name>, ...}
	 * @param mixed[] $commands
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setModeScriptSettingsAndCommands($settings, $commands, $multicall=false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array($settings, $commands), $multicall);
	}

	/**
	 * Returns the current xml-rpc variables of the mode script.
	 * @return array {mixed <setting name>, ...}
	 */
	function getModeScriptVariables()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set the xml-rpc variables of the mode script.
	 * Only available to Admin.
	 * @param mixed[] $variables
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setModeScriptVariables($variables, $multicall=false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array($variables), $multicall);
	}

	/**
	 * Send an event to the mode script.
	 * Only available to Admin.
	 * @param string $event
	 * @param string $params
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function triggerModeScriptEvent($event, $params='', $multicall=false)
	{
		if(!is_string($event))
			throw new InvalidArgumentException('event = '.print_r($event, true));
		if(!is_string($params))
			throw new InvalidArgumentException('params = '.print_r($params, true));

		return $this->execute(ucfirst(__FUNCTION__), array($event, $params), $multicall);
	}

	/**
	 * Send an event to the mode script.
	 * Only available to Admin.
	 * @param string $event
	 * @param mixed[] $params
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function triggerModeScriptEventArray($event, $params, $multicall=false)
	{
		if(!is_string($event))
			throw new InvalidArgumentException('events = '.print_r($event, true));
		if(!is_array($params))
			throw new InvalidArgumentException('params = '.print_r($params, true));

		return $this->execute(ucfirst(__FUNCTION__), array($event, $params), $multicall);
	}

	/**
	 * Get the script cloud variables of given object.
	 * Only available to Admin.
	 * @param string $arg1
	 * @param string $arg2
	 * @param bool $multicall
	 * @return array
	 * @throws InvalidArgumentException
	 */
	function getScriptCloudVariables($arg1, $arg2, $multicall=false)
	{
		if(!is_string($arg1))
			throw new InvalidArgumentException('$arg1 = '.print_r($arg1, true));
		if(!is_string($arg2))
			throw new InvalidArgumentException('$arg2 = '.print_r($arg2, true));

		return $this->execute(ucfirst(__FUNCTION__), array($arg1, $arg2), $multicall);
	}

	/**
	 * Set the script cloud variables of given object. Only available to Admin.
	 * @param string $arg1
	 * @param string $arg2
	 * @param struct $arg3
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setScriptCloudVariables($arg1, $arg2, $arg3, $multicall=false)
	{
		if(!is_string($arg1))
			throw new InvalidArgumentException('$arg1 = '.print_r($arg1, true));
		if(!is_string($arg2))
			throw new InvalidArgumentException('$arg2 = '.print_r($arg2, true));
		if(!is_struct($arg3))
			throw new InvalidArgumentException('$arg3 = '.print_r($arg3, true));

		return $this->execute(ucfirst(__FUNCTION__), array($arg1, $arg2, $arg3), $multicall);
	}

	/**
	 * Restarts the map.
	 * Only available to Admin.
	 * @param bool $dontClearCupScores Only available in legacy cup mode
	 * @param bool $multicall
	 * @return bool
	 */
	function restartMap($dontClearCupScores=false, $multicall=false)
	{
		if(!is_bool($dontClearCupScores))
			throw new InvalidArgumentException('dontClearCupScores = '.print_r($dontClearCupScores, true));

		return $this->execute(ucfirst(__FUNCTION__), array($dontClearCupScores), $multicall);
	}

	/**
	 * Switch to next map.
	 * Only available to Admin.
	 * @param bool $dontClearCupScores Only available in legacy cup mode
	 * @param bool $multicall
	 * @return bool
	 */
	function nextMap($dontClearCupScores=false, $multicall=false)
	{
		if(!is_bool($dontClearCupScores))
			throw new InvalidArgumentException('dontClearCupScores = '.print_r($dontClearCupScores, true));

		return $this->execute(ucfirst(__FUNCTION__), array($dontClearCupScores), $multicall);
	}

	/**
	 * Attempt to balance teams.
	 * Only available to Admin.
	 * @param bool $multicall
	 * @return bool
	 */
	function autoTeamBalance($multicall=false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Stop the server.
	 * Only available to SuperAdmin.
	 * @param bool $multicall
	 * @return bool
	 */
	function stopServer($multicall=false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * In legacy Rounds or Laps mode, force the end of round without waiting for all players to giveup/finish.
	 * Only available to Admin.
	 * @param bool $multicall
	 * @return bool
	 */
	function forceEndRound($multicall=false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Set new game settings using the struct passed as parameters.
	 * Only available to Admin.
	 * Requires a map restart to be taken into account.
	 * @param Structures\GameInfos $gameInfos
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setGameInfos($gameInfos, $multicall=false)
	{
		if(!($gameInfos instanceof Structures\GameInfos))
			throw new InvalidArgumentException('gameInfos = '.print_r($gameInfos, true));

		return $this->execute(ucfirst(__FUNCTION__), array($gameInfos->toArray()), $multicall);
	}

	/**
	 * Returns a struct containing the current game settings.
	 * @return Structures\GameInfos
	 */
	function getCurrentGameInfo()
	{
		return Structures\GameInfos::fromArray($this->execute(ucfirst(__FUNCTION__)));
	}

	/**
	 * Returns a struct containing the game settings for the next map.
	 * @return Structures\GameInfos
	 */
	function getNextGameInfo()
	{
		return Structures\GameInfos::fromArray($this->execute(ucfirst(__FUNCTION__)));
	}

	/**
	 * Returns a struct containing two other structures, the first containing the current game settings and the second the game settings for next map.
	 * @return Structures\GameInfos[] {Structures\GameInfos CurrentGameInfos, Structures\GameInfos NextGameInfos}
	 */
	function getGameInfos()
	{
		return Structures\GameInfos::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__)));
	}

	/**
	 * Set a new game mode.
	 * Only available to Admin.
	 * Requires a map restart to be taken into account.
	 * @param int $gameMode 0: Script, 1: Rounds, 2: TimeAttack, 3: Team, 4: Laps, 5: Cup, 6: Stunt
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setGameMode($gameMode, $multicall=false)
	{
		if(!is_int($gameMode) || $gameMode < 0 || $gameMode > 6)
			throw new InvalidArgumentException('gameMode = '.print_r($gameMode, true));

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
	 * Set a new chat time value (actually the duration of the podium).
	 * Only available to Admin.
	 * @param int $chatTime In milliseconds, 0: no podium displayed
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setChatTime($chatTime, $multicall=false)
	{
		if(!is_int($chatTime))
			throw new InvalidArgumentException('chatTime = '.print_r($chatTime, true));

		return $this->execute(ucfirst(__FUNCTION__), array($chatTime), $multicall);
	}

	/**
	 * Get the current and next chat time.
	 * @return int[] {int CurrentValue, int NextValue}
	 */
	function getChatTime()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new finish timeout value for legacy laps and rounds based modes.
	 * Only available to Admin.
	 * Requires a map restart to be taken into account.
	 * @param int $timeout In milliseconds, 0: default, 1: adaptative to the map duration
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setFinishTimeout($timeout, $multicall=false)
	{
		if(!is_int($timeout))
			throw new InvalidArgumentException('timeout = '.print_r($timeout, true));

		return $this->execute(ucfirst(__FUNCTION__), array($timeout), $multicall);
	}

	/**
	 * Get the current and next FinishTimeout.
	 * @return int[] {int CurrentValue, int NextValue}
	 */
	function getFinishTimeout()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set whether to enable the automatic warm-up phase in all modes.
	 * Only available to Admin.
	 * Requires a map restart to be taken into account.
	 * @param int $duration 0: disable, number of rounds in rounds based modes, number of times the gold medal time otherwise
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setAllWarmUpDuration($duration, $multicall=false)
	{
		if(!is_int($duration))
			throw new InvalidArgumentException('duration = '.print_r($duration, true));

		return $this->execute(ucfirst(__FUNCTION__), array($duration), $multicall);
	}

	/**
	 * Get whether the automatic warm-up phase is enabled in all modes.
	 * @return int[] {int CurrentValue, int NextValue}
	 */
	function getAllWarmUpDuration()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set whether to disallow players to respawn.
	 * Only available to Admin.
	 * Requires a map restart to be taken into account.
	 * @param bool $disable
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setDisableRespawn($disable, $multicall=false)
	{
		if(!is_bool($disable))
			throw new InvalidArgumentException('disable = '.print_r($disable, true));

		return $this->execute(ucfirst(__FUNCTION__), array($disable), $multicall);
	}

	/**
	 * Get whether players are disallowed to respawn.
	 * @return bool[] {bool CurrentValue, bool NextValue}
	 */
	function getDisableRespawn()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set whether to override the players preferences and always display all opponents.
	 * Only available to Admin.
	 * Requires a map restart to be taken into account.
	 * @param int $opponents 0: no override, 1: show all, else: minimum number of opponents
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setForceShowAllOpponents($opponents, $multicall=false)
	{
		if(!is_int($opponents))
			throw new InvalidArgumentException('opponents = '.print_r($opponents, true));

		return $this->execute(ucfirst(__FUNCTION__), array($opponents), $multicall);
	}

	/**
	 * Get whether players are forced to show all opponents.
	 * @return int[] {int CurrentValue, int NextValue}
	 */
	function getForceShowAllOpponents()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new mode script name for script mode.
	 * Only available to Admin.
	 * Requires a map restart to be taken into account.
	 * @param string $script
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setScriptName($script, $multicall=false)
	{
		if(!is_string($script))
			throw new InvalidArgumentException('script = '.print_r($script, true));

		return $this->execute(ucfirst(__FUNCTION__), array($script), $multicall);
	}

	/**
	 * Get the current and next mode script name for script mode.
	 * @return string[] {string CurrentValue, string NextValue}
	 */
	function getScriptName()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new time limit for legacy time attack mode.
	 * Only available to Admin.
	 * Requires a map restart to be taken into account.
	 * @param int $limit In milliseconds
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setTimeAttackLimit($limit, $multicall=false)
	{
		if(!is_int($limit))
			throw new InvalidArgumentException('limit = '.print_r($limit, true));

		return $this->execute(ucfirst(__FUNCTION__), array($limit), $multicall);
	}

	/**
	 * Get the current and next time limit for legacy time attack mode.
	 * @return int[] {int CurrentValue, int NextValue}
	 */
	function getTimeAttackLimit()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new synchronized start period for legacy time attack mode.
	 * Only available to Admin.
	 * Requires a map restart to be taken into account.
	 * @param int $synch
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setTimeAttackSynchStartPeriod($synch, $multicall=false)
	{
		if(!is_int($synch))
			throw new InvalidArgumentException('synch = '.print_r($synch, true));

		return $this->execute(ucfirst(__FUNCTION__), array($synch), $multicall);
	}

	/**
	 * Get the current and synchronized start period for legacy time attack mode.
	 * @return int[] {int CurrentValue, int NextValue}
	 */
	function getTimeAttackSynchStartPeriod()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new time limit for legacy laps mode.
	 * Only available to Admin.
	 * Requires a map restart to be taken into account.
	 * @param int $limit
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setLapsTimeLimit($limit, $multicall=false)
	{
		if(!is_int($limit))
			throw new InvalidArgumentException('limit = '.print_r($limit, true));

		return $this->execute(ucfirst(__FUNCTION__), array($limit), $multicall);
	}

	/**
	 * Get the current and next time limit for legacy laps mode.
	 * @return int[] {int CurrentValue, int NextValue}
	 */
	function getLapsTimeLimit()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new number of laps for legacy laps mode.
	 * Only available to Admin.
	 * Requires a map restart to be taken into account.
	 * @param int $laps
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setNbLaps($laps, $multicall=false)
	{
		if(!is_int($laps))
			throw new InvalidArgumentException('laps = '.print_r($laps, true));

		return $this->execute(ucfirst(__FUNCTION__), array($laps), $multicall);
	}

	/**
	 * Get the current and next number of laps for legacy laps mode.
	 * @return int[] {int CurrentValue, int NextValue}
	 */
	function getNbLaps()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new number of laps for legacy rounds mode.
	 * Only available to Admin.
	 * Requires a map restart to be taken into account.
	 * @param int $laps 0: map default
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setRoundForcedLaps($laps, $multicall=false)
	{
		if(!is_int($laps))
			throw new InvalidArgumentException('laps = '.print_r($laps, true));

		return $this->execute(ucfirst(__FUNCTION__), array($laps), $multicall);
	}

	/**
	 * Get the current and next number of laps for rounds mode.
	 * @return int[] {int CurrentValue, int NextValue}
	 */
	function getRoundForcedLaps()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new points limit for legacy rounds mode (value set depends on UseNewRulesRound).
	 * Only available to Admin.
	 * Requires a map restart to be taken into account.
	 * @param int $limit
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setRoundPointsLimit($limit, $multicall=false)
	{
		if(!is_int($limit))
			throw new InvalidArgumentException('limit = '.print_r($limit, true));

		return $this->execute(ucfirst(__FUNCTION__), array($limit), $multicall);
	}

	/**
	 * Get the current and next points limit for rounds mode (values returned depend on UseNewRulesRound).
	 * @return int[] {int CurrentValue, int NextValue}
	 */
	function getRoundPointsLimit()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set the points used for the scores in legacy rounds mode.
	 * Only available to Admin.
	 * @param int[] $points Array of decreasing integers for the players from the first to last
	 * @param bool $relax True to relax the constraint checking on the scores
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setRoundCustomPoints($points, $relax=false, $multicall=false)
	{
		if(!is_array($points))
			throw new InvalidArgumentException('points = '.print_r($points, true));
		if(!is_bool($relax))
			throw new InvalidArgumentException('relax = '.print_r($relax, true));

		return $this->execute(ucfirst(__FUNCTION__), array($points, $relax), $multicall);
	}

	/**
	 * Gets the points used for the scores in legacy rounds mode.
	 * @return int[]
	 */
	function getRoundCustomPoints()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set if new rules are used for legacy rounds mode.
	 * Only available to Admin.
	 * Requires a map restart to be taken into account.
	 * @param bool $newRules
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setUseNewRulesRound($newRules, $multicall=false)
	{
		if(!is_bool($newRules))
			throw new InvalidArgumentException('newRules = '.print_r($newRules, true));

		return $this->execute(ucfirst(__FUNCTION__), array($newRules), $multicall);
	}

	/**
	 * Get if the new rules are used for legacy rounds mode (Current and next values).
	 * @return bool[] {bool CurrentValue, bool NextValue}
	 */
	function getUseNewRulesRound()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new points limit for team mode (value set depends on UseNewRulesTeam).
	 * Only available to Admin.
	 * Requires a map restart to be taken into account.
	 * @param int $limit
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setTeamPointsLimit($limit, $multicall=false)
	{
		if(!is_int($limit))
			throw new InvalidArgumentException('limit = '.print_r($limit, true));

		return $this->execute(ucfirst(__FUNCTION__), array($limit), $multicall);
	}

	/**
	 * Get the current and next points limit for team mode (values returned depend on UseNewRulesTeam).
	 * @return int[] {int CurrentValue, int NextValue}
	 */
	function getTeamPointsLimit()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set a new number of maximum points per round for team mode.
	 * Only available to Admin.
	 * Requires a map restart to be taken into account.
	 * @param int $max
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setMaxPointsTeam($max, $multicall=false)
	{
		if(!is_int($max))
			throw new InvalidArgumentException('max = '.print_r($max, true));

		return $this->execute(ucfirst(__FUNCTION__), array($max), $multicall);
	}

	/**
	 * Get the current and next number of maximum points per round for team mode.
	 * @return int[] {int CurrentValue, int NextValue}
	 */
	function getMaxPointsTeam()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set if new rules are used for team mode.
	 * Only available to Admin.
	 * Requires a map restart to be taken into account.
	 * @param bool $newRules
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setUseNewRulesTeam($newRules, $multicall=false)
	{
		if(!is_bool($newRules))
			throw new InvalidArgumentException('newRules = '.print_r($newRules, true));

		return $this->execute(ucfirst(__FUNCTION__), array($newRules), $multicall);
	}

	/**
	 * Get if the new rules are used for team mode (Current and next values).
	 * @return bool[] {bool CurrentValue, bool NextValue}
	 */
	function getUseNewRulesTeam()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set the points needed for victory in Cup mode.
	 * Only available to Admin.
	 * Requires a map restart to be taken into account.
	 * @param int $limit
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setCupPointsLimit($limit, $multicall=false)
	{
		if(!is_int($limit))
			throw new InvalidArgumentException('limit = '.print_r($limit, true));

		return $this->execute(ucfirst(__FUNCTION__), array($limit), $multicall);
	}

	/**
	 * Get the points needed for victory in Cup mode.
	 * @return int[] {int CurrentValue, int NextValue}
	 */
	function getCupPointsLimit()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Sets the number of rounds before going to next map in Cup mode.
	 * Only available to Admin.
	 * Requires a map restart to be taken into account.
	 * @param int $rounds
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setCupRoundsPerMap($rounds, $multicall=false)
	{
		if(!is_int($rounds))
			throw new InvalidArgumentException('rounds = '.print_r($rounds, true));

		return $this->execute(ucfirst(__FUNCTION__), array($rounds), $multicall);
	}

	/**
	 * Get the number of rounds before going to next map in Cup mode.
	 * @return int[] {int CurrentValue, int NextValue}
	 */
	function getCupRoundsPerMap()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set whether to enable the automatic warm-up phase in Cup mode.
	 * Only available to Admin.
	 * Requires a map restart to be taken into account.
	 * @param int $duration Number of rounds, 0: no warm-up
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setCupWarmUpDuration($duration, $multicall=false)
	{
		if(!is_int($duration))
			throw new InvalidArgumentException('duration = '.print_r($duration, true));

		return $this->execute(ucfirst(__FUNCTION__), array($duration), $multicall);
	}

	/**
	 * Get whether the automatic warm-up phase is enabled in Cup mode.
	 * @return int[] {int CurrentValue, int NextValue}
	 */
	function getCupWarmUpDuration()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Set the number of winners to determine before the match is considered over.
	 * Only available to Admin.
	 * Requires a map restart to be taken into account.
	 * @param int $winners
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setCupNbWinners($winners, $multicall=false)
	{
		if(!is_int($winners))
			throw new InvalidArgumentException('winners = '.print_r($winners, true));

		return $this->execute(ucfirst(__FUNCTION__), array($winners), $multicall);
	}

	/**
	 * Get the number of winners to determine before the match is considered over.
	 * @return int[] {int CurrentValue, int NextValue}
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
	 * @param int $index
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setNextMapIndex($index, $multicall=false)
	{
		if(!is_int($index))
			throw new InvalidArgumentException('index = '.print_r($index, true));

		return $this->execute(ucfirst(__FUNCTION__), array($index), $multicall);
	}

	/**
	 * Sets the map in the selection that will be played next (unless the current one is restarted...)
	 * @param string $ident
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function setNextMapIdent($ident, $multicall=false)
	{
		if(!is_string($ident))
			throw new InvalidArgumentException('ident = '.print_r($ident, true));

		return $this->execute(ucfirst(__FUNCTION__), array($ident), $multicall);
	}

	/**
	 * Immediately jumps to the map designated by the index in the selection.
	 * @param int $index
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function jumpToMapIndex($index, $multicall=false)
	{
		if(!is_int($index))
			throw new InvalidArgumentException('index = '.print_r($index, true));

		return $this->execute(ucfirst(__FUNCTION__), array($index), $multicall);
	}

	/**
	 * Immediately jumps to the map designated by its identifier (it must be in the selection).
	 * @param string $ident
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function jumpToMapIdent($ident, $multicall=false)
	{
		if(!is_string($ident))
			throw new InvalidArgumentException('ident = '.print_r($ident, true));

		return $this->execute(ucfirst(__FUNCTION__), array($ident), $multicall);
	}

	/**
	 * Returns a struct containing the infos for the current map.
	 * @return Structures\Map
	 */
	function getCurrentMapInfo()
	{
		return Structures\Map::fromArray($this->execute(ucfirst(__FUNCTION__)));
	}

	/**
	 * Returns a struct containing the infos for the next map.
	 * @return Structures\Map
	 */
	function getNextMapInfo()
	{
		return Structures\Map::fromArray($this->execute(ucfirst(__FUNCTION__)));
	}

	/**
	 * Returns a struct containing the infos for the map with the specified filename.
	 * @param string $filename
	 * @return Structures\Map
	 * @throws InvalidArgumentException
	 */
	function getMapInfo($filename)
	{
		if(!is_string($filename))
			throw new InvalidArgumentException('filename = '.print_r($filename, true));

		return Structures\Map::fromArray($this->execute(ucfirst(__FUNCTION__), array($filename)));
	}

	/**
	 * Returns a boolean if the map with the specified filename matches the current server settings.
	 * @param string $filename
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function checkMapForCurrentServerParams($filename, $multicall=false)
	{
		if(!is_string($filename))
			throw new InvalidArgumentException('filename = '.print_r($filename, true));

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Returns a list of maps among the current selection of the server.
	 * @param int $length Maximum number of infos to be returned
	 * @param int $offset Starting index in the list
	 * @return Structures\Map[]
	 * @throws InvalidArgumentException
	 */
	function getMapList($length, $offset)
	{
		if(!is_int($length))
			throw new InvalidArgumentException('length = '.print_r($length, true));
		if(!is_int($offset))
			throw new InvalidArgumentException('offset = '.print_r($offset, true));

		return Structures\Map::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__), array($length, $offset)));
	}

	/**
	 * Add the map with the specified filename at the end of the current selection.
	 * Only available to Admin.
	 * @param string $filename
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function addMap($filename, $multicall=false)
	{
		if(!is_string($filename))
			throw new InvalidArgumentException('filename = '.print_r($filename, true));

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Add the list of maps with the specified filenames at the end of the current selection.
	 * Only available to Admin.
	 * @param string[] $filenames
	 * @param bool $multicall
	 * @return int
	 * @throws InvalidArgumentException
	 */
	function addMapList($filenames, $multicall=false)
	{
		if(!is_array($filenames))
			throw new InvalidArgumentException('filenames = '.print_r($filenames, true));

		return $this->execute(ucfirst(__FUNCTION__), array($filenames), $multicall);
	}

	/**
	 * Remove the map with the specified filename from the current selection.
	 * Only available to Admin.
	 * @param string $filename
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function removeMap($filename, $multicall=false)
	{
		if(!is_string($filename))
			throw new InvalidArgumentException('filename = '.print_r($filename, true));

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Remove the list of maps with the specified filenames from the current selection.
	 * Only available to Admin.
	 * @param string[] $filenames
	 * @param bool $multicall
	 * @return int
	 * @throws InvalidArgumentException
	 */
	function removeMapList($filenames, $multicall=false)
	{
		if(!is_array($filenames))
			throw new InvalidArgumentException('filenames = '.print_r($filenames, true));

		return $this->execute(ucfirst(__FUNCTION__), array($filenames), $multicall);
	}

	/**
	 * Insert the map with the specified filename after the current map.
	 * Only available to Admin.
	 * @param string $filename
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function insertMap($filename, $multicall=false)
	{
		if(!is_string($filename))
			throw new InvalidArgumentException('filename = '.print_r($filename, true));

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Insert the list of maps with the specified filenames after the current map.
	 * Only available to Admin.
	 * @param string[] $filenames
	 * @param bool $multicall
	 * @return int
	 * @throws InvalidArgumentException
	 */
	function insertMapList($filenames, $multicall=false)
	{
		if(!is_array($filenames))
			throw new InvalidArgumentException('filenames = '.print_r($filenames, true));

		return $this->execute(ucfirst(__FUNCTION__), array($filenames), $multicall);
	}

	/**
	 * Set as next map the one with the specified filename, if it is present in the selection.
	 * Only available to Admin.
	 * @param string $filename
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function chooseNextMap($filename, $multicall=false)
	{
		if(!is_string($filename))
			throw new InvalidArgumentException('filename = '.print_r($filename, true));

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Set as next maps the list of maps with the specified filenames, if they are present in the selection.
	 * Only available to Admin.
	 * @param array $filenames
	 * @param bool $multicall
	 * @return int
	 * @throws InvalidArgumentException
	 */
	function chooseNextMapList($filenames, $multicall=false)
	{
		if(!is_array($filenames))
			throw new InvalidArgumentException('filenames = '.print_r($filenames, true));

		return $this->execute(ucfirst(__FUNCTION__), array($filenames), $multicall);
	}

	/**
	 * Set a list of maps defined in the playlist with the specified filename as the current selection of the server, and load the gameinfos from the same file.
	 * Only available to Admin.
	 * @param string $filename
	 * @param bool $multicall
	 * @return int
	 * @throws InvalidArgumentException
	 */
	function loadMatchSettings($filename, $multicall=false)
	{
		if(!is_string($filename))
			throw new InvalidArgumentException('filename = '.print_r($filename, true));

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Add a list of maps defined in the playlist with the specified filename at the end of the current selection.
	 * Only available to Admin.
	 * @param string $filename
	 * @param bool $multicall
	 * @return int
	 * @throws InvalidArgumentException
	 */
	function appendPlaylistFromMatchSettings($filename, $multicall=false)
	{
		if(!is_string($filename))
			throw new InvalidArgumentException('filename = '.print_r($filename, true));

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Save the current selection of map in the playlist with the specified filename, as well as the current gameinfos.
	 * Only available to Admin.
	 * @param string $filename
	 * @param bool $multicall
	 * @return int
	 * @throws InvalidArgumentException
	 */
	function saveMatchSettings($filename, $multicall=false)
	{
		if(!is_string($filename))
			throw new InvalidArgumentException('filename = '.print_r($filename, true));

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Insert a list of maps defined in the playlist with the specified filename after the current map.
	 * Only available to Admin.
	 * @param string $filename
	 * @param bool $multicall
	 * @return int
	 * @throws InvalidArgumentException
	 */
	function insertPlaylistFromMatchSettings($filename, $multicall=false)
	{
		if(!is_string($filename))
			throw new InvalidArgumentException('filename = '.print_r($filename, true));

		return $this->execute(ucfirst(__FUNCTION__), array($filename), $multicall);
	}

	/**
	 * Returns the list of players on the server.
	 * @param int $length Maximum number of infos to be returned
	 * @param int $offset Starting index in the list
	 * @param int $compatibility 0: united, 1: forever, 2: forever including servers
	 * @return Structures\PlayerInfo[]
	 * @throws InvalidArgumentException
	 */
	function getPlayerList($length, $offset, $compatibility=1)
	{
		if(!is_int($length))
			throw new InvalidArgumentException('length = '.print_r($length, true));
		if(!is_int($offset))
			throw new InvalidArgumentException('offset = '.print_r($offset, true));
		if(!is_int($compatibility) || $compatibility < 0 || $compatibility > 2)
			throw new InvalidArgumentException('compatibility = '.print_r($compatibility, true));

		return Structures\PlayerInfo::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__), array($length, $offset, $compatibility)));
	}

	/**
	 * Returns a struct containing the infos on the player with the specified login.
	 * @param mixed $player Login or player object
	 * @param int $compatibility 0: united, 1: forever
	 * @return Structures\PlayerInfo
	 * @throws InvalidArgumentException
	 */
	function getPlayerInfo($player, $compatibility=1)
	{
		$login = $this->getLogin($player);
		if($login === false)
			throw new InvalidArgumentException('player = '.print_r($player, true));
		if($compatibility !== 0 && $compatibility !== 1)
			throw new InvalidArgumentException('compatibility = '.print_r($compatibility, true));

		return Structures\PlayerInfo::fromArray($this->execute(ucfirst(__FUNCTION__), array($login, $compatibility)));
	}

	/**
	 * Returns a struct containing the infos on the player with the specified login.
	 * @param mixed $player Login or player object
	 * @return Structures\PlayerDetailedInfo
	 * @throws InvalidArgumentException
	 */
	function getDetailedPlayerInfo($player)
	{
		$login = $this->getLogin($player);
		if($login === false)
			throw new InvalidArgumentException('player = '.print_r($player, true));

		return Structures\PlayerDetailedInfo::fromArray($this->execute(ucfirst(__FUNCTION__), array($login)));
	}

	/**
	 * Returns a struct containing the player infos of the game server
	 * (ie: in case of a basic server, itself; in case of a relay server, the main server)
	 * @param int $compatibility 0: united, 1: forever
	 * @return Structures\PlayerInfo
	 * @throws InvalidArgumentException
	 */
	function getMainServerPlayerInfo($compatibility=1)
	{
		if(!is_int($compatibility))
			throw new InvalidArgumentException('compatibility = '.print_r($compatibility, true));

		return Structures\PlayerInfo::fromArray($this->execute(ucfirst(__FUNCTION__), array($compatibility)));
	}

	/**
	 * Returns the current rankings for the race in progress.
	 * (In trackmania legacy team modes, the scores for the two teams are returned.
	 * In other modes, it's the individual players' scores)
	 * The ranking returned is a list of structures.
	 * Each structure contains the following fields : Login, NickName, PlayerId and Rank.
	 * In addition, for legacy trackmania modes it also contains BestTime, Score, NbrLapsFinished, LadderScore,
	 * and an array BestCheckpoints that contains the checkpoint times for the best race.
	 * @param int $length Maximum number of infos to be returned
	 * @param int $offset Starting index in the list
	 * @return Structures\PlayerRanking[]
	 * @throws InvalidArgumentException
	 */
	function getCurrentRanking($length, $offset)
	{
		if(!is_int($length))
			throw new InvalidArgumentException('length = '.print_r($length, true));
		if(!is_int($offset))
			throw new InvalidArgumentException('offset = '.print_r($offset, true));

		return Structures\PlayerRanking::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__), array($length, $offset)));
	}

	/**
	 * Returns the current ranking for the race in progressof the player with the specified login (or list of comma-separated logins).
	 * The ranking returned is a list of structures.
	 * Each structure contains the following fields : Login, NickName, PlayerId and Rank.
	 * In addition, for legacy trackmania modes it also contains BestTime, Score, NbrLapsFinished, LadderScore,
	 * and an array BestCheckpoints that contains the checkpoint times for the best race.
	 * @param mixed $players Login, player object or array
	 * @param bool $multicall
	 * @return Structures\PlayerRanking[]
	 * @throws InvalidArgumentException
	 */
	function getCurrentRankingForLogin($players, $multicall=false)
	{
		$logins = $this->getLogins($players);
		if($logins === false)
			throw new InvalidArgumentException('players = '.print_r($players, true));

		return Structures\PlayerRanking::fromArrayOfArray($this->execute(ucfirst(__FUNCTION__), array($logins), $multicall));
	}

	/**
	 * Returns the current winning team for the race in progress. (-1: if not in team mode, or draw match)
	 * @return int
	 */
	function getCurrentWinnerTeam()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Force the scores of the current game.
	 * Only available in rounds and team mode.
	 * Only available to Admin/SuperAdmin.
	 * @param int[][] $scores Array of structs {int PlayerId, int Score}
	 * @param bool $silent True to update silently (only available for SuperAdmin)
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function forceScores($scores, $silent, $multicall=false)
	{
		if(!is_array($scores))
			throw new InvalidArgumentException('scores = '.print_r($scores, true));
		foreach($scores as $i => $score)
		{
			if(!is_array($score))
				throw new InvalidArgumentException('score['.$i.'] = '.print_r($score, true));
			if(!isset($score['PlayerId']) || !is_int($score['PlayerId']))
				throw new InvalidArgumentException('score['.$i.']["PlayerId"] = '.print_r($score, true));
			if(!isset($score['Score']) || !is_int($score['Score']))
				throw new InvalidArgumentException('score['.$i.']["Score"] = '.print_r($score, true));
		}
		if(!is_bool($silent))
			throw new InvalidArgumentException('silent = '.print_r($silent, true));

		return $this->execute(ucfirst(__FUNCTION__), array($scores, $silent), $multicall);
	}

	/**
	 * Force the team of the player.
	 * Only available in team mode.
	 * Only available to Admin.
	 * @param mixed $player Login or player object
	 * @param int $team 0 or 1
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function forcePlayerTeam($player, $team, $multicall=false)
	{
		$login = $this->getLogin($player);
		if($login === false)
			throw new InvalidArgumentException('player = '.print_r($player, true));
		if($team !== 0 && $team !== 1)
			throw new InvalidArgumentException('team = '.print_r($team, true));

		return $this->execute(ucfirst(__FUNCTION__), array($login, $team), $multicall);
	}

	/**
	 * Force the spectating status of the player.
	 * Only available to Admin.
	 * @param mixed $player Login or player object
	 * @param int $mode 0: user selectable, 1: spectator, 2: player, 3: spectator but keep selectable
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function forceSpectator($player, $mode, $multicall=false)
	{
		$login = $this->getLogin($player);
		if($login === false)
			throw new InvalidArgumentException('player = '.print_r($player, true));
		if(!is_int($mode) || $mode < 0 || $mode > 3)
			throw new InvalidArgumentException('mode = '.print_r($mode, true));

		return $this->execute(ucfirst(__FUNCTION__), array($login, $mode), $multicall);
	}

	/**
	 * Force spectators to look at a specific player.
	 * Only available to Admin.
	 * @param mixed $spectator Login or player object; empty for all
	 * @param mixed $target Login or player object; empty for automatic
	 * @param int $camera -1: leave unchanged, 0: replay, 1: follow, 2: free
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function forceSpectatorTarget($spectator, $target, $camera, $multicall=false)
	{
		$spectatorLogin = $this->getLogin($spectator, true);
		if($spectatorLogin === false)
			throw new InvalidArgumentException('player = '.print_r($spectator, true));
		$targetLogin = $this->getLogin($target, true);
		if($targetLogin === false)
			throw new InvalidArgumentException('target = '.print_r($target, true));
		if(!is_int($camera) || $camera < -1 || $camera > 2)
			throw new InvalidArgumentException('camera = '.print_r($camera, true));

		return $this->execute(ucfirst(__FUNCTION__), array($spectatorLogin, $targetLogin, $camera), $multicall);
	}

	/**
	 * Pass the login of the spectator.
	 * A spectator that once was a player keeps his player slot, so that he can go back to race mode.
	 * Calling this function frees this slot for another player to connect.
	 * Only available to Admin.
	 * @param mixed $player Login or player object
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function spectatorReleasePlayerSlot($player, $multicall=false)
	{
		$login = $this->getLogin($player);
		if($login === false)
			throw new InvalidArgumentException('player = '.print_r($player, true));

		return $this->execute(ucfirst(__FUNCTION__), array($login), $multicall);
	}

	/**
	 * Enable control of the game flow: the game will wait for the caller to validate state transitions.
	 * Only available to Admin.
	 * @param bool $enable
	 * @param bool $multicall
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	function manualFlowControlEnable($enable, $multicall=false)
	{
		if(!is_bool($enable))
			throw new InvalidArgumentException('enable = '.print_r($enable, true));

		return $this->execute(ucfirst(__FUNCTION__), array($enable), $multicall);
	}

	/**
	 * Allows the game to proceed.
	 * Only available to Admin.
	 * @param bool $multicall
	 * @return bool
	 */
	function manualFlowControlProceed($multicall=false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Returns whether the manual control of the game flow is enabled.
	 * Only available to Admin.
	 * @return int 0: no, 1: yes by the xml-rpc client making the call, 2: yes by some other xml-rpc client
	 */
	function manualFlowControlIsEnabled()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Returns the transition that is currently blocked, or '' if none.
	 * (That's exactly the value last received by the callback.)
	 * Only available to Admin.
	 * @return string
	 */
	function manualFlowControlGetCurTransition()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Returns the current match ending condition.
	 * @return string 'Playing', 'ChangeMap' or 'Finished'
	 */
	function checkEndMatchCondition()
	{
		return $this->execute(ucfirst(__FUNCTION__));
	}

	/**
	 * Returns a struct containing the networks stats of the server.
	 * Only available to SuperAdmin.
	 * @return Structures\NetworkStats
	 */
	function getNetworkStats()
	{
		return Structures\NetworkStats::fromArray($this->execute(ucfirst(__FUNCTION__)));
	}

	/**
	 * Start a server on lan, using the current configuration.
	 * Only available to SuperAdmin.
	 * @param bool $multicall
	 * @return bool
	 */
	function startServerLan($multicall=false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Start a server on internet, using the current configuration.
	 * Only available to SuperAdmin.
	 * @param bool $multicall
	 * @return bool
	 */
	function startServerInternet($multicall=false)
	{
		return $this->execute(ucfirst(__FUNCTION__), array(), $multicall);
	}

	/**
	 * Returns the login of the given player
	 * @param mixed $player
	 * @return string|bool
	 */
	private function getLogin($player, $allowEmpty=false)
	{
		if(is_object($player))
		{
			if(property_exists($player, 'login'))
				$player = $player->login;
			else
				return false;
		}
		if(empty($player))
			return $allowEmpty ? '' : false;
		if(is_string($player))
			return $player;
		return false;
	}

	/**
	 * Returns logins of given players
	 * @param mixed $players
	 * @return string|bool
	 */
	private function getLogins($players, $allowEmpty=false)
	{
		if(is_array($players))
		{
			$logins = array();
			foreach($players as $player)
			{
				$login = $this->getLogin($player);
				if($login === false)
					return false;
				else
					$logins[] = $login;
			}

			return implode(',', $logins);
		}
		return $this->getLogin($players, $allowEmpty);
	}
}

/**
 * Exception Dedicated to Invalid Argument Error on Request Call
 */
class InvalidArgumentException extends \Exception {}
