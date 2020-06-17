<?php

use Illuminate\Database\Seeder;

class DB_1585837070OauthPersonalAccessClientsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('oauth_personal_access_clients')->delete();
        
        \DB::table('oauth_personal_access_clients')->insert(array (
            0 => 
            array (
                'id' => 1,
                'client_id' => 1,
                'created_at' => '2020-04-02 14:11:32',
                'updated_at' => '2020-04-02 14:11:32',
            ),
            1 => 
            array (
                'id' => 2,
                'client_id' => 3,
                'created_at' => '2020-04-02 14:11:44',
                'updated_at' => '2020-04-02 14:11:44',
            ),
        ));
        
        
    }
}