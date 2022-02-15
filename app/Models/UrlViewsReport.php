<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UrlViewsReport extends Model
{
    use HasFactory;

    protected $table = 'url_views_reports';
    protected $fillable = [ 'url_id', 'day', 'views' ];
    public $timestamps = false;

    public function url(): BelongsTo
    {
        return $this->belongsTo(ObservableUrl::class, 'url_id', 'id');
    }


}
