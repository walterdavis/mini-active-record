<?php
if(!class_exists('Inflector')){
  //VERY lightweight substitute for the mighty CakePHP inflector.
  //WARNING! When it comes to pluralize and singularize, unless you define a proper 
  //irregular, all it does is add/remove an “s” to a word.
  //All other functions are exactly the same as the ones in Cake.
  class Inflector{
    //edit this array to make it smarter
    static $irregulars = array(
      'person' => 'people',
      'matrix' => 'matrices'
      );
    static $_cache = array();
    static function _cache($type, $key, $value = false) {
      $key = '_' . $key;
      $type = '_' . $type;
      if ($value !== false) {
        Inflector::$_cache[$type][$key] = $value;
        return $value;
      }
      if (!isset(Inflector::$_cache[$type][$key])) {
        return false;
      }
      return Inflector::$_cache[$type][$key];
    }

    function pluralize($strSingular){
      if($result = Inflector::_cache(__FUNCTION__, $strSingular)){
        return $result;
      }
      if(array_key_exists($strSingular, Inflector::$irregulars)){
        return Inflector::_cache(__FUNCTION__, $strSingular, Inflector::$irregulars[$strSingular]);
      }
      return Inflector::_cache(__FUNCTION__, $strSingular, $strSingular . 's');
    }
    function singularize($strPlural){
      if($result = Inflector::_cache(__FUNCTION__, $strPlural)){
        return $result;
      }
      if(false != array_search($strPlural, Inflector::$irregulars)){
        return Inflector::_cache(__FUNCTION__, $strPlural, array_search($strPlural, Inflector::$irregulars));
      }
      return Inflector::_cache(__FUNCTION__, $strPlural, substr($strPlural, 0, -1));
    }
    function camelize($lowerCaseAndUnderscoredWord){
      if($result = Inflector::_cache(__FUNCTION__, $lowerCaseAndUnderscoredWord)){
        return $result;
      }
      return Inflector::_cache(__FUNCTION__, $lowerCaseAndUnderscoredWord, str_replace(' ', '', Inflector::humanize($lowerCaseAndUnderscoredWord)));
    }
    function underscore($strCamelCase){
      if($result = Inflector::_cache(__FUNCTION__, $strCamelCase)){
        return $result;
      }
      return Inflector::_cache(__FUNCTION__, $strCamelCase, strtolower(preg_replace('/(?<=\\w)([A-Z])/', '_\\1', $strCamelCase)));
    }
    function tableize($strModel){
      if($result = Inflector::_cache(__FUNCTION__, $strModel)){
        return $result;
      }
      return Inflector::_cache(__FUNCTION__, $strModel, Inflector::pluralize(Inflector::underscore($strModel)));
    }
    function classify($strTable){
      if($result = Inflector::_cache(__FUNCTION__, $strTable)){
        return $result;
      }
      return Inflector::_cache(__FUNCTION__, $strTable, Inflector::camelize(Inflector::singularize($strTable)));
    }
    function humanize($lowerCaseAndUnderscoredWord){
      if($result = Inflector::_cache(__FUNCTION__, $lowerCaseAndUnderscoredWord)){
        return $result;
      }
      return Inflector::_cache(__FUNCTION__, $lowerCaseAndUnderscoredWord, ucwords(str_replace('_', ' ', $lowerCaseAndUnderscoredWord)));
    }
  }
}
?>