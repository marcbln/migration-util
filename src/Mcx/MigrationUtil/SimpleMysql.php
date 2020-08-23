<?php

namespace Mcx\MigrationUtil;


use WhyooOs\Util\UtilDebug;
use WhyooOs\Util\UtilDict;

/**
 * wrapper around pdo-mysql for some quick'n'easy access to the database
 *
 * 08/2020 created
 */
class SimpleMysql
{
    /**
     * @var \PDO
     */
    protected $pdo;


    /**
     * constructor ... optionally connects to DB
     *
     * @param null $host
     * @param null $dbname
     * @param null $username
     * @param null $password
     * @param string $charset
     */
    public function __construct($host=null, $dbname=null, $username=null, $password=null, $charset = 'utf8')
    {
        if($host && $dbname && $username) {
            $this->connect($host, $dbname, $username, $password, $charset);
        }
    }

    public function connect($host, $dbname, $username, $password, $charset = 'utf8')
    {
        $opt = [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => FALSE,
        ];
        $dsn = 'mysql:host=' . $host . ';dbname=' . $dbname . ';charset=' . $charset;
        $this->pdo = new \PDO($dsn, $username, $password, $opt);
    }


    /**
     * a helper function to run prepared statements smoothly
     *
     * @param string $query
     * @param array $args
     * @return \PDOStatement|bool If the database server successfully prepares the statement,
     */
    public function query(string $query, array $args = [])
    {
        if (empty($args)) {
            return $this->pdo->query($query);
        }
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($args);

        return $stmt;
    }


    /**
     * 08/2020 created
     *
     * @param string $query
     * @param array $args
     * @return array fetched rows (FETCH_ASSOC)
     */
    public function selectMany(string $query, array $args = [])
    {
        return $this->query($query, $args)->fetchAll();

    }

    /**
     * @param string $tbl
     * @param array $criteria eg ['uid' => 123]
     * @param array $update
     */
    public function update(string $tbl, array $criteria, array $update): void
    {
        if(empty($update)) {
            return;
        }
        
        // ---- construct SET part of query
        $arrSet = [];
        foreach($update as $key => $value) {
            $arrSet[] = "$key = :set_{$key}";
        }
        $set = implode(', ', $arrSet);
                
        // ---- construct WHERE part of query
        $arrWhere = [];
        foreach($criteria as $key => $value) {
            $arrWhere[] = "$key = :where_{$key}"; 
        }
        $where = empty($arrWhere) ? '1=1' : implode(' AND ', $arrWhere);
        
        
        $query = "UPDATE `{$tbl}` SET {$set} WHERE {$where}";

        $params = array_merge(UtilDict::prependToKeys($criteria, 'where_'), UtilDict::prependToKeys($update, 'set_'));

        $this->query($query, $params);
    }
}



