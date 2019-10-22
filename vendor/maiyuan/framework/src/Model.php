<?php
namespace MDK;

use Phalcon\DI;
use Phalcon\Mvc\Model as PhalconModel;
use Phalcon\Mvc\Model\Query\Builder;

/**
 * Abstract Model.
 *
 * @method static findFirstById($id)
 * @method static findFirstByLanguage($name)
 */
abstract class Model extends PhalconModel
{

    /**
     * Create Cache Key
     * @param $parameters
     * @return string
     */
    protected static function _createKey($parameters)
    {
        $key = get_called_class().json_encode($parameters);

        return $key;
    }

    protected static function _getCache($key)
    {

    }

    public static function find($parameters = [])
    {
        if (isset($parameters['cache'])) {
            if (!is_array($parameters['cache'])){
                $parameters['cache'] = [];
            }
            $parameters['cache']['key'] = isset($parameters['cache']['key']) ?: self::_createKey($parameters);
            $parameters['cache']['lifetime'] = isset($parameters['cache']['lifetime']) ?: null;
            $parameters['cache']['service'] = isset($parameters['cache']['service']) ?: 'modelsCache';
        }
        return parent::find($parameters);
    }

    /**
     * 字段和表名处理
     * @access protected
     * @param string $key
     * @return string
     */
    protected static function parseKey(&$key) {
        $key   =  trim($key);
        if(!is_numeric($key) && !preg_match('/[,\'\"\*\(\)`.\s]/',$key)) {
            $key = '"'.$key.'"';
        }
        return $key;
    }

    /**
     * 字段和表名处理
     * @access protected
     * @param string $key
     * @return string
     */
    protected static function parseValue(&$value) {
        if(is_string($value)) {
            $value =  "'".addslashes($value)."'";
        }elseif(isset($value[0]) && is_string($value[0]) && strtolower($value[0]) == 'exp'){
            $value =  addslashes($value[1]);
        }elseif(is_array($value)) {
            $value =  array_map('self::parseValue', $value);
        }elseif(is_bool($value)){
            $value =  $value ? '1' : '0';
        }elseif(is_null($value)){
            $value =  'null';
        }
        return $value;
    }

    /**
     * 批量插入数据
     * @param array $datas
     * @return bool
     */
    public static function insertMulit($datas = []){
        if (is_null($datas) || empty($datas)){
            return false;
        }

        $fields = array_map("self::parseKey", array_keys($datas[0]));
        foreach ($datas AS $data)
        {
            $value   =  array();
            foreach ($data as $key=>$val){
                if(is_array($val) && 'exp' == $val[0]){
                    $value[]   =  $val[1];
                }elseif(is_null($val)){
                    $value[]   =   'NULL';
                }elseif(is_scalar($val)){
                    $value[] = self::parseValue($val);
                }
            }
            $values[]    = '('.implode(',', $value).')';
        }
        return DI::getDefault()->get('db')->execute("INSERT INTO blogger.".self::getTableName()."(".implode(',', $fields).") VALUES ".implode(',',$values));
    }

    /**
     * 批量更新
     * @param array $datas
     * @param string $where
     * @return bool
     */
    public static function updateMulit($datas = [], $where = '1'){
        if (is_null($datas) || empty($datas)){
            return false;
        }
        $update = [];
        foreach ($datas AS $field => $value)
        {
            $update[] = self::parseKey($field).' = '.self::parseValue($value);
        }
        $update = implode(', ', $update);
        return DI::getDefault()->get('db')->execute("UPDATE ".self::getTableName()." SET {$update} WHERE {$where}");
    }

    /**
     * 自定义主键查询
     * @param array $parameters
     * @return array|bool
     */
    public static function column($parameters = [])
    {
        if (empty($parameters)){
            return false;
        }
        $columns = explode(',', preg_replace('/\s+/', '', $parameters['columns']));
        if (count($columns) <= 1){
            unset($parameters['columns']);
        }
        $data = self::find($parameters)->toArray();
        if (empty($data))
        {
            return false;
        }
        $fields = isset($columns) ? $columns : array_keys($data[0]);
        $result = [];
        foreach($data AS $item)
        {
            $result[$item[$fields[0]]] = count($item) > 2 ? $item : $item[$fields[1]];
        }
        return $result;
    }



    /**
     * Get table name.
     *
     * @return string
     */
    public static function getTableName()
    {
        preg_match('/\\\(.*)\\\(.*)/is', get_called_class(), $modelName);
        $modelName = $modelName[2];
        $modelName = preg_replace("/([A-Z])/", ",\\1", $modelName);
        $modelName = explode(',', $modelName);
        $modelName = array_filter($modelName);
        $modelName = strtolower(implode('_', $modelName));

        $reader = DI::getDefault()->get('annotations');
        $reflector = $reader->get(get_called_class());
        $annotations = $reflector->getClassAnnotations();

        return $annotations->get('Schema')->getArgument(0).'.'.$modelName;
    }

    /**
     * Find method overload.
     * Get entities according to some condition.
     *
     * @param string      $condition Condition string.
     * @param array       $params    Condition params.
     * @param string|null $order     Order by field name.
     * @param string|null $limit     Selection limit.
     */
    public static function get($condition, $params, $order = null, $limit = null)
    {
        $condition = vsprintf($condition, $params);
        $parameters = [$condition];

        if ($order) {
            $parameters['order'] = $order;
        }

        if ($limit) {
            $parameters['limit'] = $limit;
        }

        return self::find($parameters);
    }

    /**
     * FindFirst method overload.
     * Get entity according to some condition.
     *
     * @param string      $condition Condition string.
     * @param array       $params    Condition params.
     * @param string|null $order     Order by field name.
     *
     * @return Model
     */
    public static function getFirst($condition, $params, $order = null)
    {
        $condition = vsprintf($condition, $params);
        $parameters = [$condition];

        if ($order) {
            $parameters['order'] = $order;
        }

        return self::findFirst($parameters);
    }

    /**
     * Get builder associated with table of this model.
     *
     * @param string|null $tableAlias Table alias to use in query.
     *
     * @return Builder
     */
    public static function getBuilder($tableAlias = null)
    {
        $builder = new Builder();
        $table = get_called_class();
        if (!$tableAlias) {
            $builder->from($table);
        } else {
            $builder->addFrom($table, $tableAlias);
        }

        return $builder;
    }

}
