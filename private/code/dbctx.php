<?php

namespace Code;

class DbCtx
{
    private static $instance;
    private $pdo;
    private $prefix;
    private function __construct()
    {
        global $config;
        $dbCfg = $config->database;
        $this->pdo = new \PDO('mysql:host=' . $dbCfg->server . ';dbname=' . $dbCfg->database,
            $dbCfg->user, $dbCfg->password);
        self::$instance = $this;
        $this->prefix = $dbCfg->prefix . '_' ?? '';
        error_log(__FILE__ . ':' . __LINE__ . ' ' . __FUNCTION__ . ' db-pdo constructed');
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
    public function upgrade(string $dbf): void
    {
        error_log(__FILE__.':'.__LINE__. ' '. __FUNCTION__);
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
}
