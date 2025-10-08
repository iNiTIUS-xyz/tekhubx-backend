<?php

namespace Database\Seeders;

use Carbon\Carbon;
use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ExpenseCategoryTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        DB::table('expense_categories')->insert([
            [
                'id' => 1,
                'name' => 'Freight',
                'created_at' => Carbon::parse('2024-10-06 00:27:39'),
                'updated_at' => Carbon::parse('2024-10-06 00:27:39'),
            ],
            [
                'id' => 2,
                'name' => 'Personal material cost',
                'created_at' => Carbon::parse('2024-10-06 00:27:51'),
                'updated_at' => Carbon::parse('2024-10-06 00:27:51'),
            ],
            [
                'id' => 3,
                'name' => 'Real material cost',
                'created_at' => Carbon::parse('2024-10-06 00:28:02'),
                'updated_at' => Carbon::parse('2024-10-06 00:28:02'),
            ],
            [
                'id' => 4,
                'name' => 'Scope of work order',
                'created_at' => Carbon::parse('2024-10-06 00:28:54'),
                'updated_at' => Carbon::parse('2024-10-06 00:31:41'),
            ],
            [
                'id' => 5,
                'name' => 'Taxes',
                'created_at' => Carbon::parse('2024-10-06 00:29:18'),
                'updated_at' => Carbon::parse('2024-10-06 00:29:18'),
            ],
            [
                'id' => 6,
                'name' => 'Travel Expense',
                'created_at' => Carbon::parse('2024-10-06 00:29:32'),
                'updated_at' => Carbon::parse('2024-10-06 00:29:32'),
            ],
        ]);
    }

}
