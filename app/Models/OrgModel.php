<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use App\Models\OrgUnit;

class OrgModel extends Model
{
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'org_model';

	/**
	 * The primary key associated with the model.
	 *
	 * @var string
	 */
	protected $primaryKey = 'omod_id';

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
    protected $fillable = [ 'omod_id', 'omod_code', 'omod_description' ];

	protected $guarded = [ 'units' ];

	public function load($where = false) {
		if (!$where) {
			$model = $this->all();
		}else if (is_numeric($where)) {
			$model = $this->where('omod_id', $where)->get();
		}else {
			$model = $this->where('omod_code', $where)->get();
		}
		return $model;
	}

	public function withUnits($where = false) {
		$model = $this->load($where)->toArray();
        if (count($model)) {
            $model = array_shift($model);
            $units = new OrgUnit;
            $model['units'] = $units->withRoles($model['omod_id']);
        }else {
        	$model = [];
        }
        return $model;
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