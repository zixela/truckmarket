<?php

namespace Database\Seeders;

use App\Models\ZipCode;
use Illuminate\Database\Seeder;

/**
 * Starter ZIP dataset — enough for radius search in development.
 * Import a full US ZIP dataset into the same table for production.
 */
class ZipCodeSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['10001', 'New York', 'NY', 40.750742, -73.996530],
            ['10002', 'New York', 'NY', 40.715781, -73.986176],
            ['07030', 'Hoboken', 'NJ', 40.744323, -74.032571],
            ['08608', 'Trenton', 'NJ', 40.219438, -74.762598],
            ['19102', 'Philadelphia', 'PA', 39.953349, -75.165222],
            ['02108', 'Boston', 'MA', 42.357603, -71.064280],
            ['20001', 'Washington', 'DC', 38.910353, -77.017739],
            ['21201', 'Baltimore', 'MD', 39.293415, -76.622368],
            ['30301', 'Atlanta', 'GA', 33.748992, -84.390264],
            ['33101', 'Miami', 'FL', 25.779258, -80.198181],
            ['32801', 'Orlando', 'FL', 28.541286, -81.375007],
            ['28202', 'Charlotte', 'NC', 35.226569, -80.843124],
            ['37201', 'Nashville', 'TN', 36.166836, -86.779182],
            ['60601', 'Chicago', 'IL', 41.886456, -87.622551],
            ['60007', 'Elk Grove Village', 'IL', 42.006520, -87.995325],
            ['48201', 'Detroit', 'MI', 42.345900, -83.062700],
            ['44101', 'Cleveland', 'OH', 41.499320, -81.694360],
            ['46201', 'Indianapolis', 'IN', 39.775000, -86.109000],
            ['53202', 'Milwaukee', 'WI', 43.043270, -87.906470],
            ['55401', 'Minneapolis', 'MN', 44.985030, -93.269920],
            ['63101', 'St. Louis', 'MO', 38.633400, -90.192800],
            ['64101', 'Kansas City', 'MO', 39.104100, -94.588700],
            ['70112', 'New Orleans', 'LA', 29.958400, -90.077600],
            ['73101', 'Oklahoma City', 'OK', 35.472600, -97.517400],
            ['75201', 'Dallas', 'TX', 32.788500, -96.799400],
            ['77001', 'Houston', 'TX', 29.813100, -95.310100],
            ['78201', 'San Antonio', 'TX', 29.462800, -98.516900],
            ['78701', 'Austin', 'TX', 30.271100, -97.743700],
            ['80201', 'Denver', 'CO', 39.750500, -104.999600],
            ['84101', 'Salt Lake City', 'UT', 40.759500, -111.888200],
            ['85001', 'Phoenix', 'AZ', 33.448400, -112.074000],
            ['89101', 'Las Vegas', 'NV', 36.174500, -115.137200],
            ['90001', 'Los Angeles', 'CA', 33.973900, -118.248700],
            ['90210', 'Beverly Hills', 'CA', 34.090010, -118.406310],
            ['92101', 'San Diego', 'CA', 32.715700, -117.161100],
            ['94102', 'San Francisco', 'CA', 37.779300, -122.419200],
            ['95814', 'Sacramento', 'CA', 38.581600, -121.493900],
            ['97201', 'Portland', 'OR', 45.512200, -122.658100],
            ['98101', 'Seattle', 'WA', 47.606200, -122.332100],
            ['99501', 'Anchorage', 'AK', 61.218100, -149.900300],
        ];

        foreach (array_chunk($rows, 100) as $chunk) {
            ZipCode::query()->upsert(
                array_map(fn ($r) => [
                    'zip' => $r[0], 'city' => $r[1], 'state' => $r[2],
                    'latitude' => $r[3], 'longitude' => $r[4],
                ], $chunk),
                ['zip'],
                ['city', 'state', 'latitude', 'longitude']
            );
        }
    }
}
