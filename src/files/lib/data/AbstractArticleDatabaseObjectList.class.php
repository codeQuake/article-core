<?php
namespace wcf\data;

/**
 * Abstract list class for all article based database objects.
 *
 * @author Jens Krumsieck
 * @copyright 2013-2015 codeQuake
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package de.codequake.core.article
 */
abstract class AbstractArticleDatabaseObjectList extends DatabaseObjectList
{
    /**
     * @var bool
     */
    public $categoryList = true;

    /**
     * {@inheritdoc}
     */
    public function readObjects()
    {
        //get classes
        $baseClass = $this->className;
        $classParts = explode('\\', get_called_class());
        $articleType = explode('.', $baseClass::$objectType);

        parent::readObjects();
        if ($this->categoryList) {
            if (0 !== count($this->objectIDs)) {
                $conditionBuilder = new PreparedStatementConditionBuilder();
                $conditionBuilder->add($this->getDatabaseTableIndexName().' IN (?)', array($this->objectIDs));

                $sql = '
                    SELECT *
                    FROM '.$classParts[0].WCF_N.'_'.$articleType.'_to_category
                    '.$conditionBuilder;
                $statement = WCF::getDB()->prepareStatement($sql);
                $statement->execute($conditionBuilder->getParameters());

                while ($row = $statement->fetchArray()) {
                    if (isset($this->objects[$row[$this->getDatabaseTableIndexName()]])) {
                        $this->objects[$row[$this->getDatabaseTableIndexName()]]->setCategoryID($row['categoryID']);
                    }
                }
            }
        }
    }

    /**
     * @param bool $enable
     */
    public function isCategoryList($enable = true)
    {
        $this->categoryList = $enable;
    }
}
