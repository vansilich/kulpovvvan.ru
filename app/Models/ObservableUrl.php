<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ObservableUrl extends Model
{
    use HasFactory;

    protected $table = 'observable_urls';
    protected $fillable = [ 'url' ];
    public $timestamps = false;

    public function viewsReports(): HasMany
    {
        return $this->hasMany(UrlViewsReport::class, 'url_id', 'id');
    }
}
