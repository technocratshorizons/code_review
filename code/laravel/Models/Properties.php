<?php

/**
 * Created by Reliese Model.
 * Date: Mon, 14 May 2018 12:53:46 +0000.
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class User
 * 
 * @property int $id
 * @property string $user_type
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string $password
 * @property \Carbon\Carbon $created
 * @property \Carbon\Carbon $modified
 *
 * @package App\Models
 */
class Properties extends Eloquent
{
	public $timestamps = false;

	protected $dates = [
		'created',
		'modified'
	];

	
	protected $fillable = [
		'property_name',
		'id',
		'user_id',
		'address',
		'city',
		'street',
		'postal_code',
		'type',
		'furnish',
		'smoking',
		'allowed',
		'bedroom',
		'bathroom',
		'enter_area',
		'built_year',
		'parking_type',
		'monthly_rent',
		'security_deposit',
		'utilities_included',
		'rental_incentives',
		'description',
		'available_from',
		'term_of_lease',
		'contact_method_email',
		'featured',
		'modified',
		'created',
		'contact_method_phone',
		'contact_method_text_message',
		'contact_method_hide_phone',
		'hide_address',
		'is_rented',
		'is_deleted',
		'total_view',
	];

	public function property_images()
    {
		return $this->hasMany('App\Models\Property_galleries','property_id');
	}

	public function property_amenities()
    {
		return $this->hasMany('App\Models\Property_amenities','property_id');
	}

	public function property_availabilities()
    {
		return $this->hasMany('App\Models\Property_availabilities','property_id')->whereDate('date', '>=', date('Y-m-d'))->where('time_from', '>=', date('h:i A'));
	}
	

	public function property_amenities_community()
    {
		return $this->hasMany('App\Models\Property_amenities','property_id')->where('ammenties_type','Community');
	}

	public function property_amenities_property()
    {
		return $this->hasMany('App\Models\Property_amenities','property_id')->where('ammenties_type','Property');
	}
	public function favorite_property()
    {   
        return $this->hasMany('App\Models\Favourite_properties','property_id');
	}

	public function landlord_info()
    {   
        return $this->belongsTo('App\Models\User','user_id');
	}

	public function rental_forms()
    {   
        return $this->belongsTo('App\Models\Rental_forms','id');
	}
}
