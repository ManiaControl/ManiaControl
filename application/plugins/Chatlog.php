<?php
use ManiaControl\FileUtil;
use ManiaControl\ManiaControl;
use ManiaControl\Callbacks\CallbackListener;
use ManiaControl\Callbacks\CallbackManager;
use ManiaControl\Plugins\Plugin;

/**
 * ManiaControl Chatlog Plugin
 *
 * @author steeffeen
 */
class ChatlogPlugin implements CallbackListener, Plugin {
	/**
	 * Constants
	 */
	const ID = 1;
	const VERSION = 0.1;
	const SETTING_FOLDERNAME = 'Log-Folder Name';
	const SETTING_FILENAME = 'Log-File Name';
	const SETTING_USEPID = 'Use Process-Id for File Name';
	const SETTING_LOGSERVERMESSAGES = 'Log Server Messages';
	
	/**
	 * Private properties
	 */
	private $fileName = null;
	private $logServerMessages = true;

	/**
	 * Constuct chatlog plugin
	 *
	 * @param ManiaControl $maniaControl        	
	 */
	public function __construct(ManiaControl $maniaControl) {
		$this->maniaControl = $maniaControl;
		
		// Init settings
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_FOLDERNAME, 'logs');
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_FILENAME, 'ChatLog.log');
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_USEPID, false);
		$this->maniaControl->settingManager->initSetting($this, self::SETTING_LOGSERVERMESSAGES, true);
		
		// Get settings
		$folderName = $this->maniaControl->settingManager->getSetting($this, self::SETTING_FOLDERNAME);
		$folderName = FileUtil::getClearedFileName($folderName);
		$folderDir = ManiaControlDir . '/' . $folderName;
		if (!is_dir($folderDir)) {
			$success = mkdir($folderDir);
			if (!$success) {
				trigger_error("Couldn't create chat log folder '{$folderName}'.");
			}
		}
		$fileName = $this->maniaControl->settingManager->getSetting($this, self::SETTING_FILENAME);
		$fileName = FileUtil::getClearedFileName($fileName);
		$usePId = $this->maniaControl->settingManager->getSetting($this, self::SETTING_USEPID);
		if ($usePId) {
			$dotIndex = strripos($fileName, '.');
			$pIdPart = '_' . getmypid();
			if ($dotIndex !== false && $dotIndex >= 0) {
				$fileName = substr($fileName, 0, $dotIndex) . $pIdPart . substr($fileName, $dotIndex);
			}
			else {
				$fileName .= $pIdPart;
			}
		}
		$this->fileName = $folderDir . '/' . $fileName;
		$this->logServerMessages = $this->maniaControl->settingManager->getSetting($this, self::SETTING_LOGSERVERMESSAGES);
		
		// Register for callbacks
		$this->maniaControl->callbackManager->registerCallbackListener(CallbackManager::CB_MP_PLAYERCHAT, $this, 
				'handlePlayerChatCallback');
	}

	/**
	 *
	 * @see \ManiaControl\Plugins\Plugin::getId()
	 */
	public static function getId() {
		return self::ID;
	}

	/**
	 *
	 * @see \ManiaControl\Plugins\Plugin::getName()
	 */
	public static function getName() {
		return 'Chatlog Plugin';
	}

	/**
	 *
	 * @see \ManiaControl\Plugins\Plugin::getVersion()
	 */
	public static function getVersion() {
		return self::VERSION;
	}

	/**
	 *
	 * @see \ManiaControl\Plugins\Plugin::getAuthor()
	 */
	public static function getAuthor() {
		return 'steeffeen';
	}

	/**
	 *
	 * @see \ManiaControl\Plugins\Plugin::getDescription()
	 */
	public static function getDescription() {
		return 'Plugin logging the chat messages of the server.';
	}

	/**
	 * Handle PlayerChat callback
	 *
	 * @param array $chatCallback        	
	 */
	public function handlePlayerChatCallback(array $chatCallback) {
		$data = $chatCallback[1];
		if ($data[0] <= 0 && !$this->logServerMessages) {
			// Skip server message
			return;
		}
		$this->logText($data[2], $data[1]);
	}

	/**
	 * Log the given message
	 *
	 * @param string $message        	
	 * @param string $login        	
	 */
	private function logText($text, $login = null) {
		if (!$login) {
			$login = '';
		}
		$message = date(ManiaControl::DATE) . " >> {$login}: {$text}" . PHP_EOL;
		file_put_contents($this->fileName, $message, FILE_APPEND);
	}
}

?>
