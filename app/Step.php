<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Step extends Model
{
    //

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'number', 'recipeId',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'id', 'recipeId', 'created_at', 'updated_at'
    ];
}
