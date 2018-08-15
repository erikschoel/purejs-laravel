<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuesItem extends Model
{
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'ques_item';

	/**
	 * The primary key associated with the model.
	 *
	 * @var string
	 */
	protected $primaryKey = 'item_id';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [ 'item_id', 'item_type', 'item_fk_phase', 'item_fk_basket' ];

	public function load($item_id = 0) {
		if ($item_id > 0) {
			$item = $this->where('item_id', $item_id)->orderBy('item_id')->get();
		}else {
			$item = $this->all();
		}
		return $item;
	}

	public function store($values) {
		$item_id = $values['item_id'];
		if ($item_id > 0) {
			$item = $this->updateOrCreate([ 'item_id' => $item_id ], $values);
		}else {
			$item = $this->create();
		}
		return $item;
	}

}