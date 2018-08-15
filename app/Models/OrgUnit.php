<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class OrgUnit extends Model
{
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'org_unit';

	/**
	 * The primary key associated with the model.
	 *
	 * @var string
	 */
	protected $primaryKey = 'orun_id';

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
    protected $fillable = [ 'orun_id', 'orun_fk_model', 'orun_fk_parent', 'orun_code', 'orun_description' ];

	protected $guarded = [ 'roles' ];

	public function load($value, $key = 'orun_fk_model') {
		if (!$value) {
			$model = $this->all();
		}else {
			$model = $this->where($key, $value)->get();
		}
		return $model;
	}

	public function withRoles($value, $key = 'orun_fk_model') {
		$model = $this->load($value, $key)->toArray();
        if (count($model)) {
        	foreach($model as &$mod) {
	            $mod['roles'] = DB::select('select * from org_unit_role_link join org_role on orgr_id = ourl_fk_org_role where ourl_fk_org_unit = :orun_id', [
	                'orun_id' => $mod['orun_id']
	            ]);
	        }
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