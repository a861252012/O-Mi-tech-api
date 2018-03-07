<?php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class Faq extends \Illuminate\Database\Eloquent\Model
{
    use SoftDeletes;
    protected $table = 'video_faqs';
    protected $guarded = ['id'];
}