<?php
namespace wcf\data;

use wcf\system\WCF;

/**
 * Abstract editor class for all article based database objects.
 *
 * @author Jens Krumsieck
 * @copyright 2013-2015 codeQuake
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package de.codequake.core.article
 */
abstract class AbstractArticleDatabaseObjectEditor extends DatabaseObjectEditor
{
    /**
     * @param int[] $categoryIDs
     */
    public function updateCategoryIDs(array $categoryIDs = array())
    {
        //remove old
        $classParts = explode('\\', get_called_class());
        $baseClass = static::getBaseClass();
        $articleType = explode('.', $baseClass::$objectType);
        $sql = '
            DELETE FROM '.$classParts[0].WCF_N.'_'.$articleType.'_to_category
            WHERE '.static::getDatabaseTableIndexName().' = ?';
        $statement = WCF::getDB()->prepareStatement($sql);
        $statement->execute(array($this->{static::getDatabaseTableIndexName()}));

        //assign new
        if (count($categoryIDs) !== 0) {
            WCF::getDB()->beginTransaction();

            $sql = '
                INSERT INTO '.$classParts[0].WCF_N.'_'.$articleType.'_to_category
                    (categoryID, '.static::getDatabaseTableIndexName().')
                VALUES (?,?)';
            $statement = WCF::getDB()->prepareStatement($sql);
            foreach ($categoryIDs as $categoryID) {
                $statement->execute(array(
                    $categoryID,
                    $this->{static::getDatabaseTableIndexName()},
                ));
            }
            WCF::getDB()->commitTransaction();
        }
    }
}
