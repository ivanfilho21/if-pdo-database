<?php

namespace IvanFilho\Database;

/**
 * Class Utils
 * 
 * Useful methods used in the other classes from the database package.
 * 
 * @package      Database
 * @subpackage   src
 * @author       Ivan Filho <ivanfilho21@gmail.com>
 * 
 * Created: Jul 22, 2019.
 * Last Modified: Jan 22, 2020.
 *
 */
class Utils
{

    /**
     * Return the column to be selected from the current table.
     * 
     * @param Table $table
     * @param string $columnName
     * 
     * @return Column
     */
    public static function createSelection(Table $table, string $columnName)
    {
        return $table->findColumn($columnName);
    }

    /**
     * Return the column whose value is used to filter the records to be selected from the current table.
     * 
     * @param Table $table
     * @param string $columnName
     * @param string $value
     * @param bool $like
     * 
     * @return Column
     */
    public static function createConditionFromValue(Table $table, string $columnName, string $value, bool $like = false)
    {
        $condition = $table->findColumn($columnName);
        $condition->setValue($value);
        $condition->setExtra(($like) ? 'like' : $condition->getExtra());
        return $condition;
    }

    /**
     * Get value from a object.
     * The property name is converted to a getter method notation.
     * If the getter is not found in the object, then the property is returned.
     * 
     * @param object $obj
     * @param string $property
     * 
     * @return mixed
     */
    public static function getValueFromObject(object $obj, string $property)
    {
        $value = NULL;
        $getter = Utils::getGetterFromColumnName($property);

        if (method_exists($obj, $getter)) {
            $value = $obj->$getter();
        } elseif (property_exists($obj, $property)) {
            $value = $obj->$property;
        }

        return $value;
    }

    /**
     * Return the column whose value is used to filter the records to be selected from the current table.
     * 
     * @param Table $table
     * @param object $obj
     * @param string $columnName
     * @param bool $like
     * 
     * @return Column
     */
    public static function createConditionFromObject(Table $table, object $obj, string $columnName, bool $like = false)
    {
        $condition = $table->findColumn($columnName);
        $value = Utils::getValueFromObject($obj, $columnName);
        $condition->setValue($value);
        $condition->setExtra(($like) ? 'like' : $condition->getExtra());
        return $condition;
    }

    /**
     * Remove a string from the end of another string if it exists.
     * 
     * @param string $sourceStr The source string.
     * @param string $str The string to be removed.
     * 
     * @return string
     */
    public static function removeLastString(string $sourceStr, string $str)
    {
        $sub = substr($sourceStr, strlen($sourceStr) - strlen($str));
        return $sub === $str ? substr($sourceStr, 0, strlen($sourceStr) - strlen($str)) : $sourceStr;
    }

    /**
     * Form the name of the getter of a given column name using reflection.
     * 
     * @param string $columnName
     * 
     * @return string
     */
    private static function getGetterFromColumnName(string $columnName)
    {
        $brokenColName = explode('_', $columnName);
        $brokenColName[0] = isset($brokenColName[0]) ? ucfirst($brokenColName[0]) : '';
        $brokenColName[1] = isset($brokenColName[1]) ? ucfirst($brokenColName[1]) : '';

        $getter = 'get';
        $getter .= implode('', $brokenColName);

        return empty($columnName) ? '' : $getter;
    }

    /**
     * Append pseudo-values from an array of Columns. This produces something like ":id = id".
     * 
     * @param array $columns
     * @param bool $includePK Whether or not to include the primary key of the table.
     * 
     * @return string
     */
    public static function getPseudoValuesFromColumnArray(array $columns, bool $includePK = true)
    {
        $fields = '';
        foreach ($columns as $column) {
            if (empty($column->getName())) {
                continue;
            }
            if (! $includePK && $column->getKey() == 'PRIMARY KEY') {
                continue;
            }
            $fields .= BQ .$column->getName() .BQ .' = ' .CL .$column->getName() .COMMA;
        }
        $fields = Utils::removeLastString($fields, COMMA);
        return $fields;
    }

    /**
     * Append values from an array of Columns. This produces something like "`id`, `name`".
     * 
     * @param array $columns
     * @param bool $includePK Whether or not to include the primary key of the table.
     * @param bool $fullInformation Whether or not to include type and length of columns.
     * 
     * @return string
     */
    public static function getFieldsFromColumnArray(array $columns, bool $includePK = true, bool $fullInformation = true)
    {
        $fields = '';
        foreach ($columns as $column) {
            if (empty($column->getName())) {
                continue;
            }
            if (! $includePK && $column->getKey() == 'PRIMARY KEY') {
                continue;
            }
            $fields .= ($fullInformation ? $column->getColumnInformation() : BQ .$column->getName() .BQ) .COMMA;
        }
        $fields = Utils::removeLastString($fields, COMMA);
        return $fields;
    }

}