#MiniActiveRecord

MiniActiveRecord is the spiritual successor to MyActiveRecord, by Jake Grimley. Like that library, it aims to provide a simple, fast ORM in PHP to implement Martin Fowler's ActiveRecord pattern. It looks longingly at Rails' implementation of the same pattern, and borrows some of its tricks. The design goal is to reduce the number of lines of code needed to get something done, rather than to provide a textbook example of pure performance-centered programming.

MiniActiveRecord requires PHP 5.1 or better, and has only been tested with 5.3. It uses PDO (PHP Data Objects) to interface with the database, so you must have compiled that module into PHP as well (it's on by default in 5.3). This means that you should be able to use databases other than just MySQL, although I have not tested this myself.

It is developed with a zero-errors policy; no function calls are silenced and error reporting is on, set to kill (well, `E_ALL`). Naturally, you should disable this preference in production.

##MIT License:

Copyright (c) 2013 Walter Lee Davis

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

##Requirements:

* PHP >= 5.1 (5.3 recommended).
* A database (MySQL or any other supported by PDO), accessible to your script.
* For each model in your application, a matching table, named as the lower-case plural of your singular model (`class Car{}` would become `cars` in the database)
* The primary key must be named `id` and must be auto-incrementing
* If you are implementing many-to-many (`has_and_belongs_to_many`) relationships, you must follow the Rails convention and name your join table `[first class name plural]_[second class name plural]`, using alphabetical order. `cars_drivers`, for example.

##Sugars:

* If you include a column named `updated_at` or `created_at`, defined as a `DATETIME`, the proper thing will happen automagically!
* To use Single Table Inheritance, add a column named `class` in the table containing the proper name of the subclass.
* Magic "getters": `$car->drivers` will return an array of **Driver** objects.
* Magic "setters": `$bob->add_car($jetta)` will add the `$jetta` **Car** object to the `$bob->cars` array, and will also connect the `$bob` **Driver** object to the `$jetta->drivers` array.
* Magic "finders": `$car->find_by_model_and_year('jetta', 2001)` or `$car->find_all_by_model('jetta')` do what you'd expect. There's also the `$car->find_or_create_by_name_and_year('Mini',2012)` goodness you didn't know you were looking for.

    
##Models

Each object class in your application will be represented by a sub-class of MiniActiveRecord. This allows you to create a rich application with only a few lines of code. When a new instance of the class is created, a reflection of the database structure is used to pre-populate that instance with default values, and to set up the "setters" and "getters" needed to persist it. Much of this is done using metaprogramming in the `__call`, `__set`, and `__get` "magic" functions, as well as the `__construct` initializer.

While working with these objects in your application, you simply assign values to their attributes, and when you're done, call `save()` on that object to persist them.

##Relationships:

MiniActiveRecord follows Rails' conventions when defining relationships between models. You declare these relationships in your models once, and then can use magic "getters" and "setters" to read and populate them. The following relationships are supported:

* `has_many` This is the parent of many `belongs_to` children. No changes are needed to the table to support this relationship.
* `belongs_to` This is the child of a `has_many` parent. There must be a column named `[parent class singular]_id` in this object's model table.
* `has_and_belongs_to_many` Both sides of a many-to-many relationship will declare this. The join table must also exist, as noted above. If the application has sufficient database privileges, there is a helper function named `create_link_table()` which will create the properly-named join table.
* `has_many_through` This is a "smart join", which allows the join table to carry additional relationship information. For example, a **Membership** might be the connection between a **Person** and a **Club**. This would allow a person to be president of one club, and treasurer of another. The join model will `belong_to` all models that join through it, these models will each `has_many` join models, and `has_many_through` that join model to the child model.

These relationships are maintained automatically as long as you use the setter and getter functions provided. When you save a parent record, all associated children are saved as well.

##Validations:

Each model has basic validations built into it, and you can extend your own models with custom validations that will run before an object is saved (or at any time with the `validate()` function). The following core validations are provided:

* `validate_presence($attribute[, $error_message])` This tests for the presence (`isset()`) AND the non-emptiness of the attribute. The default message is "Attribute cannot be blank". If your object may have a value of `0`, you may need to write your own validator to test that value more explicitly.
* `validate_regexp($attribute, $regexp[, $error_message])` This tests for a positive match between your attribute and the regexp provided. (You must include the delimiters in your regexp, no assumptions are made.) The default message is "Attribute is not valid".
* `validate_email([$attribute, $error_message])` This is a combination of the two foregoing validations, combining a test for presence with a test for matching a simple e-mail regexp. If nothing is entered, the default message from `validate_presence` is used. If the regexp doesn't match, then the default message is "That didn't look like an e-mail address", which you can change by passing a different message to this function.

Validations are defined using the following DSL: 

    $validations = 'function:attribute:argument; function:attribute:argument[:argument]';
    //for example:
    $validations = 'presence:name; email:email; regexp:phone:/\(?\d{3}\)?[\-\s]\d{3}\-\d{4}/';
    
Note that this DSL uses a semicolon- and colon-delimited string (like CSS). If you have a need for these characters in your arguments, you may escape them with a single backslash. If you have more complex needs, you may also define the `$validations` array as a regular array, and it will not be parsed at all. 

    $validations = array(
      array('presence', 'name', 'Hey! What\'s your name?'),
      array('email', 'email'),
      array('regexp', 'phone', '/\(?\d{3}\)?[\-\s]\d{3}\-\d{4}/', 'Need your phone number, bub')
    );

These validations are compiled at construction time, and called in order by the `validate()` callback. You may add your own callbacks following this pattern:

    class Foo extends MiniActiveRecord{
      $validations = 'bar:wibble';
      // $this->wibble must equal zero
      private function validate_bar($key, $message = 'Baz'){
        if(0 !== $key){
          $this->add_error($key, $message);
          return false;
        }
        return true;
      }
    }

At any point, a record may be inspected for errors with the `get_errors()` function. The result will only be accurate if validations have been performed, so if you are calling it outside of the normal save loop, you should call `validate()` on your object first.

##Callbacks:

The `save()` function calls a set of callbacks as it executes. These are:

1. `before_validation()` A user-defined function that can optionally modify the object, and must return something truthy.
2. `validate()` A hook that runs all defined validations on the object, and returns true or false (false will stop the save at this point).
3. `after_validation()` A user-defined function that can optionally modify the object (but should leave it in a valid state to avoid future unpleasantness with the database).
4. `before_save()` (or `before_create()` if the object is new) A last user-defined function before the actual save back to the database. This function runs after the validations, and it can halt the save process.
5. `save_without_callbacks()` Persists the object to the database. Only does so if the object is marked as `_dirty` (having unsaved changes).
6. `update_associations()` Automatically persist all associated records.
7. `after_save()` (or `after_create()` if the object is new) A user-defined function that runs after the save. Must mark the object as `_dirty` to update any changed values.

##Example:

    <?php
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
    define('MAR_DSN', 'mysql://username:password@hostname/database');
    define('MAR_LIMIT', 10000);
    define('MAR_CHARSET', 'UTF-8');
    define('DB_CHARSET', 'utf8');
    date_default_timezone_set('UTC');
    //this is the inflector from CakePHP
    require_once('lib/Inflector.php');
    //you may also use this one instead
    //require_once('lib/MiniInflector.php');
    require_once('lib/MiniActiveRecord.php');
    
    class Car extends MiniActiveRecord{
      public $validations = 'presence:model; regexp:year:/\d{4}/; presence:year';
      public $has_and_belongs_to_many = 'drivers';
      function description(){
        return implode(' ', array($this->year, $this->color, $this->model));
      }
    }
    class Driver extends MiniActiveRecord{
      public $validations = 'presence:name';
      public $has_and_belongs_to_many = 'cars';
    }
    //create some empty instances to work with
    $car = new Car();
    $driver = new Driver();
    $mini = $car->build(a('model:Mini, year:2012, color:red'));
    //#build creates the object in memory, but doesn't persist it
    $mini->save();
    $walt = $driver->create(a('name: Walter'));
    //#create saves the object directly
    $mini->add_driver($walt);
    //$mini->drivers is now an array containing $walt
    $mini->save();
    //this relationship is saved to the database
    ?>
