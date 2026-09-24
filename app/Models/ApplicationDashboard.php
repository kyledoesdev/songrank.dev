<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApplicationDashboard extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'application_dashboard';

    protected $fillable = [
        'name',
        'about_page',
        'support_page',
        'version',
        'slideshow_speed',
        'seo_terms',
    ];
}
