<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\QuesItem;

class QuesSelector extends Model
{
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'ques_selector';

	/**
	 * The primary key associated with the model.
	 *
	 * @var string
	 */
	protected $primaryKey = 'sele_id';

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
    protected $fillable = [ 'sele_id', 'sele_fk_question', 'sele_fk_question_option', 'sele_operator', 'sele_value', 'sele_type' ];

	protected $guarded = [ 'sele_description' ];

	protected $appends = [ 'sele_description' ];

	public function getSeleDescriptionAttribute() {
	    return 'sele_description';
	}

	public function load($sele_id = 0) {
		if ($sele_id > 0) {
			$selector = $this->where('sele_id', $sele_id)->get();
		}else {
			$selector = $this->all();
		}
		return $selector;
	}

	public function store($values) {
		$sele_id = $values['sele_id'];
		if ($sele_id > 0) {
			$selector = $this->updateOrCreate([ 'sele_id' => $sele_id ], $values);
		}else {
			$selector = $this->create();
		}
		return $selector;
	}

}