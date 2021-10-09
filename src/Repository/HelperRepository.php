<?php


namespace App\Repository;


use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\DBALException;

abstract class HelperRepository extends ServiceEntityRepository
{
    public $gd = 0x1D;
    public $fd = 0x1F;

    protected static $group_delimiter = 0x1D;
    protected static $items_delimiter = 0x1F;

    const SQL_DATE_FORMAT = 'Y-m-d';
    const SQL_DATE_TIME_FORMAT = 'Y-m-d H-i-s';

    public static function unConcat($regularlyConcatenatedValue)
    {
        $result = [];
        foreach(explode("\x1d", $regularlyConcatenatedValue) AS $arr) {
            $subResult = [];

            foreach(explode("\x1f", $arr) AS $singleValue) {
                $subResult[] = $singleValue;
            }
            $result[] = $subResult;
        }

        return $result;
    }

    public static function _genPQMS($count) {
        return "(" . implode(',', array_fill(0, $count, "?")) . ")";
    }

    public function genQMS($count)
    {
        return array_fill(0, $count, "?");
    }

    /**
     * TODO refact use since we can remove the catch if null
     * @param int $count
     * @return string
     */
    public function genParenthesesQMS(int $count): string
    {
        if($count === 0){
            return '(NULL)';
        }
        return "(" . implode(',', $this->genQMS($count)) . ")";
    }

    public function arrToPQMS($arr)
    {
        return $this->genParenthesesQMS(count($arr));
    }

    //TODO check the use of (null)
    public function genNupletsQMS($nupletsCount, $qmsPerNupletsCount) {
        $data = [];
        for($i = 0; $i < $nupletsCount; $i++) {
            $data[] = $this->genParenthesesQMS($qmsPerNupletsCount);
        }
        return implode(",", $data);
    }

    public function toIdArray($arr, $id_identifier = "id")
    {
        return array_map(static function($item) use ($id_identifier) { return $item[$id_identifier]; }, $arr);
    }

    //TODO use array_column and rename so it's note confusing
    public function toIntegerIdArray($arr, $id_identifier = "id")
    {
        return array_map(static function($item) use ($id_identifier) { return (int)$item[$id_identifier]; }, $arr);
    }

    /**
     * @param $query
     * @param array $params
     * @param null|string $fetchMode
     * @return array
     * @throws DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function sqlFetch($query, $params = [], $fetchMode = null) {
        try {
            $stmt = $this->_em->getConnection()->prepare($query);
            $stmt->execute(is_array($params) ? $params : [$params]);
            switch ($fetchMode){
                case 'one':
                    $results = $stmt->fetchAssociative();
                    break;
                case 'all':
                    $results = $stmt->fetchAllAssociative();
                    break;
                default:
                    $results = $stmt->fetchAll();
            }
            $stmt->free();
            return $results;
        } catch (DBALException $e) {
            throw $e;
        }
    }

    public function sqlFetchOneOrNull($query, $params = [], $fetchMode = null) {
        $res = $this->sqlFetch($query, $params, $fetchMode);
        return empty($res) ? null : $res;
    }

    /**
     * @param $query
     * @param $params
     * @return array
     * Does the same thing as SQL fetch but returns an empty array instead of null if no result.
     */
    public function sqlFetchArr($query, $params)
    {
        $tmp = $this->sqlFetch($query, $params);
        return empty($tmp) ? [] : $tmp;
    }

    /**
     * Do the fetch and directly transform it to an array of ids ["id" => "x"] to ["x"]
     * @param $sql
     * @param $params
     * @return array
     * @throws DBALException
     */
    public function sqlFetchIds($sql, $params)
    {
        return $this->toIdArray($this->sqlFetch($sql, $params));
    }

    //this should not be used with another field than id ( yeah name the field id is confusing when u can use toIntegerIdArray
    public function sqlFetchIntegerIds($sql, $params)
    {
        return $this->toIntegerIdArray($this->sqlFetch($sql, $params));

    }

    /**
     * @param $baseQuery string the query up to 'VALUES' (ex : 'INSERT INTO bla_bla(id,attr1,attr2) VALUES' )
     * @param $params [] the flat php array containing all the parameters.
     * @param $perInsertQMCount integer the amount of attributes filled in the insert (3 for the previous example)
     * @param $maxImportPerBatch integer the maximum amount of rows to insert in a single call.
     *
     */
    public function largeInsert($baseQuery, $params, $perInsertQMCount, $maxImportPerBatch = 5000)
    {
        $currentOffset = 0;
        $sub = array_slice($params, $currentOffset, $maxImportPerBatch*$perInsertQMCount);
        while(sizeof($sub)) {
            $this->sqlExec($baseQuery . $this->genNupletsQMS(count($sub)/$perInsertQMCount, $perInsertQMCount), $sub);
            $currentOffset = $currentOffset + $maxImportPerBatch*$perInsertQMCount;
            $sub = array_slice($params, $currentOffset, $maxImportPerBatch*$perInsertQMCount);
        }

    }

    /**
     * @param $baseQuery string the query complete with two ? (ex : 'UPDATE table t SET t.field = (?) WHERE t.fieldRef IN ?' )
     * @param $params array the array of fieldRef paired with the value ( ['fieldRef' => 'value'] )
     * @param $caseParam string the fieldRef of the case ( ex : 't.fieldRef' )
     * @param $maxImportPerBatch integer the maximum amount of rows to insert in a single call.
     * @throws DBALException
     */
    public function largeUpdateOneField($baseQuery, $params, $caseParam, $maxImportPerBatch = 5000): void
    {
        $currentOffset = 0;
        $sub = array_slice($params, $currentOffset, $maxImportPerBatch);
        while(sizeof($sub)) {

            $updateCase = "CASE ".$caseParam;
            foreach ($sub as $fieldRef => $value){
                $updateCase .= " WHEN ".$fieldRef." THEN '".$value."'";
            }
            $updateCase .= " END";

            $this->sqlExec($baseQuery, [ $updateCase, array_keys($sub)]);

            $currentOffset += $maxImportPerBatch;
            $sub = array_slice($params, $currentOffset, $maxImportPerBatch);
        }
    }

    /**
     * @param $query
     * @param array $params
     */
    public function sqlExec($query, $params = []) {
        try {
            $stmt = $this->_em->getConnection()->prepare($query);
            $stmt->execute(is_array($params) ? $params : [$params]);
            $stmt->free();
        } catch (DBALException $e) {
            throw $e;
        }
    }

    /**
     * Returns the last inserted ID.
     * @param $query
     * @param array $params
     */
    public function sqlInsert($query, $params = [])
    {
        try {
            $conn = $this->_em->getConnection();
            $stmt = $conn->prepare($query);
            $stmt->execute(is_array($params) ? $params : [$params]);
            $li = +$conn->lastInsertId();
            $stmt->free();
            return $li;
        } catch (DBALException $e) {
            throw $e;
        }
    }

    /**
     * WARN : use this only in case of int-based ids
     * @param $tableName
     * @return int
     * @throws DBALException
     */
    public function getFirstAddableIdOfTable($tableName)
    {
        $res = $this->sqlFetch("SELECT max(id) AS id FROM " . $tableName);
        if(empty($res)) {
            return 1;
        }

        return (int)$res[0]["id"] + 1;
    }

    public function nonEmptyFieldOrEmptyString($array, $key)
    {
        if(array_key_exists($key, $array)) {
            $res = $array[$key];
            if(empty($array[$key])) {
                return "";
            }
            return $res;
        }

        return "";
    }

    /**
     * Returns an array of the extracted property.
     * @param $propertyName
     * @param $array
     * @param bool $purgeNullValues
     * @return array
     * @throws \Exception
     */
    public function extractProperty($propertyName, $array, $purgeNullValues = false)
    {
        if(is_array($array)) {
            $result = [];
            foreach($array as $item) {
                if(!$purgeNullValues || $item[$propertyName] != null) {
                    $result[] = $item[$propertyName];
                }
            }
            return $result;
        } else {
            throw new \Exception("Provided object is not a valid array");
        }
    }

    public function uniqueValueList(...$arrays): array
    {
        return array_values(array_unique(array_merge(...$arrays)));
    }
}