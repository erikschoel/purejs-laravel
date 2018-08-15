<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\QuesItem;

class QuesBasket extends Model
{
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'ques_basket';

	/**
	 * The primary key associated with the model.
	 *
	 * @var string
	 */
	protected $primaryKey = 'bask_id';

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
    protected $fillable = [ 'bask_id', 'bask_code', 'bask_desc' ];

	protected $guarded = [ 'items' ];

	public function load($bask_id = 0) {
		if ($bask_id > 0) {
			$basket = $this->where('bask_id', $bask_id);
		}else {
			$basket = $this->all();
		}
		return $basket->orderBy('bask_desc')->get();
	}

	public function store($values) {
		$bask_id = $values['bask_id'];
		if ($bask_id > 0) {
			$basket = $this->updateOrCreate([ 'bask_id' => $bask_id ], $values);
			if (isset($values['items']) && is_array($values['items'])) {
				$items = [];
				foreach ($values['items'] as $item) {
					$model = new QuesItem;
					$items[] = $model->store(array_merge($item, [
						'item_fk_basket' => $basket['bask_id']
					]));
				}
				$basket->attributes['items'] = $items;
			}
		}else {
			$basket = $this->create();
		}
		return $basket;
	}

}