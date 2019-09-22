<?php
/*
-------------------------------------------------------------------------
GLPI - Gestionnaire Libre de Parc Informatique
Copyright (C) 2015-2016 Teclib'.

http://glpi-project.org

based on GLPI - Gestionnaire Libre de Parc Informatique
Copyright (C) 2003-2014 by the INDEPNET Development Team.

-------------------------------------------------------------------------

LICENSE

This file is part of GLPI.

GLPI is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

GLPI is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with GLPI. If not, see <http://www.gnu.org/licenses/>.
--------------------------------------------------------------------------
*/

/* Test for inc/computer_softwareversion.class.php */

class Computer_SoftwareVersionTest extends DbTestCase {

   /**
    * @covers Computer_SoftwareVersion::getTypeName
    */
   public function testTypeName() {
      $this->assertEquals('Installation', Computer_SoftwareVersion::getTypeName(1));
      $this->assertEquals('Installations', Computer_SoftwareVersion::getTypeName(0));
      $this->assertEquals('Installations', Computer_SoftwareVersion::getTypeName(10));
   }

   /**
    * @covers Computer_SoftwareVersion::prepareInputForAdd
    */
   public function testPrepareInputForAdd() {
      $this->Login();

      $computer1 = getItemByTypeName('Computer', '_test_pc01');

      // Do some installations
      $ins = new Computer_SoftwareVersion();
      $this->assertGreaterThan(0, $ins->add([
         'computers_id'        => $computer1->getID(),
         'softwareversions_id' => $ver,
      ]));

      $input = [
         'computers_id' => $computer1->getID(),
         'name'         => 'A name'
      ];

      $expected = [
         'computers_id'         => $computer1->getID(),
         'name'                 => 'A name',
         'is_template_computer' => $computer1->getField('is_template'),
         'is_deleted_computer'  => $computer1->getField('is_deleted'),
         'entities_id'          => '1',
         'is_recursive'         => '0'
      ];

      $this->setEntity('_test_root_entity', true);
      $this->assertEquals($expected, $ins->prepareInputForAdd($input));

      $this->setEntity('_test_root_entity', true);
      $this->assertEquals($expected, $ins->prepareInputForAdd($input));
   }

   /**
    * @covers Computer_SoftwareVersion::prepareInputForUpdate
    */
   public function testPrepareInputForUpdate() {
      $this->Login();

      $computer1 = getItemByTypeName('Computer', '_test_pc01');
      $computer11 = getItemByTypeName('Computer', '_test_pc11', true);
      $ver = getItemByTypeName('SoftwareVersion', '_test_softver_1', true);

      // Do some installations
      $ins = new Computer_SoftwareVersion();
      $this->assertGreaterThan(0, $ins->add([
         'computers_id'        => $computer1->getID(),
         'softwareversions_id' => $ver,
      ]));

      $input = [
         'computers_id' => $computer1->getID(),
         'name'         => 'Another name'
      ];

      $expected = [
         'computers_id'         => $computer1->getID(),
         'name'                 => 'Another name',
         'is_template_computer' => $computer1->getField('is_template'),
         'is_deleted_computer'  => $computer1->getField('is_deleted')
      ];

      $this->assertEquals($expected, $ins->prepareInputForUpdate($input));
   }


   /**
    * @covers Computer_SoftwareVersion::countForVersion
    */
   public function testCountInstall() {

      $this->Login();

      $computer1 = getItemByTypeName('Computer', '_test_pc01', true);
      $computer11 = getItemByTypeName('Computer', '_test_pc11', true);
      $computer12 = getItemByTypeName('Computer', '_test_pc12', true);
      $ver = getItemByTypeName('SoftwareVersion', '_test_softver_1', true);

      // Do some installations
      $ins = new Computer_SoftwareVersion();
      $this->assertGreaterThan(0, $ins->add([
         'computers_id'        => $computer1,
         'softwareversions_id' => $ver,
      ]));
      $this->assertGreaterThan(0, $ins->add([
         'computers_id'        => $computer11,
         'softwareversions_id' => $ver,
      ]));
      $this->assertGreaterThan(0, $ins->add([
         'computers_id'        => $computer12,
         'softwareversions_id' => $ver,
      ]));

      // Count installations
      $this->setEntity('_test_root_entity', true);
      $this->assertEquals(3, Computer_SoftwareVersion::countForVersion($ver), 'count in all tree');

      $this->setEntity('_test_root_entity', false);
      $this->assertEquals(1, Computer_SoftwareVersion::countForVersion($ver), 'count in root');

      $this->setEntity('_test_child_1', false);
      $this->assertEquals(2, Computer_SoftwareVersion::countForVersion($ver), 'count in child');
   }

   /**
    * @covers Computer_SoftwareVersion::updateDatasForComputer
    */
   public function testUpdateDatasFromComputer() {
      $c00 = 1566671;
      $computer1 = getItemByTypeName('Computer', '_test_pc01');
      $ver1 = getItemByTypeName('SoftwareVersion', '_test_softver_1', true);
      $ver2 = getItemByTypeName('SoftwareVersion', '_test_softver_2', true);

      // Do some installations
      $softver = new Computer_SoftwareVersion();
      $softver01 = $softver->add([
         'computers_id'        => $computer1->getID(),
         'softwareversions_id' => $ver1,
      ]);
      $this->assertGreaterThan(0, $softver01);
      $softver02 = $softver->add([
         'computers_id'        => $computer1->getID(),
         'softwareversions_id' => $ver2,
      ]);
      $this->assertGreaterThan(0, $softver02);

      foreach ([$softver01, $softver02] as $tsoftver) {
         $o = new Computer_SoftwareVersion();
         $o->getFromDb($tsoftver);
         $this->assertEquals('0', $o->getField('is_deleted_computer'));
      }

      //computer that does not exists
      $this->assertFalse($softver->updateDatasForComputer($c00));

      //update existing computer
      $input = $computer1->fields;
      $input['is_deleted'] = '1';
      $this->assertTrue($computer1->update($input));

      $this->assertEquals(2, $softver->updateDatasForComputer($computer1->getID()));

      //check if all has been updated
      foreach ([$softver01, $softver02] as $tsoftver) {
         $o = new Computer_SoftwareVersion();
         $o->getFromDb($tsoftver);
         $this->assertEquals('1', $o->getField('is_deleted_computer'));
      }

      //restore computer state
      $input['is_deleted'] = '0';
      $this->assertTrue($computer1->update($input));
   }
}
