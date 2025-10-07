<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SliderImage extends Model
{
    protected $fillable = [
        'title',
        'image_path',
        'type',
        'sort_order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer'
    ];

    // Scope for active images
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope for desktop images
    public function scopeDesktop($query)
    {
        return $query->where('type', 'desktop');
    }

    // Scope for mobile images
    public function scopeMobile($query)
    {
        return $query->where('type', 'mobile');
    }

    // Scope for ordering by sort_order
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
