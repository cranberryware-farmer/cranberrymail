<?php

use Illuminate\Database\Seeder;

class DB_1585837070OauthClientsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('oauth_clients')->delete();
        
        \DB::table('oauth_clients')->insert(array (
            0 => 
            array (
                'id' => 1,
                'user_id' => NULL,
                'name' => 'Cranberry Mail Personal Access Client',
                'secret' => 'qd4CC4imavEltmJ2H3Bs7Fjc6DZiI4y9YqdQzYs5',
                'redirect' => 'http://localhost',
                'personal_access_client' => 1,
                'password_client' => 0,
                'revoked' => 0,
                'created_at' => '2020-04-02 14:11:32',
                'updated_at' => '2020-04-02 14:11:32',
            ),
            1 => 
            array (
                'id' => 2,
                'user_id' => NULL,
                'name' => 'Cranberry Mail Password Grant Client',
                'secret' => 'ZYPqhPqsgo7NaGBMD6gHFEeHzm49JfMTpljSThcq',
                'redirect' => 'http://localhost',
                'personal_access_client' => 0,
                'password_client' => 1,
                'revoked' => 0,
                'created_at' => '2020-04-02 14:11:32',
                'updated_at' => '2020-04-02 14:11:32',
            ),
            2 => 
            array (
                'id' => 3,
                'user_id' => NULL,
                'name' => 'Cranberry Mail Personal Access Client',
                'secret' => 'pn0KvLYuGUygWVlTccXh4GZSJJmKDDu2LUP6aQdX',
                'redirect' => 'http://localhost',
                'personal_access_client' => 1,
                'password_client' => 0,
                'revoked' => 0,
                'created_at' => '2020-04-02 14:11:44',
                'updated_at' => '2020-04-02 14:11:44',
            ),
            3 => 
            array (
                'id' => 4,
                'user_id' => NULL,
                'name' => 'Cranberry Mail Password Grant Client',
                'secret' => 'J71JJzrUawTljMZiosb1ChgRoE81WVIpIkE4kkT7',
                'redirect' => 'http://localhost',
                'personal_access_client' => 0,
                'password_client' => 1,
                'revoked' => 0,
                'created_at' => '2020-04-02 14:11:44',
                'updated_at' => '2020-04-02 14:11:44',
            ),
        ));
        
        
    }
}