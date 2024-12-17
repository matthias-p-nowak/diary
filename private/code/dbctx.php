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

    }
}
