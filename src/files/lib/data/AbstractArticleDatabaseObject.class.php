<?php
namespace wcf\data;

use wcf\data\attachment\GroupedAttachmentList;
use wcf\data\IMessage;
use wcf\system\bbcode\AttachmentBBCode;
use wcf\system\bbcode\MessageParser;
use wcf\system\breadcrumb\Breadcrumb;
use wcf\system\breadcrumb\IBreadcrumbProvider;
use wcf\system\category\CategoryHandler;
use wcf\system\language\LanguageFactory;
use wcf\system\request\IRouteController;
use wcf\system\request\LinkHandler;
use wcf\system\tagging\TagEngine;
use wcf\system\WCF;
use wcf\util\StringUtil;

/**
 * Abstract class for all article based database objects.
 *
 * @author Jens Krumsieck
 * @copyright 2013-2015 codeQuake
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package de.codequake.core.article
 */

abstract class AbstractArticleDatabaseObject extends DatabaseObject implements IRouteController, IMessage, IBreadcrumbProvider
{
    /**
     * php class for categories
     * @var string
     */
    protected static $categoryBasicClass = '';

    /**
     * categoryIDs article is connected to
     * @var array<int>
     */
    protected $categoryIDs = array();

    /**
     * @var string
     */
    protected static $objectType = '';

    /** 
     * main controller for viewing objects of this class
     * @var    string 
     */ 
    protected static $objectViewController = '';

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return $this->getFormattedMessage();
    }

    /**
     * check whether user can add new object
     * @return bool
     */
    public function canAdd()
    {
        return false;
    }

    /**
     * check whether user can moderate current object
     * @return bool
     */
    public function canModerate()
    {
        return false;
    }

    /**
     * @check whether user can read current object
     * @return bool
     */
    public function canRead()
    {
        return false;
    }

    /**
     * @return \wcf\data\attachment\GroupedAttachmentList
     */
    public function getAttachments()
    {
        if (MODULE_ATTACHMENT && $this->attachments) {
            $attachmentList = new GroupedAttachmentList(self::$objectType);
            $attachmentList->getConditionBuilder()->add('attachment.objectID IN (?)', array($this->{static::getDatabaseTableIndexName()}));
            $attachmentList->readObjects();
            //add permissions!
            $attachmentList->setPermissions(array(
                'canDownload' => '',
                'canViewPreview' => ''
            ));
            AttachmentBBCode::setAttachmentList($attachmentList);

            return $attachmentList;
        }

        return;
    }

    /**
     * {@inheritdoc}
     */
    public function getBreadcrumb()
    {
        return new Breadcrumb($this->subject, $this->getLink());
    }

    /**
     * @return array<self::$categoryBasicClass>
     */
    public function getCategories()
    {
        $classParts = explode('\\', get_called_class());
        $articleType = explode('.', self::$objectType);
        if ($this->categories === null) {
            $this->categories = array();

             if (0 !== count($this->categoryIDs)) {
                foreach ($this->categoryIDs as $categoryID) {
                    $this->categories[$categoryID] = new self::$categoryBasicClass(CategoryHandler::getInstance()->getCategory($categoryID));
                    }
            } else {
                $sql = '
                    SELECT categoryID 
                    FROM '.$classParts[0].WCF_N.'_'.end($articleType).'_to_category
                    WHERE '.static::getDatabaseTableIndexName().' = ?';
                    $statement = WCF::getDB()->prepareStatement($sql);
                    $statement->execute(array($this->{static::getDatabaseTableIndexName()}));

                    while ($row = $statement->fetchArray()) {
                        $this->categories[$row['categoryID']] = new self::$categoryBasicClass(CategoryHandler::getInstance()->getCategory($row['categoryID']));
                    }
                }
            }

            return $this->categories;
        }


    /**
     * @return int[]
     */
    public function getCategoryIDs()
    {
        return $this->categoryIDs;
    }

    /**
     * {@inheritdoc}
     */
    public function getExcerpt($maxLength = 255)
    {
        return StringUtil::truncateHTML($this->getSimplifiedFormattedMessage(), $maxLength);
    }

    /** 
     *    {@inheritdoc}
     */
    public function getFormattedMessage()
    {
        AttachmentBBCode::setObjectID($this->{static::getDatabaseTableIndexName()});
        MessageParser::getInstance()->setOutputType('text/html');
        return MessageParser::getInstance()->parse($this->getMessage(), $this->enableSmilies, $this->enableHtml, $this->enableBBCodes);
    }

    /**
     * {@inheritdoc}
     */
    public function getLanguage()
    {
        if ($this->languageID) {
             return LanguageFactory::getInstance()->getLanguage($this->languageID);
         }
        return;
    }

    /**
     * {@inheritdoc}
     */
    public function getLanguageIcon()
    {
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
        return LinkHandler::getInstance()->getLink(self::$objectViewController, array(
            'application' => $classParts[0],
            'object' => $this,
            'appendSession' => $appendSession,
            'forceFrontend' => true,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     *    {@inheritdoc}
     */
    public function getSimplifiedFormattedMessage()
    {
        MessageParser::getInstance()->setOutputType('text/simplified-html');
        return MessageParser::getInstance()->parse($this->getMessage(), $this->enableSmilies, $this->enableHtml, $this->enableBBCodes);
    }

    /**
     * @return array<\wcf\data\tag\Tag>
     */
    public function getTags()
    {
        $tags = TagEngine::getInstance()->getObjectTags(static::objectType, 
                                                        $this->{static::getDatabaseTableIndexName()}, 
                                                        array(($this->languageID === null ? LanguageFactory::getInstance()->getDefaultLanguageID() : $this->languageID))
                                                        );
        return $tags;
    }

    /**
     * {@inheritdoc}
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * {@inheritdoc}
     */
    public function getUserID()
    {
        return $this->userID;
    }

    /**
     * {@inheritdoc}
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param int $categoryID
     */
    public function setCategoryID($categoryID)
    {
        $this->categoryIDs[] = $categoryID;
    }

    /**
     * @param array<int> $categoryIDs
     */
    public function setCategoryIDs(array $categoryIDs)
    {
        $this->categoryIDs = $categoryIDs;
    }
}
