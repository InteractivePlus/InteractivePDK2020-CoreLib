<?php
namespace InteractivePlus\PDK2020Core\Utils;
class PathUtil{
    /**
     * get the root path of this project(CoreLib)
     * @return string The root directory of this project, without a slash in the end.
     */
    public static function getProjectRootPath() : string{
        return __DIR__ . '/../..';
    }

    public static function getTemplatePath() : string{
        return self::getProjectRootPath() . '/templates';
    }
}