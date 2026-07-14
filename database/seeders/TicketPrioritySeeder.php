<?php

namespace Database\Seeders;

use App\Models\TicketPriority;
use Illuminate\Database\Seeder;

class TicketPrioritySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $ticketPriorities = [
            [
                "name" => "Low",
                "level" => 1,
                "color" => "green"
            ],
            [
                "name" => "Medium",
                "level" => 2,
                "color" => "blue"
            ],
            [
                "name" => "High",
                "level" => 3,
                "color" => "orange"
            ],
            [
                "name" => "Critical",
                "level" => 4,
                "color" => "red"
            ],

        ];

        foreach ($ticketPriorities as $priority) {
            TicketPriority::create($priority);
        }
    }
}
