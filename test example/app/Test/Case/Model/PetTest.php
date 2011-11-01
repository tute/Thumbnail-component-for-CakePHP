<?php
/* Pet Test cases generated on: 2011-11-01 09:33:31 : 1320136411*/
App::uses('Pet', 'Model');

/**
 * Pet Test Case
 *
 */
class PetTestCase extends CakeTestCase {
/**
 * Fixtures
 *
 * @var array
 */
	public $fixtures = array('app.pet');

/**
 * setUp method
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();

		$this->Pet = ClassRegistry::init('Pet');
	}

/**
 * tearDown method
 *
 * @return void
 */
	public function tearDown() {
		unset($this->Pet);

		parent::tearDown();
	}

}
