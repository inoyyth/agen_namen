<?php

use Illuminate\Database\Seeder;
use App\Category;

class CategoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $datas = array(
            [
                'name' => 'Makanan',
                'description' => "Segala jenis makanan",
                'status' => 1,
            ],
            [
                'name' => 'Minuman',
                'description' => "Segala Jenis Minuman",
                'status' => 1,
            ]
        );

        foreach ($datas as $data) {
            Category::create($data);
        }
    }
}
