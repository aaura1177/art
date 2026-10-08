<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class serviceCategories extends Model implements FromCollection, WithHeadings
{
    protected $table = 'service_categories';
    
    protected $fillable = [
        'name',
    ];

  // In serviceCategories Model
public function products()
{
    return $this->hasMany(ServiceProduct::class, 'service_category_id');
}
    

    /**
     * Return the collection of data to be exported.
     *
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Returning all records from the service_categories table
        return $this->all();
    }

    /**
     * Define the headings for the Excel sheet.
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',       // You can add other headings like ID, Name, etc.
            'Name',
        ];
    }
}
