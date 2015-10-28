<?php
namespace wcf\data;

use wcf\system\attachment\AttachmentHandler;
use wcf\system\language\LanguageFactory;
use wcf\system\tagging\TagEngine;

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
}
