<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Contacts extends Model
{
    protected $fillable = ['first_name', 'last_name', 'email_address', 'phone_number'];
}
