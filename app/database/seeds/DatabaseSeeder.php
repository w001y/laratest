<?php

class DatabaseSeeder extends Seeder {

	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{

        if (App::environment() === 'production') {
            exit('Production environment detected! NO SEEDING FOR YOU.. Set machine name in bootstrap/start.php to specify local host');
        }

		Eloquent::unguard();


		$this->call('EmailPlatformsTableSeeder');
		$this->call('MemberTiersTableSeeder');
		$this->call('RewardTypesTableSeeder');
		$this->call('RewardDefinitionsTableSeeder');

	}

}