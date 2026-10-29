<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Visitor extends Model
{
    use HasFactory;
    protected $table = 'shetabit_visits';


    public static function uniqueIpVisitors()
    {
        // Use Laravel's built-in "distinct" and "count" methods
        // to count the unique IP addresses in the visitors table
        return self::distinct('ip')->count('ip');
    }


    public static function uniqueBrowsers()
    {
        // Use Laravel's query builder to fetch the unique browsers and their counts
        // by grouping by the "browser" column and counting the distinct "ip" column
        $browsers = self::select('browser', DB::raw('COUNT(DISTINCT ip) as unique_ip_count'))
            ->groupBy('browser')
            ->get();

        // Calculate the total count by summing up the unique IP counts
        $total = $browsers->sum('unique_ip_count');

        // Calculate the percentage for each browser based on unique IP count and total count
        $browsers->each(function ($browser) use ($total) {
            $browser->percentage = ($browser->unique_ip_count / $total) * 100;
        });

        // Sort the browsers by percentage in descending order
        $browsers = $browsers->sortByDesc('percentage');

        // Return the browsers and their counts, along with the total count, as an array
        return [
            'browsers' => $browsers,
            'total' => $total
        ];
    }
}
