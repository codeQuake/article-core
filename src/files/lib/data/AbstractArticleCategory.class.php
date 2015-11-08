<?php
namespace wcf\data;

use wcf\data\category\AbstractDecoratedCategory;
use wcf\system\breadcrumb\Breadcrumb;
use wcf\system\breadcrumb\IBreadcrumbProvider;
use wcf\system\category\CategoryHandler;
use wcf\system\category\CategoryPermissionHandler;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;

/**
 * Abstract class for all article based category database objects.
 *
 * @author Jens Krumsieck
 * @copyright 2013-2015 codeQuake
 * @license GNU Lesser General Public License <http://opensource.org/licenses/lgpl-license.php>
 * @package de.codequake.core.article
 */
abstract class AbstractArticleCategory extends AbstractDecoratedCategory implements IBreadcrumbProvider
{
    const OBJECT_TYPE_NAME = '';
    const PERMISSION_PREFIX = 'wcf.articles';

    private static $categoryController = 'Category';

    protected $permissions;

    /**
     * @return bool
     */
    public function isAccessible()
    {
        if ($this->getObjectType()->objectType != self::OBJECT_TYPE_NAME) {
            return false;
        }

        return $this->getPermission('canViewCategory');
    }

    /**
     * @param string $permission
     * @return bool
     */
    public function getPermission($permission)
    {
        if ($this->permissions === null) {
            $this->permissions = CategoryPermissionHandler::getInstance()->getPermissions($this->getDecoratedObject());
        }

        if (array_key_exists($permission, $this->permissions)) {
            return $this->permissions[$permission];
        }

        return (WCF::getSession()->getPermission('user.'.self::PERMISSION_PREFIX.$permission) || WCF::getSession()->getPermission('mod.'.self::PERMISSION_PREFIX.$permission) || WCF::getSession()->getPermission('admin.'.self::PERMISSION_PREFIX.$permission));
    }

    /**
     * @return \wcf\system\breadcrumb\Breadcrumb
     */
    public function getBreadcrumb()
    {
        $classParts = explode('\\', get_called_class());
        return new Breadcrumb(WCF::getLanguage()->get($this->title), LinkHandler::getInstance()->getLink(self::$categoryController, array(
            'application' => $classParts[0],
            'object' => $this->getDecoratedObject(),
        )));
    }

    /**
     * @param string[] $permissions
     * @return int[]
     */
    public static function getAccessibleCategoryIDs($permissions = array('canViewCategory'))
    {
        $categoryIDs = array();
        foreach (CategoryHandler::getInstance()->getCategories(self::OBJECT_TYPE_NAME) as $category) {
            $result = true;
            $category = new self($category);
            foreach ($permissions as $permission) {
                $result = $result && $category->getPermission($permission);
            }

            if ($result) {
                $categoryIDs[] = $category->categoryID;
            }
        }

        return $categoryIDs;
    }

}
