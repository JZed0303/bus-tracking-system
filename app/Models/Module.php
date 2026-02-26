<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'icon',
        'route_name',
        'menu_url',
        'scope',
        'menu_type',
        'parent_id',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('title');
    }

    public function isHeader(): bool
    {
        return $this->menu_type === 'header';
    }

    public function isDropdown(): bool
    {
        return $this->menu_type === 'dropdown';
    }

    public function isLink(): bool
    {
        return $this->menu_type === 'link';
    }

    public function permissionName(string $action): string
    {
        return strtolower($action . '_' . $this->slug);
    }
}
