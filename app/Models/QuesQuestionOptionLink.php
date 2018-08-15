<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuesQuestionOptionLink extends Model
{
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'ques_question_option_link';

	/**
	 * The primary key associated with the model.
	 *
	 * @var string
	 */
	protected $primaryKey = 'qoli_id';

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
    protected $fillable = [ 'qoli_id','qoli_fk_question','qoli_fk_option' ];

	public function load($qoli_id = 0) {
		if ($qoli_id > 0) {
			$qoli = $this->where('qoli_id', $qoli_id);
		}else {
			$qoli = $this->all();
		}
		return $qoli->orderBy('qoli_id')->get();
	}

	public function store($values) {
		$qoli_id = $values['qoli_id'];
		if ($qoli_id > 0) {
			$qoli = $this->updateOrCreate([ 'qoli_id' => $qoli_id ], $values);
		}else {
			$qoli = $this->create();
		}
		return $qoli;
	}

}