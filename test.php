<?php
require_once('config.inc.php');
//include your classes here
require_once('models/Car.php');
require_once('models/Dent.php');
//note the order here: Auto is an STI subclass of Car
//it MUST load after its parent
require_once('models/Driver.php');
require_once('models/Accident.php');
header('Content-type: text/plain; charset=utf-8');
$car = new Car();
$dent = new Dent();
$driver = new Driver();
// $accident = new Accident();
// $jetta = $car->find_first();
// // $crunch = $dent->create(a('name:crunch'));
// $jetta->add_dent($dent->create(a('name:scrape')));
// $jetta->save();
// $project->create_link_table(new Role());
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
$lisa = $driver->find_first(a('where: name = "Lisa"'));
$bob = $driver->build(a('name:Bob'));
// print_r($bob);
// $jetta = $car->find_first();
// $jetta->add_driver($bob);
// $jetta->save();
foreach($car->find_all() as $c) {
  // $c->add_driver($walt);
  //   $c->add_driver($cait);
  //   $c->add_driver($lisa);
  //   $c->save();
    print $c->description() . "\n";
  // foreach($c->dents as $d){
  //   print_r( $d ) . "\n";
  //   print_r($accident->find_by_dent_id_and_car_id($d->id, $c->id));
  // }
  // print_r($c->drivers);
  foreach($c->drivers as $d) print $d->name . "\n";
}
// $jetta = $car->find_first(a('where: model = "Jetta"'));
// $mini = $car->find_first(a('where:model="Mini" AND color="red"'));
// print_r($mini);
// $mini->validate();
// print_r($mini->get_errors());
// $mini->year = 2010;
// $mini->validate();
// $mini->add_driver($walt);
// $mini->add_driver($cait);
//foreach($mini->drivers as $driver) print $driver->name . "\n";
// $mini->save();
// $mini->add_dent(new Dent(a('name:scratch')));
// $mini->save();
// foreach($auto->find_all() as $c) $c->save();
// foreach($mini->dents as $dent) print $dent->car->inspect();

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