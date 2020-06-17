<?php

use Illuminate\Database\Seeder;

class DB_1585837070DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $this->call(DB_1585837070OauthClientsTableSeeder::class);
        $this->call(DB_1585837070OauthPersonalAccessClientsTableSeeder::class);
    }
}
