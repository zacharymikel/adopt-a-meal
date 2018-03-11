<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(MasterAdminSeeder::class);
        $this->call(MessageTypesSeeder::class);
        $this->call(MessagesSeeder::class);
    }
}
