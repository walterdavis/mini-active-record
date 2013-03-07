<?php
//TODO: add in the date stuff

// /**
//  * return a date formatted for the database
//  * @static
//  * @param  int intTimeStamp  A unix timestamp
//  * @return string  mysql format date string
//  */
// function DbDate($intTimeStamp=null)
// {
//  return date('Y-m-d', $intTimeStamp ? $intTimestamp:mktime() );
// }
// 
// /**
//  * return a datetime formatted for the database
//  * @static
//  * @param  int intTimeStamp  A unix timestamp
//  * @return string  mysql format datetime string
//  */  
// function DbDateTime($intTimeStamp=null)
// {
//  return date('Y-m-d H:i:s', $intTimeStamp ? $intTimeStamp:mktime() );
// }
// 
// /**
//  * return a unix timestamp from a database field
//  * @static
//  * @param  string  mysql datetime
//  * @return int unix timestamp  
//  */
// function TimeStamp($strMySQLDate)
// {
//  return strtotime($strMySQLDate,time());
// }
// 

//maximum number of results from a find_all
if(!defined('MAR_LIMIT')){
  define('MAR_LIMIT', 10000);
}
if(!defined('MAR_CHARSET')){
  define('MAR_CHARSET', 'UTF-8');
}
if(!defined('DB_CHARSET')){
  define('DB_CHARSET', 'utf8');
}
/**
 * MiniActiveRecord is ActiveRecord in PHP 5.3+
 * License: MIT
 *
 * @package MiniActiveRecord
 * @author Walter Lee Davis
 */
class MiniActiveRecord{
  public $has_many = '';
  public $has_many_through = '';
  public $belongs_to = '';
  public $has_and_belongs_to_many = '';
  public $validations = '';
  private static $_cache = array();
  private static $_db;
  private static $_table;
  private static $_class;
  private static $_columns;
  private static $_validations = array();
  private $_errors;
  function __construct($params = array()){
    self::initialize();
    foreach($params as $key => $val){
      $this->$key = $val;
    }
  }
  private function initialize(){
    try{
      $params = @parse_url(MAR_DSN);
      $dsn = sprintf('%s:host=%s;dbname=%s', $params['scheme'], $params['host'], str_replace('/','',$params['path']));
      $this->_db = new PDO($dsn, $params['user'],$params['pass'], array(
          PDO::ATTR_PERSISTENT => true
      ));
      $this->_db->exec("SET NAMES '" . DB_CHARSET . "';");
      $this->_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
      if(empty($this->_class)) $this->_class = get_class($this);
      if(empty($this->_table)) $this->_table = $this->table();
      if(empty($this->_columns)) $this->_columns = $this->columns();
      if(empty($this->_validations)) $this->_validations = $this->validations();
      foreach($this->_columns as $key => $col){
        $this->$key = $col['Default'];
      }
    }catch(MARException $e){
      print $e->getMessage();
    }
  }
  private function table(){
    $table = Inflector::tableize($this->_class);
    $tables = self::tables();
    $class = $this->_class;
    while(!array_search($table, $tables) && $class != 'MiniActiveRecord'){
      $class = get_parent_class($class);
      $table = Inflector::tableize($class);
    }
    return $table;
  }
  private function persisted(){
    return (isset($this->id) && $this->id > 0);
  }
  private function tables(){
    static $tables = array();
    if(count($tables) < 1){
      $result = $this->_db->query('SHOW TABLES');
      foreach($result as $row){
        $tables[] = $row[0];
      }
    }
    return $tables;
  }
  private function columns(){
    static $cache = array();
    $table = $this->_table;
    if(isset($cache[$table])){
      return $cache[$table];
    }else{
      if($result = $this->_db->query('SHOW COLUMNS FROM ' . $table)){
        $fields = array();
        while($row = $result->fetch(PDO::FETCH_ASSOC)){
          $fields[$row['Field']] = $row;
        }
        return $cache[$table] = $fields;
      }
    }
  }
  private function has_many(){
    $has_many = array();
    foreach(w($this->has_many) as $c){
      $obj = $this->$c;
      foreach($obj as $o){
        $has_many[$c][$o->id] = $o;
      }
    }
    return $has_many;
  }
  private function has_many_through(){
    $has_many = array();
    foreach(w($this->has_many_through) as $c){
      $c = explode(':', $c);
      $link_obj = $this->$c[1];
      foreach($link_obj as $lo){
        $obj = $lo->$c[0];
        $has_many_through[$c[0]][$obj->id] = $obj;
      }
    }
    return $has_many_through;
  }
  private function validations(){
    $validations = array();
    $rules = $this->validations;
    if(is_string($rules)) $rules = w($rules);
    foreach($rules as $v){
      $v = explode(':', $v);
      $validations[] = $v;
    }
    return $validations;
  }
  private function has_and_belongs_to_many(){
    $has_and_belongs_to_many = array();
    foreach(w($this->has_and_belongs_to_many) as $c){
      $obj = $this->$c;
      foreach($obj as $o){
        $has_and_belongs_to_many[$c][$o->id] = $o;
      }
    }
    return $has_and_belongs_to_many;
  }
  private function belongs_to(){
    $belongs_to = array();
    foreach(w($this->belongs_to) as $c){
      $obj = $this->$c;
      $belongs_to[$c][$obj->id] = $obj;
    }
    return $belongs_to;
  }
  function identity(){
    return md5(get_class($this) . $this->id);
  }
  function link_table($second_model){
    $names = array($this->_table, $second_model->_table);
    sort($names);
    return implode('_', $names);
  }
  function foreign_key(){
    return Inflector::singularize($this->_table) . '_id';
  }
  function create_link_table($second_model){
    if(!array_search($this->link_table($second_model), $this->tables())){
      $sql = 'CREATE TABLE `' . $this->link_table($second_model) . '` (
        `' . $this->foreign_key() . '` int(11) unsigned NOT NULL,
        `' . $second_model->foreign_key() . '` int(11) unsigned NOT NULL,
        UNIQUE KEY `combo` (`' . $this->foreign_key() . '`,`' . $second_model->foreign_key() . '`)
      ) DEFAULT CHARSET=' . DB_CHARSET . ';';
      $this->query($sql);
    }
  }
  
  function find($id){
    return array_pop($this->find_by_sql('SELECT * FROM `' . $this->_table . '` WHERE id = ' . $id));
  }
  function find_first($options = array()){
    $options = array_merge(array('where' => null, 'order' => 'id ASC', 'limit' => 1, 'offset' => 0), $options, array('limit' => 1));
    return array_pop(self::find_all($options));
  }
  function where($where){
    return $this->find_all(array('where' => $where));
  }
  function find_all($options = array()){
    $options = array_merge(array('where' => null, 'order' => 'id ASC', 'limit' => MAR_LIMIT, 'offset' => 0), $options);
    $where = $limit = '';
    $sti = (in_array('type', array_keys($this->_columns))) ? ' AND `type` = "' . $this->_class . '"' : '';
    $where = (!empty($options['where'])) ? ' WHERE 1 AND ' . $options['where'] : ' WHERE 1';
    if($options['limit'] + $options['offset'] > 0){
      $limit = ' LIMIT ' . $options['limit'] . ' OFFSET ' . $options['offset'];
    }
    $order = ' ORDER BY ' . $options['order'];
    $values = isset($options['values']) ? $options['values'] : array();
    return $this->find_by_sql('SELECT * FROM `' . $this->_table . '`' . $where . $sti . $order . $limit, $values);
  }
  function find_by_sql($sql, $values = array()){
    $fingerprint = md5($sql);
    $records = array();
    $result = $this->query($sql, $values);
    if(!!$result){
      if(array_key_exists($fingerprint, self::$_cache)){
        //print "\n\nSatisfied from cache in “" . __FUNCTION__ . "”\n\n";
        return self::$_cache[$fingerprint];
      }
      while($row = $result->fetch(PDO::FETCH_ASSOC)){
        $records[$row['id']] = $this->create($row);
      }
    }
    return self::$_cache[$fingerprint] = $records;
  }
  function query($sql, $values = array()){
    $statement = $this->_db->prepare($sql);
    $statement->execute($values);
    return $statement;
  }
  function create($arrVals = array()){
    $obj = new $this->_class($arrVals);
    return $obj;
  }
  private function save_without_callbacks(){
    $keys = $vals = $tokens = $set = array();
    foreach(array_keys($this->_columns) as $col){
      if($col != 'id'){
        if($col == 'type') $this->$col = $this->_class;
        $keys[] = "`$col`";
        $vals[] = $this->$col;
        $tokens[] = '?';
        $set[] = "`$col` = ?";
      }
    }
    $sql = ($this->persisted()) ? 'UPDATE ' : 'INSERT INTO ';
    $sql .= '`' . $this->_table . '` ';
    $sql .= ($this->persisted()) ? 'SET ' . implode(', ', $set) . ' WHERE `id` = ' . $this->id : '(' . implode(', ', $keys) . ') VALUES(' . implode(', ', $tokens) . ')';
    $statement = $this->_db->prepare($sql);
    $statement->execute($vals);
    if(!$this->persisted()){
      $this->id = $this->_db->lastInsertId();
    }
    return $this;
  }
  function save(){
    $this->before_validation();
    $this->validate();
    $this->after_validation();
    if($this->get_errors()) return false;
    $this->before_save();
    $this->save_without_callbacks();
    foreach(w($this->has_and_belongs_to_many) as $c){
      $this->update_attached($c);
    }
    foreach(w($this->has_many) as $c){
      $this->update_attached($c);
    }
    foreach(w($this->has_many_through) as $c){
      $this->update_attached($c);
    }
    foreach(w($this->belongs_to) as $c){
      $this->update_attached($c);
    }
    $this->after_save();
    return $this;
  }
  function destroy(){
    if($this->get_errors()) return false;
    $sql = 'DELETE FROM `' . $this->_table . '` WHERE `id` = ' . $this->id;
    $this->_db->query($sql);
    return $this;
  }
  function validate(){
    $this->_errors = null;
    foreach( $this->_validations as $v){
      call_user_func_array('self::validate_' . array_shift($v), $v);
    }
    if($this->get_errors()){
      return false;
    }
    return true;
  }
  private function validate_presence($key, $message = null){
    if(!$message){
      $message = Inflector::humanize($key) . ' cannot be empty';
    }
    if(!isset($this->$key) || empty($this->$key)){
      $this->add_error($key, $message);
      return false;
    }
    return true;
  }
  private function validate_regexp($key, $regexp, $message = null){
    if(!$message){
      $message = Inflector::humanize($key) . ' is not valid';
    }
    if(!preg_match($regexp, $this->$key)){
      $this->add_error($key, $message);
      return false;
    }
    return true;
  }
  private function validate_email($key = 'email', $message = 'That didn’t look like an e-mail address'){
    if(!$this->validate_presence($key)) return false;
    return $this->validate_regexp($key, '/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,8}$/i', $message);
  }
  function get_errors(){
    if(count($this->_errors) > 0){
      return $this->_errors;
    }
    return false;
  }
  function get_error($key){
    if(count($this->_errors) > 0 && isset($this->_errors[$key])){
      return $this->_errors[$key];
    }
    return false;
  }
  private function add_error($field, $error){
    $this->_errors[$field] = $error;
  }
  private function find_relationship($table){
    if(strstr($this->has_many, $table)) return 'has_many';
    if(strstr($this->has_many_through, "$table:")) return 'has_many_through';
    if(strstr($this->belongs_to, "$table:")) return 'belongs_to';
    $class = Inflector::classify($table);
    if(in_array($this->link_table(new $class()), $this->tables())) return 'has_and_belongs_to_many';
    return false;
  }
  private function update_attached($relation){
    $relationship = $this->find_relationship($relation);
    $class = Inflector::classify($relation);
    $obj = new $class();
    switch($relationship){
      case 'has_and_belongs_to_many':
        $sql = 'DELETE FROM ' . $this->link_table($obj) . ' WHERE ' . $this->foreign_key() . ' = ' . $this->id;
        $this->query($sql);
        foreach($this->$relation as $o){
          $sql = 'INSERT INTO ' . $this->link_table($obj) . ' (' . $this->foreign_key() . ', ' . $obj->foreign_key() . ') VALUES (' . $this->id . ', ' . $o->id . ')';
          $this->query($sql);
        }
        break;
      case 'has_many_through':
      case 'has_many':
        foreach($this->$relation as $o){
          $key = $this->foreign_key();
          $o->$k = $this->id;
          $o->save();
        }
        break;
      case 'belongs_to':
        $key = $obj->foreign_key();
        $this->$k = $obj->id;
        $this->save_without_callbacks();
        break;
      default:
        break;
    }
    
  }
  function update_attributes($pairs){
    foreach($pairs as $key => $val){
      $this->$key = $val;
    }
    return $this->save();
  }
  function __call($name, $arguments){
    if(substr($name, 0, 4) == 'add_'){
      $to_add = substr($name, 4);
      $key = Inflector::tableize($to_add);
      $obj = $arguments[0];
      $current = $this->$key;
      $current[$obj->id] = $obj;
      $this->$key = $current;
      if(strstr($this->has_and_belongs_to_many, $key)){
        //add the inverse
        $key = Inflector::tableize($this->_class);
        $current = $obj->$key;
        $current[$this->id] = $this;
        $obj->$key = $current;
      }
    }
    if(substr($name, 0, 7) == 'remove_'){
      $to_remove = substr($name, 7);
      $key = Inflector::tableize($to_remove);
      $obj = $arguments[0];
      $current = $this->$key;
      unset($current[$obj->id]);
      unset(self::$_cache[$key][$obj->id]);
      $this->$key = $current;
      if(strstr($this->has_and_belongs_to_many, $key)){
        //remove the inverse
        $key = Inflector::tableize($this->_class);
        $obj = $arguments[0];
        $current = $obj->$key;
        unset($current[$this->id]);
        unset(self::$_cache[$key][$this->id]);
        $obj->$key = $current;
      }
    }
    if(substr($name, 0, 8) == 'find_by_'){
      $keys = preg_split('/_and_/', substr($name, 8), -1, PREG_SPLIT_NO_EMPTY);
      $where = $options = array();
      foreach($keys as $key){
        if(in_array($key, array_keys($this->_columns))){
          $where[] = "`$key` = ?";
        }
      }
      if((count($keys) + 1) == count($arguments)){
        $options = array_pop($arguments);
      }
      $options['where'] = implode(' AND ', $where);
      $options['values'] = $arguments;
      return $this->find_all($options);
    }
  }
  function __set($name, $value){
    $this->$name = $value;
  }
  function __get($name){
    if($this->persisted() && isset(self::$_cache[$name][$this->id])){
      return self::$_cache[$name][$this->id];
    }
    if(strstr($this->belongs_to, $name)){
      $class = Inflector::classify($name);
      $obj = new $class();
      $key = $name . '_id';
      if(isset($this->$key) && ! empty($this->$key)){
        $k = $obj->find($this->$key);
        return self::$_cache[$name][$this->id] = $k;
      }
    }
    if(strstr($this->has_many, $name)){
      $class = Inflector::classify($name);
      $obj = new $class();
      $key = $this->foreign_key();
      $k = $obj->find_all(array('where' => "$key = $this->id"));
      return self::$_cache[$name][$this->id] = $k;
    }
    if(strstr($this->has_and_belongs_to_many, $name)){
      $class = Inflector::classify($name);
      $obj = new $class();
      $key = $this->foreign_key();
      $fkey = $obj->foreign_key();
      $k = $obj->find_by_sql('SELECT `' . $obj->_table . '`.* FROM `' . $obj->_table . '` INNER JOIN ' . $this->link_table($obj) . ' ON ' . $fkey . ' = `' . $obj->_table . '`.id WHERE ' . $this->link_table($obj) . '.' . $key . ' = ' . $this->id);
      return self::$_cache[$name][$this->id] = $k;
    }
    if(preg_match('/' . $name . ':(.+?)\b/', $this->has_many_through, $matches)){
      $class = Inflector::classify($name);
      $obj = new $class();
      $key = $obj->foreign_key();
      $links = $this->$matches[1];
      $out = array();
      foreach($links as $link){
        $out[$link->$key] = $obj->find($link->$key);
      }
      return self::$_cache[$name][$this->id] = $out;
    }
  }
  function reload(){
    return $this->find($this->id);
  }
  function inspect($show_empty_values = true){
    if(is_object($this)){
      print "\n\n" . $this->_class . "\n\n";
      foreach(get_object_vars($this) as $key => $val){
        if($show_empty_values || !empty($val)){
          if(substr($key, 0, 1) != '_'){
            print $key . ': ' . $val . "\n";
          }
        }
      }
    }
  }
  
  //callbacks -- extend in your subclass
  public function before_save(){
    return true;
  }
  public function after_save(){
    return true;
  }
  public function before_validation(){
    return true;
  }
  public function after_validation(){
    return true;
  }
}

/**
 * language extensions
 *
 * @author Walter Lee Davis
 */
 
/**
 * Convert a space-delimited string to an array
 *
 * @param string $str 
 * @return array
 * @author Walter Lee Davis
 */
function w($str){
  return preg_split('/\s+/', $str, -1, PREG_SPLIT_NO_EMPTY);
}
//escape illegal characters in HTML
function h($str){
  return htmlentities($str, ENT_COMPAT, MAR_CHARSET);
}
//translate ruby 1.9 hash syntax to PHP array
function a($arguments){
  //'a: foo, b: bar' => array('a' => 'foo', 'b' => 'bar')
  $ary = array();
  $pairs = preg_split('/\s*?,\s*?/', $arguments, -1, PREG_SPLIT_NO_EMPTY);
  foreach($pairs as $pair){
    $parts = preg_split('/\s*?:\s*?/', $pair, -1, PREG_SPLIT_NO_EMPTY);
    if(count($parts) > 0) $ary[trim($parts[0])] = trim($parts[1]);
  }
  return $ary;
}

?>