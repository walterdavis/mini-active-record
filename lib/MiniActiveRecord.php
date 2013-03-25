<?php
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
 * MiniActiveRecord is ActiveRecord in PHP 5.3+, in < 1,000 LOC
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
  public $_dirty = true;
  private static $_cache = array();
  private static $_db;
  public static $_table;
  private static $_class;
  private static $_columns;
  private static $_column_names;
  private static $_validations = array();
  private $_errors;
  
  /**
   * Auto-construct a new MAR object
   *
   * @param array $params (optional, to pre-populate the object)
   * @author Walter Lee Davis
   */
  function __construct($params = array()){
    self::initialize();
    $this->populate($params, true);
  }
  /**
   * Build a new MAR object with database reflection
   *
   * @return void
   * @author Walter Lee Davis
   */
  private function initialize(){
    try{
      $this->connection();
      if(empty($this->_class)) $this->_class = get_class($this);
      if(empty($this->_table)) $this->_table = $this->table();
      if(empty($this->_columns)) $this->_columns = $this->columns();
      if(empty($this->_column_names)) $this->_column_names = array_keys($this->columns());
      if(empty($this->_validations)) $this->_validations = $this->validations();
      foreach($this->_columns as $key => $col){
        $this->$key = $col['Default'];
      }
    }catch(MARException $e){
      print $e->getMessage();
    }
  }
  
  /**
   * singleton constructor for database connection
   *
   * @return PDO instance $_db set on this
   * @author Walter Lee Davis
   */
  private function connection(){
    if(is_object($this->_db)) return $this->_db;
    $params = parse_url(MAR_DSN);
    $dsn = sprintf('%s:host=%s;dbname=%s', $params['scheme'], $params['host'], str_replace('/','',$params['path']));
    $this->_db = new PDO($dsn, $params['user'],$params['pass'], array(
        PDO::ATTR_PERSISTENT => true
    ));
    $this->_db->exec("SET NAMES '" . DB_CHARSET . "';");
    $this->_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
  }
  
  /**
   * get the databse table for this object
   *
   * @return string actual table name (even in STI)
   * @author Walter Lee Davis
   */
  private function table(){
    $table = Inflector::tableize($this->_class);
    $tables = self::tables();
    $class = $this->_class;
    while(!array_search($table, $tables)){
      $class = get_parent_class($class);
      if($class == 'MiniActiveRecord') break;
      $table = Inflector::tableize($class);
    }
    return $table;
  }
  
  /**
   * is this object new (no id)?
   *
   * @return boolean
   * @author Walter Lee Davis
   */
  private function persisted(){
    return (isset($this->id) && $this->id > 0);
  }
  
  /**
   * singleton get all tables (and their structure) from database
   *
   * @return array
   * @author Walter Lee Davis
   */
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
  
  /**
   * get all column names from an object
   *
   * @return array
   * @author Walter Lee Davis
   */
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
  
  /**
   * update an object from an array
   *
   * @param array $params 
   * @param boolean $include_id 
   * @return object $this
   * @author Walter Lee Davis
   */
  public function populate($params = array(), $include_id = false){
    foreach($params as $key => $val){
      if($key != 'id' || $include_id) $this->$key = $val;
    }
    return $this;
  }
  
  /**
   * gets a nested array of all children of this object
   *
   * @return array
   * @author Walter Lee Davis
   */
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
  
  /**
   * gets a nested array of all children of this object
   *
   * @return array
   * @author Walter Lee Davis
   */
  private function has_many_through(){
    $has_many_through = array();
    foreach(w($this->has_many_through) as $c){
      $c = explode(':', $c);
      $link_obj = $this->$c[1];
      foreach($link_obj as $lo){
        $key = Inflector::singularize($c[0]);
        $obj = $lo->$key;
        $has_many_through[$c[0]][$obj->id] = $obj;
      }
    }
    return $has_many_through;
  }
  
  /**
   * gets a nested array of many-to-many relatives
   *
   * @return array
   * @author Walter Lee Davis
   */
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
  
  /**
   * gets a nested array of parent objects
   *
   * @return array
   * @author Walter Lee Davis
   */
  private function belongs_to(){
    $belongs_to = array();
    foreach(w($this->belongs_to) as $c){
      $obj = $this->$c;
      $belongs_to[$c][$obj->id] = $obj;
    }
    return $belongs_to;
  }

  /**
   * gets an array of validations and their arguments, ready to run in validate()
   *
   * @return array
   * @author Walter Lee Davis
   */
  private function validations(){
    $rules = $this->validations;
    if(is_string($rules)){
      $validations = array();
      $rules = preg_split('/(?<!\\\);\s*/', $rules, -1, PREG_SPLIT_NO_EMPTY);
      foreach($rules as $v){
        $v = str_replace('\;',';',$v);
        $v = preg_split('/(?<!\\\):\s*/', $v, -1, PREG_SPLIT_NO_EMPTY);
        $validations[] = $v;
      }
      return $validations;
    }
    return $this->validations();
  }
  
  /**
   * gets a globally unique id to the current object (not used as of yet)
   *
   * @return string
   * @author Walter Lee Davis
   */
  function identity(){
    return md5(get_class($this) . $this->id);
  }
  
  /**
   * gets the link table between the current object and the second object
   * used in HABTM relationships
   *
   * @param object $second_model in HABT relationship with $this
   * @return string
   * @author Walter Lee Davis
   */
  function link_table($second_model){
    $names = array($this->_table, $second_model->_table);
    sort($names);
    return implode('_', $names);
  }
  
  /**
   * get the proper foreign key for this object
   *
   * @return string
   * @author Walter Lee Davis
   */
  function foreign_key(){
    return Inflector::singularize($this->_table) . '_id';
  }
  
  /**
   * create a link table between the current object and the second model
   *
   * @param object $second_model 
   * @return void
   * @author Walter Lee Davis
   */
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
  
  /**
   * return a date formatted for the database
   * @static
   * @param  int time_stamp  A unix timestamp
   * @return string  mysql format date string
   */
  private function db_date($time_stamp=null){
   return date('Y-m-d', $time_stamp ? $time_stamp : mktime() );
  }
  
  /**
   * return a datetime formatted for the database
   * @static
   * @param  int time_stamp  A unix timestamp
   * @return string  mysql format datetime string
   */  
  private function db_datetime($time_stamp=null){
   return date('Y-m-d H:i:s', $time_stamp ? $time_stamp:mktime() );
  }
  
  /**
   * set any magic timestamp columns: created_at and updated_at
   *
   * @return void
   * @author Walter Lee Davis
   */
  private function update_timestamps(){
    if(in_array('created_at', $this->_column_names)) $this->created_at = $this->db_datetime($this->time_stamp($this->created_at));
    if(in_array('updated_at', $this->_column_names)) $this->updated_at = $this->db_datetime();
  }
  
  /**
   * return a unix timestamp from a database formatted date
   * @static
   * @param  string  mysql datetime
   * @return int unix timestamp  
   */
  function time_stamp($db_date_stamp){
   return strtotime($db_date_stamp,time());
  }
  
  /**
   * find by ID (or array of IDs)
   *
   * @param mixed $id integer or array of integers
   * @return single object or array of objects
   * @author Walter Lee Davis
   */
  function find($id){
    if(is_array($id)){
      $keys = array_fill(0, count($id), '?');
      return $this->find_by_sql('SELECT * FROM `' . $this->_table . '` WHERE id IN(' . implode(',', $keys) . ')', $id);
    }
    return array_pop($this->find_by_sql('SELECT * FROM `' . $this->_table . '` WHERE id = ' . intval($id)));
  }
  
  /**
   * find the first object matching the $options array
   *
   * @param array $options 
   * @return object or false
   * @author Walter Lee Davis
   */
  function find_first($options = array()){
    $options = array_merge(array('where' => null, 'order' => 'id ASC', 'limit' => 1, 'offset' => 0), $options, array('limit' => 1));
    if($obj = array_pop(self::find_all($options))) return $obj;
    return false;
  }
  
  /**
   * find all objects matching the options array
   *
   * @param array $options 
   * @return array
   * @author Walter Lee Davis
   */
  function find_all($options = array()){
    $options = array_merge(array('where' => null, 'order' => 'id ASC', 'limit' => MAR_LIMIT, 'offset' => 0), $options);
    $where = $limit = '';
    $sti = (in_array('type', $this->_column_names)) ? ' AND `type` = "' . $this->_class . '"' : '';
    $where = (!empty($options['where'])) ? ' WHERE 1 AND ' . $options['where'] : ' WHERE 1';
    if($options['limit'] + $options['offset'] > 0){
      $limit = ' LIMIT ' . $options['limit'] . ' OFFSET ' . $options['offset'];
    }
    $order = ' ORDER BY ' . $options['order'];
    $values = isset($options['values']) ? $options['values'] : array();
    return $this->find_by_sql('SELECT * FROM `' . $this->_table . '`' . $where . $sti . $order . $limit, $values);
  }
  
  /**
   * count the objects that match the parameters (or all of them)
   *
   * @param array $options 
   * @return integer
   * @author Walter Lee Davis
   */
  function count($options = array()){
    $options = array_merge(array('where' => null, 'order' => 'id ASC', 'limit' => MAR_LIMIT, 'offset' => 0), $options);
    $where = $limit = '';
    $sti = (in_array('type', $this->_column_names)) ? ' AND `type` = "' . $this->_class . '"' : '';
    $where = (!empty($options['where'])) ? ' WHERE 1 AND ' . $options['where'] : ' WHERE 1';
    if($options['limit'] + $options['offset'] > 0){
      $limit = ' LIMIT ' . $options['limit'] . ' OFFSET ' . $options['offset'];
    }
    $values = isset($options['values']) ? $options['values'] : array();
    $result = $this->query('SELECT COUNT(*) AS _count FROM `' . $this->_table . '`' . $where . $sti . $limit, $values);
    if(!!$result){
      if($row = $result->fetch(PDO::FETCH_ASSOC))
        return $row['_count'];
    }
    return 0;
  }

  /**
   * find objects matching a sql query
   * the query should have placeholders for the array of values for best effect
   *
   * @param string $sql query with placeholders
   * @param array $values to replace the placeholders
   * @return array
   * @author Walter Lee Davis
   */
  function find_by_sql($sql, $values = array()){
    $fingerprint = md5($sql);
    $records = array();
    $result = $this->query($sql, $values);
    if(!!$result){
      if(array_key_exists($fingerprint, self::$_cache)){
        return self::$_cache[$fingerprint];
      }
      while($row = $result->fetch(PDO::FETCH_ASSOC)){
        $records[$row['id']] = $this->build($row);
        $records[$row['id']]->_dirty = false;;
      }
    }
    return self::$_cache[$fingerprint] = $records;
  }
  
  /**
   * run a query, replacing placeholders
   *
   * @param string $sql 
   * @param array $values 
   * @return PDO statement
   * @author Walter Lee Davis
   */
  function query($sql, $values = array()){
    $statement = $this->_db->prepare($sql);
    $statement->execute($values);
    // print($statement->queryString . ': (' . implode(', ', $values) . ")\n");
    // $e = new Exception;
    // print_r($e->getTraceAsString());
    return $statement;
  }
  
  /**
   * instantiate an object without saving it
   *
   * @param array $options 
   * @return object
   * @author Walter Lee Davis
   */
  function build($options = array()){
    $obj = new $this->_class($options);
    $obj->_dirty = true;
    return $obj;
  }
  
  /**
   * build and save an object and return it
   *
   * @param array $options 
   * @return object
   * @author Walter Lee Davis
   */
  function create($options=array()){
    $obj = self::build($options);
    return $obj->save();
  }
  
  /**
   * save an object without calling any validations or callbacks
   *
   * @return object
   * @author Walter Lee Davis
   */
  private function save_without_callbacks(){
    $keys = $vals = $tokens = $set = array();
    foreach($this->_column_names as $col){
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
    $this->query($sql, $vals);
    if(!$this->persisted()){
      $this->id = $this->_db->lastInsertId();
    }
    return $this;
  }
  
  /**
   * run all callbacks and save the object
   *
   * @return object
   * @author Walter Lee Davis
   */
  function save(){
    $this->before_validation();
    $this->validate();
    $this->after_validation();
    if($this->get_errors()) return;
    if($this->persisted()){
      if(false === $this->before_save()) return;
    }else{
      if(false === $this->before_create()) return;
    }
    $this->update_timestamps();
    $this->save_without_callbacks();
    $this->update_associations();
    $this->_dirty = false;
    $this->after_save();
    return $this;
  }
  
  /**
   * destroy an object
   *
   * @return object
   * @author Walter Lee Davis
   */
  function destroy(){
    if($this->get_errors()) return false;
    if(false === $this->before_destroy()) return false;
    $sql = 'DELETE FROM `' . $this->_table . '` WHERE `id` = ' . $this->id;
    $this->_db->query($sql);
    return $this;
  }
  
  /**
   * run all registered validations
   *
   * @return void
   * @author Walter Lee Davis
   */
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
  
  /**
   * tests for presence of a column in the object
   *
   * @param string $key column
   * @param string $message optional
   * @return boolean
   * @author Walter Lee Davis
   */
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
  
  /**
   * tests for a match to a regexp on a column
   *
   * @param string $key column
   * @param string $regexp should match, include delimiters and modifiers
   * @param string $message optional
   * @return boolean
   * @author Walter Lee Davis
   */
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
  
  /**
   * combination of presence and regexp matching an email
   *
   * @param string $key column
   * @param string $message optional
   * @return boolean
   * @author Walter Lee Davis
   */
  private function validate_email($key = 'email', $message = 'That didn’t look like an e-mail address'){
    if(!$this->validate_presence($key)) return false;
    return $this->validate_regexp($key, '/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,8}$/i', $message);
  }
  
  /**
   * gather all errors from the current object
   * DOES NOT call validate(), do that first
   *
   * @return array or false
   * @author Walter Lee Davis
   */
  function get_errors(){
    if(count($this->_errors) > 0){
      return $this->_errors;
    }
    return false;
  }
  
  /**
   * get an error for a single column
   *
   * @param string $key column
   * @return string error message
   * @author Walter Lee Davis
   */
  function get_error($key){
    if(count($this->_errors) > 0 && isset($this->_errors[$key])){
      return $this->_errors[$key];
    }
    return false;
  }
  
  /**
   * add an error to a column
   *
   * @param string $field column
   * @param string $error error message
   * @return void
   * @author Walter Lee Davis
   */
  private function add_error($field, $error){
    $this->_errors[$field] = $error;
  }
  
  /**
   * find relationship between this object and another
   *
   * @param string $table foreign object table name
   * @return string relationship name
   * @author Walter Lee Davis
   */
  private function find_relationship($table){
    if(preg_match('/\b' . $table . '\b/', $this->has_many)) return 'has_many';
    if(preg_match('/\b' . $table . ':/', $this->has_many_through)) return 'has_many_through';
    if(preg_match('/\b' . $table . '\b/', $this->belongs_to)) return 'belongs_to';
    $class = Inflector::classify($table);
    if(in_array($this->link_table(new $class()), $this->tables())) return 'has_and_belongs_to_many';
    return false;
  }
  
  /**
   * save all related records
   *
   * @return void
   * @author Walter Lee Davis
   */
  private function update_associations(){
    $relations = array_merge($this->belongs_to(), $this->has_many(), $this->has_many_through(), $this->has_and_belongs_to_many());
    foreach($relations as $relation => $object){
      if(isset($this->$relation)){
        $relationship = $this->find_relationship($relation);
        $class = Inflector::classify($relation);
        $obj = new $class();
        switch($relationship){
          case 'has_and_belongs_to_many':
            $related = $this->$relation;
            $sql = 'DELETE FROM ' . $this->link_table($obj) . ' WHERE ' . $this->foreign_key() . ' = ' . intval($this->id);
            $this->query($sql);
            foreach($related as $k => $o){
              if($o->_dirty){
                $related[$k] = $o->save();
              }else{
                $sql = 'INSERT INTO ' . $this->link_table($obj) . ' (' . $this->foreign_key() . ', ' . $obj->foreign_key() . ') VALUES (' . intval($this->id) . ', ' . intval($o->id) . ')';
                $this->query($sql);
              }
            }
            break;
          case 'has_many_through':
            foreach($this->$relation as $r){
              if($r->_dirty) $r->save();
            }
            break;
          case 'has_many':
            foreach($this->$relation as $r){
              if($r->_dirty) $r->save();
            }
            $ids = Inflector::singularize($relation) . '_ids';
            $sql = 'UPDATE `' . $relation . '` SET ' . $this->foreign_key() . ' = NULL WHERE ' . $this->foreign_key() . ' = ' . intval($this->id);
            $this->query($sql);
            $sql = 'UPDATE `' . $relation . '` SET ' . $this->foreign_key() . ' = ' . $this->id . ' WHERE id IN(' . implode(',', $this->$ids) . ')';
            $this->query($sql);
            break;
          case 'belongs_to':
            $key = $obj->foreign_key();
            if($obj->_dirty) $obj->save();
            if($this->_dirty || $this->$key != $obj->id){
              $this->$key = $obj->id;
              $this->update_timestmaps();
              $this->save_without_callbacks();
            }
            break;
          default:
            break;
        }
      }
    }
  }
  
  /**
   * update a bunch of columns on the object
   *
   * @param array $pairs key => val pairs of columns and new values
   * @return object
   * @author Walter Lee Davis
   */
  function update_attributes($pairs){
    foreach($pairs as $key => $val){
      $this->$key = $val;
    }
    return $this->save_without_callbacks();
  }
  
  /**
   * magic setters -- called when a function doesn't exist
   *
   * @param string $name function called
   * @param array $arguments 
   * @return void updates $this
   * @author Walter Lee Davis
   */
  function __call($name, $arguments){
    if(substr($name, 0, 4) == 'add_'){
      $to_add = substr($name, 4);
      $key = Inflector::tableize($to_add);
      $obj = $arguments[0];
      if(!$obj->persisted()) $obj->save();
      if(preg_match('/' . $key . ':(.+?)\b/', $this->has_many_through, $matches)){
        //add the join object
        $key = $matches[1];
        $current = $this->$key;
        $join_obj = Inflector::classify($key);
        $join_obj = new $join_obj();
        $tfk = $this->foreign_key();
        $ofk = $obj->foreign_key();
        $jo = $join_obj->create(array($tfk => $this->id, $ofk => $obj->id));
        $current[$jo->id] = $jo;
        $this->$key = $current;
        return self::$_cache[$key][$this->id] = $current;
      }
      $current = $this->$key;
      $current[$obj->id] = $obj;
      $this->$key = $current;
      self::$_cache[$key][$this->id] = $current;
      if(preg_match('/\b' . $key . '\b/', $this->has_and_belongs_to_many)){
        //add the inverse
        $key = Inflector::tableize($this->_class);
        $current = $obj->$key;
        $current[$this->id] = $this;
        $obj->$key = $current;
        self::$_cache[$key][$obj->id] = $current;
      }
    }
    if(substr($name, 0, 7) == 'remove_'){
      $to_remove = substr($name, 7);
      $key = Inflector::tableize($to_remove);
      $obj = $arguments[0];
      $current = $this->$key;
      unset($current[$obj->id]);
      self::$_cache[$key][$this->id] = $current;
      $this->$key = $current;
      if(preg_match('/' . $key . ':(.+?)\b/', $this->has_many_through, $matches)){
        //remove the join object
        $key = $matches[1];
        $current = $this->$key;
        $current[$obj->id]->destroy();
        unset($current[$obj->id]);
        $this->$key = $current;
        self::$_cache[$key][$this->id] = $current;
      }
      if(preg_match('/\b' . $key . '\b/', $this->has_and_belongs_to_many)){
        //remove the inverse
        $key = Inflector::tableize($this->_class);
        $obj = $arguments[0];
        $current = $obj->$key;
        unset($current[$this->id]);
        self::$_cache[$key][$obj->id] = $current;
        $obj->$key = $current;
      }
    }
    if(substr($name, 0, 8) == 'find_by_'){
      $keys = preg_split('/_and_/', substr($name, 8), -1, PREG_SPLIT_NO_EMPTY);
      $where = $options = array();
      foreach($keys as $key){
        if(in_array($key, $this->_column_names)){
          $where[] = "`$key` = ?";
        }
      }
      if((count($keys) + 1) == count($arguments)){
        $options = array_pop($arguments);
      }
      $options['where'] = implode(' AND ', $where);
      $options['values'] = $arguments;
      return $this->find_first($options);
    }
    if(substr($name, 0, 12) == 'find_all_by_'){
      $keys = preg_split('/_and_/', substr($name, 12), -1, PREG_SPLIT_NO_EMPTY);
      $where = $options = array();
      foreach($keys as $key){
        if(in_array($key, $this->_column_names)){
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
    if(substr($name, 0, 18) == 'find_or_create_by_'){
      $keys = preg_split('/_and_/', substr($name, 18), -1, PREG_SPLIT_NO_EMPTY);
      $where = $options = $params = array();
      foreach($keys as $key){
        if(in_array($key, $this->_column_names)){
          $where[] = "`$key` = ?";
        }
      }
      if((count($keys) + 1) == count($arguments)){
        $options = array_pop($arguments);
      }
      $options['where'] = implode(' AND ', $where);
      $options['values'] = $arguments;
      foreach($arguments as $k => $v){
        $params[$keys[$k]] = $v;
      }
      if($match = $this->find_first($options)) return $match;
      return $this->create($params);
    }
  }
  
  /**
   * public setter to work around private variables
   *
   * @param string $name 
   * @param string $value 
   * @return void
   * @author Walter Lee Davis
   */
  function __set($name, $value){
    $this->$name = $value;
  }
  
  /**
   * magic getter -- called when a property doesn't exist
   *
   * @param string $name property requested
   * @return string requested property
   * @author Walter Lee Davis
   */
  function __get($name){
    if($this->persisted() && isset(self::$_cache[$name][$this->id])){
      return self::$_cache[$name][$this->id];
    }
    if(preg_match('/\b' . $name . '\b/', $this->belongs_to)){
      $class = Inflector::classify($name);
      $obj = new $class();
      $key = $name . '_id';
      if(isset($this->$key) && ! empty($this->$key)){
        $k = $obj->find($this->$key);
        return self::$_cache[$name][$this->id] = $k;
      }
    }
    if(preg_match('/\b' . $name . '\b/', $this->has_many)){
      if(!$this->persisted()) return;
      $class = Inflector::classify($name);
      $obj = new $class();
      $key = $this->foreign_key();
      $k = $obj->find_all(array('where' => "$key = $this->id"));
      return self::$_cache[$name][$this->id] = $k;
    }
    if(substr($name, -4) == '_ids'){
      $child_association = Inflector::tableize(substr($name, 0, -4));
      $objs = $this->$child_association;
      $k = pluck('id', $objs);
      return self::$_cache[$name][$this->id] = $k;
    }
    if(preg_match('/\b' . $name . '\b/', $this->has_and_belongs_to_many) && $this->persisted()){
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
  
  /**
   * force a lookup from the database
   *
   * @return void
   * @author Walter Lee Davis
   */
  function reload(){
    if($this->persisted())
    return $this->find($this->id);
  }
  
  /**
   * pretty-print the object
   *
   * @param boolean $show_empty_values
   * @return stdout
   * @author Walter Lee Davis
   */
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
  //these are called automatically in save and destroy
  public function before_save(){
    return $this;
  }
  public function before_create(){
    return $this;
  }
  public function after_save(){
    $this->save_without_callbacks();
    return $this;
  }
  public function after_create(){
    $this->save_without_callbacks();
    return $this;
  }
  public function before_validation(){
    return $this;
  }
  public function after_validation(){
    return $this;
  }
  public function before_destroy(){
    return $this;
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
//extract one key (column) from an array of objects
function pluck($key, $array_of_objects){
  $out = array();
  foreach((array) $array_of_objects as $obj){
    $out[] = $obj->$key;
  }
  return $out;
}
?>