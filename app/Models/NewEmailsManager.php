<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewEmailsManager extends Model
{
    use HasFactory;

    protected $fillable = ['manager', 'count_new', 'date'];
    protected $table = 'new_emails_manager';
    public $timestamps = false;

}
