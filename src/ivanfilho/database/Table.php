<?php

namespace IvanFilho\Database;

use \PDO;
use \PDOStatement;

# Common SQL types
define('INT', 'INT');
define('DECIMAL', 'DECIMAL(8, 4)');
define('VARCHAR', 'VARCHAR');
define('TEXT', 'TEXT');
define('DATE', 'DATE');
define('TIME', 'TIME');
define('DATETIME', 'DATETIME');
define('TIMESTAMP', 'TIMESTAMP');

define('COMMA', ', ');
define('AND_A', ' AND ');
define('BQ', '`');
define('QT', "'");
define('CL', ':');

/**
 * Class Table
 * 
 * Common operations related to a table in the database.
 * 
 * Classes Dependency:
 * 
 * \PDO
 * \PDOStatement
 * IvanFilho\Database\Exceptions
 * IvanFilho\Database\Column
 * IvanFilho\Database\Utils
 * 
 * @package      Database
 * @subpackage   src
 * @author       Ivan Filho <ivanfilho21@gmail.com>
 * 
 * Created: Jul 22, 2019.
 * Last Modified: Jan 22, 2020.
 */
abstract class Table
{
    private $db;
    private $columns;
    private $name;
    private $model;

    public function __construct(PDO $db, string $name, array $columns = array(), string $model = '')
    {
        $this->db = $db;
        $this->name = $name;
        $this->columns = $columns;
        $this->model = $this->createModelName($model);
    }

    /**
     * Set the name of the current table.
     * 
     * @param string $name
     * 
     * @return void
     */
    public function setName(string $name) { $this->name = $name; }

    /**
     * Get the name of the current table.
     * 
     * @return string
     */
    public function getName() { return $this->name; }

    /**
     * Set the array of Columns of the current table.
     * 
     * @param array $columns
     * 
     * @return void
     */
    public function setColumns(array $columns) { $this->columns = $columns; }

    /**
     * Get the array of Columns of the current table.
     * 
     * @return array
     */
    public function getColumns() { return $this->columns; }

    /**
     * Set the model name of the current table.
     * 
     * @param string $model
     * 
     * @return void
     */
    public function setModel(string $model) { $this->model = $model; }

    /**
     * Set the model name of the current table.
     * 
     * @return string
     */
    public function getModel() { return $this->model; }

    /**
     * Add a Column object to the array of Columns of this table.
     * 
     * @param Column $column
     * 
     * @return void
     */
    public function addColumn(Column $column) { $this->columns[] = $column; }

    /**
     * Fetch a Column from the array of Columns of this table by its name.
     * 
     * @param string $columnName
     * 
     * @return Column
     */
    public function findColumn(string $columnName)
    {
        foreach ($this->columns as $c) {
            if ($c->getName() === $columnName) {
                return $c;
            }
        }
        return null;
    }

    /**
     * Create this table in database.
     * 
     * @return void
     */
    public function create()
    {
        if (empty($this->name)) {
            Exceptions::triggerError($this, __METHOD__, 'Table must have a name', E_USER_ERROR);
        }

        if (empty($this->columns)) {
            Exceptions::triggerError($this, __METHOD__, 'Table must contain at least one column', E_USER_ERROR);
        }

        $fields = Utils::getFieldsFromColumnArray($this->columns);
        $sql = 'CREATE TABLE IF NOT EXISTS ' .BQ .$this->name .BQ .' (' .$fields .')';
        $this->db->query($sql);
    }

    /**
     * Delete this table from database.
     * 
     * @return void
     */
    public function drop()
    {
        if (empty($this->name)) {
            Exceptions::triggerError($this, __METHOD__, 'Table must have a name', E_USER_ERROR);
        }

        $sql = 'DROP TABLE IF EXISTS ' .BQ .$this->name .BQ;
        $this->db->query($sql);
    }

    /**
     * Count records.
     * 
     * @return int
     */
    public function count()
    {
        return count($this->read(array(Utils::createSelection($this, 'id'))));
    }

    /**
     * Insert a new object in this table. Return last inserted id.
     * 
     * @return int
     */
    protected function insert(object $obj)
    {
        return $this->prepareValues('insert', $obj);
    }

    /**
     * Update a record in this table based on the object. If no conditions are passed the record is updated by its id.
     * 
     * @param object $obj
     * @param array $whereColumns Array of columns whose values are used to filter records.
     * 
     * @return void
     */
    protected function update(object $obj, array $whereColumns = array())
    {
        if (empty($whereColumns)) {
            $whereColumns[] = Utils::createConditionFromObject($this, $obj, 'id');
        }
        $this->prepareValues('update', $obj, $whereColumns, false);
    }

    /**
     * Fetch records from the current table.
     * 
     * @param array $selectColumns Array of columns to be selected.
     * @param array $whereColumns Array of columns whose values are used to filter records.
     * @param bool $asList Specify whether or not the fetch data is returned as an array.
     * @param string $limit Specify the limit of records to be fetched.
     * @param array $order Order the fetched records.
     * 
     * @return object
     * @return array
     */
    protected function read(array $selectColumns = array(), array $whereColumns = array(), bool $asList = false, string $limit = '', array $order = array())
    {
        $sql = $this->createSelectSQL($selectColumns, $whereColumns, $limit, $order);
        // echo $sql .'<br>'; #die();

        if (count($whereColumns) > 0) {
            $sql = $this->db->prepare($sql);
            $sql = $this->bindValues($sql, $whereColumns);

            $sql->setFetchMode(PDO::FETCH_INTO, new $this->model());
            $sql->execute();

            $object = new $this->model();

            if ($sql->rowCount() == 1) {
                $fetch = $sql->fetch();
                return $asList ? array($fetch) : $fetch;
            }
            elseif ($sql->rowCount() > 1) {
                return $sql->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, get_class($object));
            }
        }
        else {
            $object = new $this->model();
            $sql = $this->db->query($sql);

            if ($sql->rowCount() == 1) {
                $fetch = $sql->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, get_class($object));
                return $asList ? array($fetch) : $fetch;
            }
            elseif ($sql->rowCount() > 1) {
                return $sql->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, get_class($object));
            }
        }

        // return $asList ? array() : null;
        return array();
    }

    /**
     * Delete a record from this table.
     * 
     * @param array $whereColumns Array of columns whose values are used to filter records.
     * 
     * @return void
     */
    protected function delete(array $whereColumns)
    {
        $this->prepareValues('delete', null, $whereColumns, true);
    }

    /**
     * Bind values in a SQL statement.
     * 
     * @param PDOStatement $stmt
     * @param array $whereColumns Array of columns whose values are used to filter records.
     * 
     * @return PDOStatement
     */
    private function bindValues(PDOStatement $stmt, array $whereColumns)
    {
        foreach ($whereColumns as $column) {
            if (empty($column->getName())) {
                continue;
            }

            $value = $column->getValue();

            if ($column->getExtra() == 'like') {
                $value = QT .'%' .$column->getValue() .'%' .QT;
            }
            $stmt->bindValue(CL .$column->getName(), $value);
            // echo CL .$column->getName() .' = ' .$value .'<br>';
        }

        return $stmt;
    }

    /**
     * Execute an INSERT, UPDATE or DELETE operation.
     * 
     * @param string $operation The operation to be executed.
     * @param object $obj
     * @param array $whereColumns Array of columns whose values are used to filter records.
     * @param bool $includePK Whether or not to include the primary key of the table.
     * 
     * @return void
     */
    private function prepareValues(string $operation = '', object $obj = null, array $whereColumns = array(), bool $includePK = false)
    {
        if ($operation != 'delete' && empty($obj)) {
            Exceptions::triggerError($this, __METHOD__, "Object to <b>$operation</b> is empty", E_USER_ERROR);
        }

        $pseudoValues = Utils::getPseudoValuesFromColumnArray($this->columns, $includePK);
        $where = $this->formatWhereClause($whereColumns);

        if ($operation == 'insert') {
            $sql = 'INSERT INTO';
        }
        elseif ($operation == 'update') {
            $sql = 'UPDATE';
        }
        elseif ($operation == 'delete') {
            $sql = 'DELETE FROM';
        }
        else {
            return false;
        }

        $sql .= ' ' .BQ .$this->name .BQ;
        if (! empty($obj))
            $sql .= ' SET ' .$pseudoValues;
        $sql .= $where;
        $sql = $this->db->prepare($sql);

        # Bind values from the getters in a SQL statement
        if (! empty($obj)) {
            foreach ($this->columns as $column) {
                $columnName = $column->getName();
                $value = Utils::getValueFromObject($obj, $columnName);

                if (! $includePK && $column->getKey() == 'PRIMARY KEY') {
                    continue;
                }

                $sql->bindValue(CL .$columnName, $value);
                // echo CL .$columnName . ' = ' . $value . '<br>';
            }
        }

        $sql = $this->bindValues($sql, $whereColumns);
        $sql->execute();

        if ($operation == 'insert') {
            return $this->db->lastInsertId();
        }
    }

    /**
     * Create a SELECT statement string.
     * 
     * @param array $selectColumns Array of columns to be selected.
     * @param array $whereColumns Array of columns whose values are used to filter records.
     * @param string $limit Specify the limit of records to be fetched.
     * @param array $order Order the fetched records.
     * 
     * @return string
     */
    private function createSelectSQL(array $selectColumns = array(), array $whereColumns = array(), string $limit = '', array $order = array())
    {
        $table = BQ .$this->name .BQ;
        $select = $this->formatSelectClause($selectColumns);
        $where = $this->formatWhereClause($whereColumns);

        $sql = "SELECT $select FROM $table $where";

        if (count($order) > 0) {
            $sql .= ' ORDER BY ';
            foreach ($order as $o) {
                $sql .= BQ .$o['column']->getName() .BQ .' ' .$o['criteria'] .COMMA;
            }
            $sql = Utils::removeLastString($sql, COMMA);
        }
        $sql .= (! empty($limit)) ? ' LIMIT ' .$limit : '';

        return $sql;
    }

    /**
     * Append the names of columns of an array and separate them with commas.
     * 
     * @param array $columns
     * 
     * @return string
     */
    private function formatSelectClause(array $columns = array())
    {
        $clause = '';

        foreach ($columns as $column) {
            $clause .= BQ .$column->getName() .BQ .COMMA;
        }

        return empty($clause) ? '*' : Utils::removeLastString($clause, COMMA);
    }

    /**
     * Create a WHERE statement string based on an array of Columns.
     * 
     * @param array $columns
     * 
     * @return string
     */
    private function formatWhereClause(array $columns = array())
    {
        $clause = '';
        foreach ($columns as $column) {
            $operator = ($column->getExtra() === 'like') ? 'LIKE' : '=';
            $clause .= BQ .$column->getName() .BQ .' ' .$operator .' ' .CL .$column->getName() .AND_A;
        }
        $clause = Utils::removeLastString($clause, AND_A);
        return (! empty($clause) ? ' WHERE ' : '') .$clause;
    }

    /**
     * Form the name of the Model of a given model name using reflection.
     * If modelName is empty, the name of this table is used instead.
     * 
     * @param string $modelName
     * 
     * @return string
     */
    private function createModelName(string $modelName)
    {
        $modelName = empty($modelName) ? $this->getName() : $modelName;
        $broken = explode('_', $modelName);
        $broken[0] = isset($broken[0]) ? ucfirst($broken[0]) : '';
        $broken[1] = isset($broken[1]) ? ucfirst($broken[1]) : '';

        return Utils::removeLastString(implode('', $broken), 's');
    }

}