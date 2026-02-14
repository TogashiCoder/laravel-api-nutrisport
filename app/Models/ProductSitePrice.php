<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductSitePrice extends Model
{
    protected $fillable = [
        'product_id',
        'site_id',
        'price',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
