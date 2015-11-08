<?php
namespace wcf\data\category;

/**
 * manages the category cache
 *
 * @author Jens Krumsieck
 * @copyright 2013-2015 codeQuake
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package de.codequake.core.article
 */

abstract class AbstractArticleCategoryNode extends CategoryNode
{
    /**
     * {@inheritdoc}
     */
    protected static $baseClass = '';

    protected $unreadArticles;

    protected $articles;

    /**
     * @return int
     */
    public function getUnreadArticles()
    {
        if ($this->unreadArticles === null) {
            $this->unreadArticles = NewsCategoryCache::getInstance()->getUnreadArticles($this->categoryID);
        }

        return $this->unreadArticles;
    }

    /**
     * @return int
     */
    public function getArticles()
    {
        if ($this->articles === null) {
            $this->articles = NewsCategoryCache::getInstance()->getArticles($this->categoryID);
        }

        return $this->articles;
    }

}
