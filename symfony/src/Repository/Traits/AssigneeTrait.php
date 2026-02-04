<?php namespace App\Repository\Traits;

trait AssigneeTrait
{
    public function attributeParameterAssignee(object $entity, array $params): void
    {
        foreach ($params as $key => $value) {
            $setter = 'set' . str_replace('_', '', ucwords($key, '_'));

            if (method_exists($entity, $setter)) $entity->$setter($value);
        }
    }
}

// test the implementation 50 atributes for 10K times
// result:
// Time for attributeParameterAssignee1: 0.10808491706848 seconds
// Time for attributeParameterAssignee2: 0.1431770324707 seconds // this is current implementation
//
// test the implementation 50 atributes for 100k times
// result:
// Time for attributeParameterAssignee1: 1.377995967865 seconds
// Time for attributeParameterAssignee2: 1.7270948886871 seconds // this is current implementation
//
// test the implementation 50 atributes for 1M times
// result:
// Time for attributeParameterAssignee1: 10.827730894089 seconds
// Time for attributeParameterAssignee2: 14.921622037888 seconds // this is current implementation
//
// 40% slower
//
//
// <?php
// class Entity {
//     private $itm_img;
//     private $property1;
//     private $property2;
//     private $property3;
//     private $property4;
//     private $property5;
//     private $property6;
//     private $property7;
//     private $property8;
//     private $property9;
//     private $property10;
//     private $property11;
//     private $property12;
//     private $property13;
//     private $property14;
//     private $property15;
//     private $property16;
//     private $property17;
//     private $property18;
//     private $property19;
//     private $property20;
//     private $property21;
//     private $property22;
//     private $property23;
//     private $property24;
//     private $property25;
//     private $property26;
//     private $property27;
//     private $property28;
//     private $property29;
//     private $property30;
//     private $property31;
//     private $property32;
//     private $property33;
//     private $property34;
//     private $property35;
//     private $property36;
//     private $property37;
//     private $property38;
//     private $property39;
//     private $property40;
//     private $property41;
//     private $property42;
//     private $property43;
//     private $property44;
//     private $property45;
//     private $property46;
//     private $property47;
//     private $property48;
//     private $property49;
//     private $property50;


//     public function setItmImg($value) { $this->itm_img = $value; }
//     public function setProperty1($value) { $this->property1 = $value; }
//     public function setProperty2($value) { $this->property2 = $value; }
//     public function setProperty3($value) { $this->property3 = $value; }
//     public function setProperty4($value) { $this->property4 = $value; }
//     public function setProperty5($value) { $this->property5 = $value; }
//     public function setProperty6($value) { $this->property6 = $value; }
//     public function setProperty7($value) { $this->property7 = $value; }
//     public function setProperty8($value) { $this->property8 = $value; }
//     public function setProperty9($value) { $this->property9 = $value; }
//     public function setProperty10($value) { $this->property10 = $value; }
//     public function setProperty11($value) { $this->property11 = $value; }
//     public function setProperty12($value) { $this->property12 = $value; }
//     public function setProperty13($value) { $this->property13 = $value; }
//     public function setProperty14($value) { $this->property14 = $value; }
//     public function setProperty15($value) { $this->property15 = $value; }
//     public function setProperty16($value) { $this->property16 = $value; }
//     public function setProperty17($value) { $this->property17 = $value; }
//     public function setProperty18($value) { $this->property18 = $value; }
//     public function setProperty19($value) { $this->property19 = $value; }
//     public function setProperty20($value) { $this->property20 = $value; }
//     public function setProperty21($value) { $this->property21 = $value; }
//     public function setProperty22($value) { $this->property22 = $value; }
//     public function setProperty23($value) { $this->property23 = $value; }
//     public function setProperty24($value) { $this->property24 = $value; }
//     public function setProperty25($value) { $this->property25 = $value; }
//     public function setProperty26($value) { $this->property26 = $value; }
//     public function setProperty27($value) { $this->property27 = $value; }
//     public function setProperty28($value) { $this->property28 = $value; }
//     public function setProperty29($value) { $this->property29 = $value; }
//     public function setProperty30($value) { $this->property30 = $value; }
//     public function setProperty31($value) { $this->property31 = $value; }
//     public function setProperty32($value) { $this->property32 = $value; }
//     public function setProperty33($value) { $this->property33 = $value; }
//     public function setProperty34($value) { $this->property34 = $value; }
//     public function setProperty35($value) { $this->property35 = $value; }
//     public function setProperty36($value) { $this->property36 = $value; }
//     public function setProperty37($value) { $this->property37 = $value; }
//     public function setProperty38($value) { $this->property38 = $value; }
//     public function setProperty39($value) { $this->property39 = $value; }
//     public function setProperty40($value) { $this->property40 = $value; }
//     public function setProperty41($value) { $this->property41 = $value; }
//     public function setProperty42($value) { $this->property42 = $value; }
//     public function setProperty43($value) { $this->property43 = $value; }
//     public function setProperty44($value) { $this->property44 = $value; }
//     public function setProperty45($value) { $this->property45 = $value; }
//     public function setProperty46($value) { $this->property46 = $value; }
//     public function setProperty47($value) { $this->property47 = $value; }
//     public function setProperty48($value) { $this->property48 = $value; }
//     public function setProperty49($value) { $this->property49 = $value; }
//     public function setProperty50($value) { $this->property50 = $value; }

// }

// class AttributeAssigner {
//     public function attributeParameterAssignee1(object $entity, array $params): void {
//         $setterMap = [
//             'itm_img' => 'setItmImg',
//     'property1' => 'setProperty1',
//     'property2' => 'setProperty2',
//     'property3' => 'setProperty3',
//     'property4' => 'setProperty4',
//     'property5' => 'setProperty5',
//     'property6' => 'setProperty6',
//     'property7' => 'setProperty7',
//     'property8' => 'setProperty8',
//     'property9' => 'setProperty9',
//     'property10' => 'setProperty10',
//     'property11' => 'setProperty11',
//     'property12' => 'setProperty12',
//     'property13' => 'setProperty13',
//     'property14' => 'setProperty14',
//     'property15' => 'setProperty15',
//     'property16' => 'setProperty16',
//     'property17' => 'setProperty17',
//     'property18' => 'setProperty18',
//     'property19' => 'setProperty19',
//     'property20' => 'setProperty20',
//     'property21' => 'setProperty21',
//     'property22' => 'setProperty22',
//     'property23' => 'setProperty23',
//     'property24' => 'setProperty24',
//     'property25' => 'setProperty25',
//     'property26' => 'setProperty26',
//     'property27' => 'setProperty27',
//     'property28' => 'setProperty28',
//     'property29' => 'setProperty29',
//     'property30' => 'setProperty30',
//     'property31' => 'setProperty31',
//     'property32' => 'setProperty32',
//     'property33' => 'setProperty33',
//     'property34' => 'setProperty34',
//     'property35' => 'setProperty35',
//     'property36' => 'setProperty36',
//     'property37' => 'setProperty37',
//     'property38' => 'setProperty38',
//     'property39' => 'setProperty39',
//     'property40' => 'setProperty40',
//     'property41' => 'setProperty41',
//     'property42' => 'setProperty42',
//     'property43' => 'setProperty43',
//     'property44' => 'setProperty44',
//     'property45' => 'setProperty45',
//     'property46' => 'setProperty46',
//     'property47' => 'setProperty47',
//     'property48' => 'setProperty48',
//     'property49' => 'setProperty49',
//     'property50' => 'setProperty50',
//         ];

//         foreach ($params as $key => $value) {
//             if (isset($setterMap[$key])) {
//                 $setter = $setterMap[$key];
//                 if (method_exists($entity, $setter)) {
//                     $entity->$setter($value);
//                 }
//             }
//         }
//     }

//     public function attributeParameterAssignee2(object $entity, array $params): void {
//         foreach ($params as $key => $value) {
//             $setter = 'set' . str_replace('_', '', ucwords($key, '_'));

//             if (method_exists($entity, $setter)) $entity->$setter($value);
//         }
//     }
// }

// function generateTestData() {
//     $params = [];
//     for ($i = 0; $i < 50; $i++) {
//         $params["property$i"] = "value$i";
//     }
//     $params['itm_img'] = 'image_value';
//     return $params;
// }

// function benchmark(callable $func, $iterations = 1000000) {
//     $startTime = microtime(true);
//     for ($i = 0; $i < $iterations; $i++) {
//         $entity = new Entity();
//         $params = generateTestData();
//         $func($entity, $params);
//     }
//     $endTime = microtime(true);
//     return $endTime - $startTime;
// }

// $assigner = new AttributeAssigner();

// $time1 = benchmark([$assigner, 'attributeParameterAssignee1']);
// $time2 = benchmark([$assigner, 'attributeParameterAssignee2']);

// echo "Time for attributeParameterAssignee1: $time1 seconds\n";
// echo "Time for attributeParameterAssignee2: $time2 seconds\n";

// ?>