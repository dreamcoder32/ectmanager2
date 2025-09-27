<?php

namespace Database\Seeders;

use App\Models\State;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $states = [
            ['name' => 'Adrar', 'code' => 'DZ-01'],
            ['name' => 'Chlef', 'code' => 'DZ-02'],
            ['name' => 'Laghouat', 'code' => 'DZ-03'],
            ['name' => 'Oum El Bouaghi', 'code' => 'DZ-04'],
            ['name' => 'Batna', 'code' => 'DZ-05'],
            ['name' => 'Béjaïa', 'code' => 'DZ-06'],
            ['name' => 'Biskra', 'code' => 'DZ-07'],
            ['name' => 'Béchar', 'code' => 'DZ-08'],
            ['name' => 'Blida', 'code' => 'DZ-09'],
            ['name' => 'Bouira', 'code' => 'DZ-10'],
            ['name' => 'Tamanrasset', 'code' => 'DZ-11'],
            ['name' => 'Tébessa', 'code' => 'DZ-12'],
            ['name' => 'Tlemcen', 'code' => 'DZ-13'],
            ['name' => 'Tiaret', 'code' => 'DZ-14'],
            ['name' => 'Tizi Ouzou', 'code' => 'DZ-15'],
            ['name' => 'Alger', 'code' => 'DZ-16'],
            ['name' => 'Djelfa', 'code' => 'DZ-17'],
            ['name' => 'Jijel', 'code' => 'DZ-18'],
            ['name' => 'Sétif', 'code' => 'DZ-19'],
            ['name' => 'Saïda', 'code' => 'DZ-20'],
            ['name' => 'Skikda', 'code' => 'DZ-21'],
            ['name' => 'Sidi Bel Abbès', 'code' => 'DZ-22'],
            ['name' => 'Annaba', 'code' => 'DZ-23'],
            ['name' => 'Guelma', 'code' => 'DZ-24'],
            ['name' => 'Constantine', 'code' => 'DZ-25'],
            ['name' => 'Médéa', 'code' => 'DZ-26'],
            ['name' => 'Mostaganem', 'code' => 'DZ-27'],
            ['name' => 'M\'Sila', 'code' => 'DZ-28'],
            ['name' => 'Mascara', 'code' => 'DZ-29'],
            ['name' => 'Ouargla', 'code' => 'DZ-30'],
            ['name' => 'Oran', 'code' => 'DZ-31'],
            ['name' => 'El Bayadh', 'code' => 'DZ-32'],
            ['name' => 'Illizi', 'code' => 'DZ-33'],
            ['name' => 'Bordj Bou Arréridj', 'code' => 'DZ-34'],
            ['name' => 'Boumerdès', 'code' => 'DZ-35'],
            ['name' => 'El Tarf', 'code' => 'DZ-36'],
            ['name' => 'Tindouf', 'code' => 'DZ-37'],
            ['name' => 'Tissemsilt', 'code' => 'DZ-38'],
            ['name' => 'El Oued', 'code' => 'DZ-39'],
            ['name' => 'Khenchela', 'code' => 'DZ-40'],
            ['name' => 'Souk Ahras', 'code' => 'DZ-41'],
            ['name' => 'Tipaza', 'code' => 'DZ-42'],
            ['name' => 'Mila', 'code' => 'DZ-43'],
            ['name' => 'Aïn Defla', 'code' => 'DZ-44'],
            ['name' => 'Naâma', 'code' => 'DZ-45'],
            ['name' => 'Aïn Témouchent', 'code' => 'DZ-46'],
            ['name' => 'Ghardaïa', 'code' => 'DZ-47'],
            ['name' => 'Relizane', 'code' => 'DZ-48'],
            ['name' => 'Timimoun', 'code' => 'DZ-49'],
            ['name' => 'Bordj Badji Mokhtar', 'code' => 'DZ-50'],
            ['name' => 'Ouled Djellal', 'code' => 'DZ-51'],
            ['name' => 'Béni Abbès', 'code' => 'DZ-52'],
            ['name' => 'In Salah', 'code' => 'DZ-53'],
            ['name' => 'In Guezzam', 'code' => 'DZ-54'],
            ['name' => 'Touggourt', 'code' => 'DZ-55'],
            ['name' => 'Djanet', 'code' => 'DZ-56'],
            ['name' => 'El M\'Ghair', 'code' => 'DZ-57'],
            ['name' => 'El Meniaa', 'code' => 'DZ-58'],
        ];

        foreach ($states as $state) {
            State::create([
                'name' => $state['name'],
                'code' => $state['code'],
                'is_active' => true,
            ]);
        }
    }
}
