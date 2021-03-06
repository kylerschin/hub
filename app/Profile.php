<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $table = "profile";

    public $hidden = [
    'email',
    'email_validated',
    'email_is_public',
    'privacy',
    'nickname',
    'user_id',
    'location',
    'website',
    'primary_node',
    'socialnode',
    'twitter',
    'github',
    'keybase'
    ];
}
