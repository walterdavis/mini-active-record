<?php
require_once('config.inc.php');
//include your classes here
require_once('models/Car.php');
//note the order here: Auto is an STI subclass of Car
//it MUST load after its parent
require_once('models/Auto.php');
require_once('models/Driver.php');

$auto = new Auto(a('model:T, year: 1890, color:black'));
//$auto->save();
$car = new Car();
$driver = new Driver();
// $project->create_link_table(new Role());
header('Content-type: text/plain; charset=utf-8');
// foreach($car->find_by_model('Mini') as $m){
//   
//   $m->inspect(false);
// }
// foreach($driver->find_all() as $p){
//   //$p->inspect(false);
//   print "\n" . $p->name . ":\n\n";
//   foreach($p->cars as $c){
//     //$c->inspect(false);
//     print $c->description() . "\n";
//     //$r->user->inspect();
//     //print_r($r->user->has_many());
//     //print($r->identity());
//   }
// }
$cait = $driver->find_first(a('where: name = "Caitlin"'));
$walt = $driver->find_first(a('where: name = "Walter"'));
// $jetta = $car->find_first(a('where: model = "Jetta"'));
$mini = new Car(a('model:Mini, color: red'));
// print_r($mini);
$mini->validate();
print_r($mini->get_errors());
$mini->year = 2009;
$mini->validate();
print_r($mini->save());

// print_r($mini->reload());
// $mini->model = 'Mini';
//$mini->save();
// print_r($mini);
// foreach($car->find_all(a('where: model = "Mini"')) as $mini){
//   $mini->update_attributes(a('year: 2010'));
//   $cait->add_car($mini);
//   $mini->add_driver($walt);
// print_r($mini->drivers);
// }
// foreach($car->find_all(a('where: model = "Mini", order: id DESC')) as $mini){
//   foreach($mini->drivers as $d) $d->inspect(false);
// }
// $cait->add_car($jetta);
//print_r($cait->cars);
//print_r($cait->cars);
//$ar->find_first(array('order' => 'name ASC'))->inspect();
// $toaster = $ar->create(array('name' => 'toaster', 'position' => 12));
// $toaster->inspect();
// $toaster->save();
// $toaster->inspect();
?>