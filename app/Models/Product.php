<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'stock',
        'description',
        'is_available',
    ];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
        ];
    }

    public function sitePrices(): HasMany
    {
        return $this->hasMany(ProductSitePrice::class);
    }
}
