<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password','phone_no','roll_no','division','parentname1', 'parentemail1','parentphone_no1','parentname2', 'parentemail2','parentphone_no2'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    public function division_class()
    {
        return $this->belongsTo('App\Division','division');
    }
     public function applications()
     {
         return $this->hasMany('App\Application','student_id');
     }
     public function internalTests()
     {
         return $this->hasMany('App\InternalTest','student_id','id');
     }

}
