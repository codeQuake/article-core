<?php
namespace wcf\data;

/**
 * Abstract class for viewable article database objects
 *
 * @author Jens Krumsieck
 * @copyright 2013-2015 codeQuake
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package de.codequake.core.article
 */
abstract class AbstractViewableArticleDatabaseObject extends DatabaseObjectDecorator
{
    protected $effectiveVisitTime;
  
    public $userProfile;

     /**
     * Returns when the active user visited this news.
     *
     * @return int
     */
    public function getVisitTime()
    {
        //set base class
        $baseClass = self::$baseClass;

        if ($this->effectiveVisitTime === null) {
            if (WCF::getUser()->userID) {
                $this->effectiveVisitTime = max($this->visitTime, VisitTracker::getInstance()->getVisitTime($baseClass::$objectType));
            } else {
                $this->effectiveVisitTime = max(VisitTracker::getInstance()->getObjectVisitTime($baseClass::$objectType, $baseClass::getDatabaseTableIndexName()), VisitTracker::getInstance()->getVisitTime($baseClass::$objectType));
            }
            if ($this->effectiveVisitTime === null) {
                $this->effectiveVisitTime = 0;
            }
        }

        return $this->effectiveVisitTime;
    }

    /**
     * Returns if this news is new for the active user.
     *
     * @return bool
     */
    public function isNew()
    {
        return ($this->lastChangeTime > $this->getVisitTime());
    }

    /**
     * @return \wcf\data\user\UserProfile
     */
    public function getUserProfile()
    {
        if ($this->userProfile === null) {
            $this->userProfile = new UserProfile(new User($this->getDecoratedObject()->userID));
        }

        return $this->userProfile;
    }
}
}
