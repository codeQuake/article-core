<?php
namespace wcf\data;

use wcf\data\IMessage;
use wcf\system\bbcode\AttachmentBBCode;
use wcf\system\bbcode\MessageParser;
use wcf\system\breadcrumb\Breadcrumb;
use wcf\system\breadcrumb\IBreadcrumbProvider;
use wcf\system\language\LanguageFactory;
use wcf\system\request\IRouteController; 
use wcf\util\StringUtil;

/**
 * Abstract class for all article based database objects.
 * 
 * @author	Jens Krumsieck
 * @copyright	2013-2015 codeQuake
 * @license	GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package de.codequake.core.article
 */

 abstract class AbstractArticleDatabaseObject extends DatabaseObject implements IRouteController, IMessage {
	
	/** 
	 * main controller for viewing objects of this class
	 * @var	string 
	 */ 
	protected static $objectViewController = ''; 

	/**
	 * {@inheritdoc}
	 */
	public function __toString() {
		return $this->getFormattedMessage();
	}

	/**
	 * {@inheritdoc}
	 */
	public function getBreadcrumb() {
		return new Breadcrumb($this->subject, $this->getLink());
	}

	/**
	 * check whether user can add new object
	 * @return bool
	 */
	public function canAdd() {
		return false;
	}

	/**
	 * check whether user can moderate current object
	 * @return bool
	 */
	public function canModerate() {
		return false;
	}

	/**
	 * @check whether user can read current object
	 * @return bool
	 */
	public function canRead() {
		return false;
	}


	/**
	 * {@inheritdoc}
	 */
	public function getExcerpt($maxLength = 255) {
		return StringUtil::truncateHTML($this->getSimplifiedFormattedMessage(), $maxLength);
	}

	/** 
	 *	{@inheritdoc}
	 */
	public function getFormattedMessage() {
		AttachmentBBCode::setObjectID($this->{$this->$databaseTableIndexName});
		MessageParser::getInstance()->setOutputType('text/html');
		return MessageParser::getInstance()->parse($this->getMessage(), $this->enableSmilies, $this->enableHtml, $this->enableBBCodes);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getLanguage() {
		if ($this->languageID) { 
			 return LanguageFactory::getInstance()->getLanguage($this->languageID); 
		 } 
		return; 
	}

	/**
	 * {@inheritdoc}
	 */
	public function getLanguageIcon() {
		return '<img src="'.$this->getLanguage()->getIconPath().'" alt="" title="'.$this->getLanguage().'" class="jsTooltip iconFlag" />';
	}

	/** 
	 * {@inheritdoc} 
	 * 
	 * @param bool $appendSession 
	 */ 
	public function getLink($appendSession = true) 
	{
		$classParts = explode('\\', get_called_class());
		return LinkHandler::getInstance()->getLink(static::$objectViewController, array( 
			'application' => $classParts[0], 
			'object' => $this, 
			'appendSession' => $appendSession, 
			'forceFrontend' => true, 
		)); 
	}

	/**
	 * {@inheritdoc}
	 */
	public function getMessage() {
		return $this->message;
	}

	/**
	 *	{@inheritdoc}
	 */
	public function getSimplifiedFormattedMessage() {
		MessageParser::getInstance()->setOutputType('text/simplified-html');
		return MessageParser::getInstance()->parse($this->getMessage(), $this->enableSmilies, $this->enableHtml, $this->enableBBCodes);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getTime() {
		return $this->time;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getUserID() {
		return $this->userID;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getUsername() {
		return $this->username;
	}

}
