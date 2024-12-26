<?php

namespace Code\Db;

use Generator;
use PDO;
use PDOStatement;

/**
 * Creating clause items
 */
function makeClause(array $input)
{
    $ret = [];
    foreach ($input as $v) {
        $ret[] = '`' . $v . '`=:' . $v;
    }
    return $ret;
}

/**
 * adding backquotes so the items can be used in an SQL
 */
function addBackQuotes(array $input)
{
    $ret = [];
    foreach ($input as $v) {
        $ret[] = '`' . $v . '`';
    }
    return $ret;
}

/**
 * as placeholder names
 */
function prependColon(array $input)
{
    $ret = [];
    foreach ($input as $v) {
        $ret[] = ':' . $v;
    }
    return $ret;
}

class DbCtx
{
    private static $instance;
    private $pdo;
    private $prefix;
    private function __construct()
    {
        global $config;
        $dbCfg = $config->database;
        $tz = $config->timezone ?? 'utc';
        $this->pdo = new \PDO('mysql:host=' . $dbCfg->server . ';dbname=' . $dbCfg->database . ';timezone=' . $tz,
            $dbCfg->user, $dbCfg->password);
        $this->pdo->exec('SET time_zone = \'' . $tz . '\' ');
        self::$instance = $this;
        $this->prefix = $dbCfg->prefix . '_' ?? '';
        // error_log(__FILE__ . ':' . __LINE__ . ' ' . __FUNCTION__ . ' db-pdo constructed');
    }

    /**
     * returns existing or creates new instance
     * @@return DbCtx
     */
    public static function getCtx(): DbCtx
    {
        return self::$instance ?? new self();
    }

    /**
     * @return void
     */
    public function upgradeDatabase(string $dbf): void
    {
        error_log(__FILE__ . ':' . __LINE__ . ' ' . __FUNCTION__);
        $content = file_get_contents($dbf);
        $content = str_replace('${prefix}', $this->prefix, $content);
        // splitting the file at each occurance '^-- 2020-01-01 or similar dates'
        $pattern = '/^(-- \d{4}-\d{2}-\d{2}.*)$/m';
        $result = preg_split($pattern, $content, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        // running each part as a batch, it should not fail
        $lastSuccess = 'start of file';
        foreach ($result as $sqlParts) {
            $sqlParts = trim($sqlParts);
            if (preg_match($pattern, $sqlParts) == 1) {
                // this is a line '-- 2020-01-01...'
                error_log("db update up to $sqlParts");
                $lastSuccess = $sqlParts;
            } else {
                if ($sqlParts == '') {
                    // empty queries will fail, hence skipping them
                    continue;
                }
                try {
                    $this->pdo->exec($sqlParts);
                } catch (\PDOException $e) {
                    $msg = $e->getMessage();
                    error_log("got an exception $msg");
                    error_log($sqlParts);
                    break;
                }
            }
        }
    }

    /**
     * Basically a select, returns objects.
     * @param array<int,mixed> $criteria
     * @param string $tableName the table to get the rows from
     * @return Generator<mixed> of Objects with a classname equal to that tableName
     */
    public function findRows(string $tableName, array $criteria = [], string $suffix = ''): Generator
    {
        $stmt = $this->fetchStmt($tableName, $criteria, $suffix);
        while ($res = $stmt->fetchObject(__NAMESPACE__ . '\\' . $tableName)) {
            $res->ctx = $this;
            yield $res;
        }
    }

    /**
     * 
     * @return Generator
     */
    public function sqlAndRows(string $sql,string $tableName = ''): Generator
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        if(!empty($tableName)){
            while ($res = $stmt->fetchObject(__NAMESPACE__ . '\\' . $tableName)) {
                $res->ctx = $this;
                yield $res;
            }
        }
    }

    /**
     * Executes select and returns statement to read from
     * @return PDOStatement|bool
     * @param array<int,mixed> $criteria
     */
    private function fetchStmt(string $tableName, array $criteria, string $suffix): PDOStatement | bool
    {
        $sql = 'select * from `' . $this->prefix . $tableName . '`';
        if (count($criteria) > 0) {
            $keys = array_keys($criteria);
            $clause = makeClause($keys);
            $sql .= ' where ' . implode(' and ', $clause);
        }
        $sql .= $suffix;
        error_log(__FILE__ . ':' . __LINE__ . ' ' . __FUNCTION__ . ' ' . $sql);
        $stmt = $this->pdo->prepare($sql);
        foreach ($criteria as $key => $value) {
            if ($stmt->bindValue(':' . $key, $value)) {

            } else {
                error_log(__FILE__ . ':' . __LINE__ . ' binding parameter ' . $key . ' failed');
            };
        }
        $stmt->execute();
        return $stmt;
    }

    /**
     * @return void
     * @param mixed $row
     */
    public function storeRow($row): void
    {
        $tableName = basename(str_replace('\\', '/', get_class($row)));
        $rowDetails = $this->getRowDetails($tableName);
        $columns2store = array_keys($rowDetails);
        foreach ($columns2store as $idx => $propName) {
            if (!property_exists($row, $propName)) {
                unset($columns2store[$idx]);
            }
        }
        // constructing the SQL
        $sql = 'Replace into `' . $this->prefix . $tableName . '`( ' .
        implode(', ', addBackQuotes($columns2store)) . ' ) ' .
        ' values ( ' . implode(', ', prependColon($columns2store)) . ' )';
        $stmt = $this->pdo->prepare($sql);
        foreach ($columns2store as $name) {
            if (!$stmt->bindParam(':' . $name, $row->$name, $rowDetails[$name]->pdo_type)) {
                error_log('error in parameter binding in DbCtx StoreRow name=' . $name);
                return;
            }
        }
        $r = $stmt->execute();
    }

    private array $allRowDetails;

    /**
     * For storing, we need to know what can be stored in the database
     *
     * @return array
     */
    public function getRowDetails(string $tableName): array
    {
        if (isset($this->allRowDetails[$tableName])) {
            return $this->allRowDetails[$tableName];
        }
        // retrieve the columns to store, if available
        $sql = 'Select * from `' . $this->prefix . $tableName . '` limit 0';
        $stmt = $this->pdo->query($sql);
        $columnCount = $stmt->columnCount();
        $rowDetails = [];
        for ($i = 0; $i < $columnCount; ++$i) {
            $ci = $stmt->getColumnMeta($i);
            $name = $ci['name'];
            $rowDetails[$name] = (object) $ci;
        }
        $this->allRowDetails[$tableName] = $rowDetails;
        return $rowDetails;
    }
}
