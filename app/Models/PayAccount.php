<?php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class PayAccount extends \Illuminate\Database\Eloquent\Model
{
    use SoftDeletes;
    protected  $table='video_pay_account';
    protected $primaryKey = 'id';
}