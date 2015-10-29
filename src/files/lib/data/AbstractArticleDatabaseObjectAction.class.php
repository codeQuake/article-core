<?php
namespace wcf\data;

use wcf\system\attachment\AttachmentHandler;
use wcf\system\language\LanguageFactory;
use wcf\system\tagging\TagEngine;
use wcf\system\visitTracker\VisitTracker;

/**
 * Abstract action class for all article based database objects.
 *
 * @author Jens Krumsieck
 * @copyright 2013-2015 codeQuake
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package de.codequake.core.article
 */
class AbstractArticleDatabaseObjectAction extends AbstractDatabaseObjectAction
{
    /**
     * Identifier where unread article count is saved
     * @var string
     */
    protected static $userStorageIdentifier = '';

    /**
     * {@inheritdoc}
     */
    public function create()
    {
        $data = $this->parameters['data'];

        //count attachments
        if (isset($this->parameters['attachmentHandler']) && $this->parameters['attachementHandler'] !== null) {
            $data['attachments'] = count($this->parameters['attachmentHandler']);
        }

        //get classes
        $baseClass = $this->className;
        $articleClass = $baseClass::getBaseClass();

        //create object
        $article = parent::create();
        $editor = new $baseClass($article);

        //update attachments
        if (isset($this->parameters['attachmentHandler']) && $this->parameters['attachmentHandler'] !== null) {
            $this->parameters['attachmentHandler']->updateObjectID($article->{$baseClass::getDatabaseTableIndexName()});
        }

        //handle categories
        $editor->updateCategoryIDs($this->parameters['categoryIDs']);
        $editor->setCategoryIDs($this->parameters['categoryIDs']);

        //handle languageID
        $languageID = (!isset($data['languageID']) || ($data['languageID'] === null)) ? LanguageFactory::getInstance()->getDefaultLanguageID() : $data['languageID'];

        //handle tags
        if (!empty($this->parameters['tags'])) {
            TagEngine::getInstance()->addObjectTags($articleClass::$objectType, $article->{$baseClass::getDatabaseTableIndexName()}, $this->parameters['tags'], $languageID);
        }

        return $article;
    }

    /**
     * {@inheritdoc}
     */
    public function update()
    {
        //get classes
        $baseClass = $this->className;
        $articleClass = $baseClass::getBaseClass();

        //count attachments
        if (isset($this->parameters['attachmentHandler']) && $this->parameters['attachementHandler'] !== null) {
            $data['attachments'] = count($this->parameters['attachmentHandler']);
        }

        parent::update();
        foreach ($this->objects as $article) {
            $this->objectIDs[] = $article->{$baseClass::getDatabaseTableIndexName()};
        }

        foreach ($this->objects as $news) {
            if (isset($this->parameters['categoryIDs'])) {
                 $news->updateCategoryIDs($this->parameters['categoryIDs']);
            }
            // update tags
            $tags = array();
            if (isset($this->parameters['tags'])) {
                $tags = $this->parameters['tags'];
                unset($this->parameters['tags']);
            }
            if (!empty($tags)) {
                $languageID = (!isset($this->parameters['data']['languageID']) || ($this->parameters['data']['languageID'] === null)) ? LanguageFactory::getInstance()->getDefaultLanguageID() : $this->parameters['data']['languageID'];
                TagEngine::getInstance()->addObjectTags($articleClass::$objectType, $article->{$baseClass::getDatabaseTableIndexName()}, $tags, $languageID);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete()
    {
        //get classes
        $baseClass = $this->className;
        $articleClass = $baseClass::getBaseClass();

        $attachedNewsIDs = array();
        foreach ($this->objects as $article) {
            $this->objectIDs[] = $article->{$baseClass::getDatabaseTableIndexName()};
            if ($article->attachments != 0) {
                $attachedObjectIDs[] = $article->{$baseClass::getDatabaseTableIndexName()};
            }
        }

        // remove attachments
        if (0 !== count($attachedObjectIDs)) {
            AttachmentHandler::removeAttachments($articleClass::$objectType, $attachedObjectIDs);
        }

        return parent::delete();
    }

    /**
     * Validates parameters to mark news as read.
     */
    public function validateMarkAsRead()
    {
        if (0 === count($this->objects)) {
            $this->readObjects();

            if (0 === count($this->objects)) {
                throw new UserInputException('objectIDs');
            }
        }
    }

    /**
     * Mark news as read.
     */
    public function markAsRead()
    {
        //get classes
        $baseClass = $this->className;
        $articleClass = $baseClass::getBaseClass();

        if (empty($this->parameters['visitTime'])) {
            $this->parameters['visitTime'] = TIME_NOW;
        }

        if (0 === count($this->objects)) {
            $this->readObjects();
        }

        foreach ($this->objects as $article) {
            VisitTracker::getInstance()->trackObjectVisit($articleClass::$objectType, $article->{$baseClass::getDatabaseTableIndexName()}, $this->parameters['visitTime']);
        }

        // reset storage
        if (WCF::getUser()->userID) {
            UserStorageHandler::getInstance()->reset(array(WCF::getUser()->userID), self::$userStorageIdentifier);
        }
    }

    /**
     * Validates parameters to mark all news as read.
     */
    public function validateMarkAllAsRead()
    {
    }

    /**
     * Marks all news as read.
     */
    public function markAllAsRead()
    {
        //get classes
        $baseClass = $this->className;
        $articleClass = $baseClass::getBaseClass();

        VisitTracker::getInstance()->trackTypeVisit($articleClass::$objectType);

        // reset storage
        if (WCF::getUser()->userID) {
            UserStorageHandler::getInstance()->reset(array(WCF::getUser()->userID), self::$userStorageIdentifier);
        }
    }
}
