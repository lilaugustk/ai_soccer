<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Wc2026StadiumCoordinatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $stadiums = [
            ['name' => 'MetLife Stadium', 'lat' => 40.8128, 'lng' => -74.0742],
            ['name' => 'AT&T Stadium', 'lat' => 32.7473, 'lng' => -97.0945],
            ['name' => 'SoFi Stadium', 'lat' => 33.9535, 'lng' => -118.3390],
            ['name' => 'Levi\'s Stadium', 'lat' => 37.4033, 'lng' => -121.9694],
            ['name' => 'Hard Rock Stadium', 'lat' => 25.9580, 'lng' => -80.2389],
            ['name' => 'Lumen Field', 'lat' => 47.5952, 'lng' => -122.3316],
            ['name' => 'Lincoln Financial Field', 'lat' => 39.9008, 'lng' => -75.1675],
            ['name' => 'Arrowhead Stadium', 'lat' => 39.0489, 'lng' => -94.4839],
            ['name' => 'Gillette Stadium', 'lat' => 42.0909, 'lng' => -71.2643],
            ['name' => 'NRG Stadium', 'lat' => 29.6847, 'lng' => -95.4107],
            ['name' => 'Rose Bowl', 'lat' => 34.1613, 'lng' => -118.1676],
            ['name' => 'BC Place', 'lat' => 49.2768, 'lng' => -123.1120],
            ['name' => 'BMO Field', 'lat' => 43.6332, 'lng' => -79.4186],
            ['name' => 'Estadio Azteca', 'lat' => 19.3029, 'lng' => -99.1505],
            ['name' => 'Estadio BBVA', 'lat' => 25.6688, 'lng' => -100.2458],
            ['name' => 'Estadio Akron', 'lat' => 20.6811, 'lng' => -103.4628],
            ['name' => 'Mercedes-Benz Stadium', 'lat' => 33.7553, 'lng' => -84.4006],
        ];

        foreach ($stadiums as $data) {
            DB::table('wc2026_stadiums')
                ->where('name', $data['name'])
                ->update([
                    'latitude' => $data['lat'],
                    'longitude' => $data['lng'],
                ]);
        }
    }
}
