<?php
namespace wcf\data;

use wcf\system\database\util\PreparedStatementConditionBuilder;
use wcf\system\language\LanguageFactory;
use wcf\system\visitTracker\VisitTracker;
use wcf\system\SingletonFactory;
use wcf\system\WCF;

/**
 * manages the category cache
 *
 * @author Jens Krumsieck
 * @copyright 2013-2015 codeQuake
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package de.codequake.core.article
 */

abstract class AbstractArticleCategoryCache
{
    protected $unreadArticles;

    protected $articles = array();

    private static $articleType = 'article';

    const OBJECT_TYPE = '';

    /**
     * @param int $categoryID
     *
     * @return int
     */
    public function getArticles($categoryID)
    {
        if ($this->articles === null) {
            $this->initDBOs();
        }

        if (array_key_exists($categoryID, $this->articles)) {
            return $this->articles[$categoryID];
        }

        return 0;
    }

    /**
     * @param int $categoryID
     *
     * @return int
     */
    public function getUnreadArticles($categoryID)
    {
        if ($this->articles === null) {
            $this->initUnreadArticles();
        }

        if (array_key_exists($categoryID, $this->unreadArticles)) {
            return $this->unreadArticles[$categoryID];
        }

        return 0;
    }

    protected function initDBOs()
    {
        //Get application
        $classParts = explode('\\', get_called_class());
        
        $conditionBuilder = new PreparedStatementConditionBuilder();
        $conditionBuilder->add(self::$articleType.'.isDeleted = 0');
        $conditionBuilder->add(self::$articleType.'.isDisabled = 0');

        // apply language filter
        if (LanguageFactory::getInstance()->multilingualismEnabled() && count(WCF::getUser()->getLanguageIDs())) {
            $conditionBuilder->add('('.self::$articleType.'.languageID IN (?) OR '.self::$articleType.'.languageID IS NULL)', array(
                WCF::getUser()->getLanguageIDs(),
            ));
        }

        $sql = 'SELECT		COUNT(*) AS count, '.self::$articleType.'_to_category.categoryID
				FROM		'.$classParts[0].WCF_N.'_'.self::$articleType.' '.self::$articleType.'
				LEFT JOIN	'.$classParts[0].WCF_N.'_'.self::$articleType.'_to_category '.self::$articleType.'_to_category
				ON		('.self::$articleType.'_to_category.'.self::$articleType.'ID = '.self::$articleType.'.'.self::$articleType.'ID)
				'.$conditionBuilder.'
				GROUP BY	'.self::$articleType.'_to_category.categoryID';
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute($conditionBuilder->getParameters());
        while ($row = $statement->fetchArray()) {
            $this->articles[$row['categoryID']] = $row['count'];
        }
    }

    protected function initUnreadArticle()
    {
        //Get application
        $classParts = explode('\\', get_called_class());

        if (WCF::getUser()->userID) {
            $conditionBuilder = new PreparedStatementConditionBuilder();
            $conditionBuilder->add(self::$articleType.'.lastChangeTime > ?', array(
                VisitTracker::getInstance()->getVisitTime(self::OBJECT_TYPE),
            ));
            $conditionBuilder->add(self::$articleType.'.isDeleted = 0');
            $conditionBuilder->add(self::$articleType.'.isDisabled = 0');
            $conditionBuilder->add('tracked_visit.visitTime IS NULL');
            // apply language filter
            if (LanguageFactory::getInstance()->multilingualismEnabled() && count(WCF::getUser()->getLanguageIDs())) {
                $conditionBuilder->add('('.self::$articleType.'.languageID IN (?) OR '.self::$articleType.'.languageID IS NULL)', array(
                    WCF::getUser()->getLanguageIDs(),
                ));
            }

            $sql = 'SELECT		COUNT(*) AS count, '.self::$articleType.'_to_category.categoryID
				FROM		'.$classParts[0].WCF_N.'_'.self::$articleType.' '.self::$articleType.'
				LEFT JOIN	wcf'.WCF_N.'_tracked_visit tracked_visit
				ON		(tracked_visit.objectTypeID = '.VisitTracker::getInstance()->getObjectTypeID(self::OBJECT_TYPE).' AND tracked_visit.objectID = '.self::$articleType.'.'.self::$articleType.'ID AND tracked_visit.userID = '.WCF::getUser()->userID.')
				LEFT JOIN	'.$classParts[0].WCF_N.'_'.self::$articleType.'_to_category '.self::$articleType.'_to_category
				ON		('.self::$articleType.'_to_category.'.self::$articleType.'ID = '.self::$articleType.'.'.self::$articleType.'ID)
				'.$conditionBuilder.'
				GROUP BY	'.self::$articleType.'_to_category.categoryID';
            $statement = WCF::getDB()->prepareStatement($sql);
            $statement->execute($conditionBuilder->getParameters());

            while ($row = $statement->fetchArray()) {
                $this->unreadArticles[$row['categoryID']] = $row['count'];
            }
        }
    }
}
