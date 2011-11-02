<?php
/* Pet Fixture generated on: 2011-11-01 09:33:31 : 1320136411 */

/**
 * PetFixture
 *
 */
class PetFixture extends CakeTestFixture {

/**
 * Fields
 *
 * @var array
 */
	public $fields = array(
		'id' => array('type' => 'integer', 'null' => false, 'length' => 11, 'key' => 'primary'),
		'name' => array('type' => 'string', 'null' => true),
		'description' => array('type' => 'text', 'null' => true),
		'pet_file_path' => array('type' => 'string', 'null' => true),
		'pet_file_name' => array('type' => 'string', 'null' => true),
		'pet_file_size' => array('type' => 'string', 'null' => true),
		'pet_content_type' => array('type' => 'string', 'null' => true),
		'indexes' => array(),
		'tableParameters' => array()
	);

/**
 * Records
 *
 * @var array
 */
	public $records = array(
		array(
			'id' => 1,
			'name' => 'Lorem ipsum dolor sit amet',
			'description' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'pet_file_path' => 'Lorem ipsum dolor sit amet',
			'pet_file_name' => 'Lorem ipsum dolor sit amet',
			'pet_file_size' => 'Lorem ipsum dolor sit amet',
			'pet_content_type' => 'Lorem ipsum dolor sit amet'
		),
	);
}
