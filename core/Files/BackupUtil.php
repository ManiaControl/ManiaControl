<?php

namespace ManiaControl\Files;

use ManiaControl\Logger;
use ManiaControl\ManiaControl;

/**
 * Backup Utility Class
 *
 * @author    ManiaControl Team <mail@maniacontrol.com>
 * @copyright 2014-2020 ManiaControl Team
 * @license   http://www.gnu.org/licenses/ GNU General Public License, Version 3
 */
abstract class BackupUtil {

	/**
	 * Perform a Full Backup of ManiaControl
	 *
	 * @return bool
	 */
	public static function performFullBackup() {
		$backupFolder = self::getBackupFolder();
		if (!$backupFolder) {
			return false;
		}

		$time = date('y-m-d_H-i-s');
		if (defined('PHP_UNIT_TEST')) {
			$time = date('y-m-d_H-i');
		}

		$backupFileName = $backupFolder . 'backup_' . ManiaControl::VERSION . '_' . $time . '.zip';
		$backupZip      = new \ZipArchive();
		if ($backupZip->open($backupFileName, \ZipArchive::CREATE) !== true) {
			Logger::logError("Couldn't create backup zip!");
			return false;
		}
		$excludes      = array();
		$baseFileNames = array('configs', 'core', 'plugins', 'ManiaControl.php');
		$pathInfo      = pathInfo(MANIACONTROL_PATH);
		$parentPath    = $pathInfo['dirname'] . DIRECTORY_SEPARATOR;
		$dirName       = $pathInfo['basename'];
		$backupZip->addEmptyDir($dirName);
		self::zipDirectory($backupZip, MANIACONTROL_PATH, strlen($parentPath), $excludes, $baseFileNames);
		return $backupZip->close();
	}

	/**
	 * Get the Backup Folder Path and create it if necessary
	 *
	 * @return string|bool
	 */
	private static function getBackupFolder() {
		$backupFolder = MANIACONTROL_PATH . 'backup' . DIRECTORY_SEPARATOR;
		if (!is_dir($backupFolder) && !mkdir($backupFolder)) {
			Logger::logError("Couldn't create backup folder!");
			return false;
		}
		if (!is_writeable($backupFolder)) {
			Logger::logError("ManiaControl doesn't have the necessary write rights for the backup folder!");
			return false;
		}
		return $backupFolder;
	}

	/**
	 * Add a Directory to the ZipArchive
	 *
	 * @param \ZipArchive $zipArchive
	 * @param string      $folderName
	 * @param int         $prefixLength
	 * @param array       $excludes
	 * @param array       $baseFileNames
	 * @return bool
	 */
	private static function zipDirectory(\ZipArchive &$zipArchive, $folderName, $prefixLength, array $excludes = array(), array $baseFileNames = array()) {
		$folderHandle = opendir($folderName);
		if (!is_resource($folderHandle)) {
			Logger::logError("Couldn't open folder '{$folderName}' for backup!");
			return false;
		}
		$useBaseFileNames = !empty($baseFileNames);
		while (false !== ($file = readdir($folderHandle))) {
			if (FileUtil::isHiddenFile($file)) {
				// Skip such .files
				continue;
			}
			if (in_array($file, $excludes)) {
				// Excluded
				continue;
			}
			if ($useBaseFileNames && !in_array($file, $baseFileNames)) {
				// Not one of the base files
				continue;
			}
			$filePath  = $folderName . DIRECTORY_SEPARATOR . $file;
			$localPath = substr($filePath, $prefixLength);
			if (is_file($filePath)) {
				$zipArchive->addFile($filePath, $localPath);
				continue;
			}
			if (is_dir($filePath)) {
				$zipArchive->addEmptyDir($localPath);
				self::zipDirectory($zipArchive, $filePath, $prefixLength, $excludes);
				continue;
			}
		}
		closedir($folderHandle);
		return true;
	}

	/**
	 * Perform a Backup of the Plugins
	 *
	 * @return bool
	 */
	public static function performPluginsBackup() {
		$backupFolder = self::getBackupFolder();
		if (!$backupFolder) {
			return false;
		}
		$backupFileName = $backupFolder . 'backup_plugins_' . ManiaControl::VERSION . date('y-m-d_H-i') . '_' . time() . '.zip';
		$backupZip      = new \ZipArchive();
		if ($backupZip->open($backupFileName, \ZipArchive::CREATE) !== true) {
			Logger::logError("Couldn't create backup zip!");
			return false;
		}
		$directory  = MANIACONTROL_PATH . 'plugins';
		$pathInfo   = pathInfo($directory);
		$parentPath = $pathInfo['dirname'] . DIRECTORY_SEPARATOR;
		$dirName    = $pathInfo['basename'];
		$backupZip->addEmptyDir($dirName);
		self::zipDirectory($backupZip, $directory, strlen($parentPath));
		return $backupZip->close();
	}
}
