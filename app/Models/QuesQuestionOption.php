<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuesQuestionOption extends Model
{
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'ques_question_option';

	/**
	 * The primary key associated with the model.
	 *
	 * @var string
	 */
	protected $primaryKey = 'quop_id';

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
    protected $fillable = [ 'quop_id','quop_code','quop_description' ];

	public function load($ques_id = 0) {
		if ($ques_id > 0) {
			$quop = $this->where('quop_id', $quop_id);
		}else {
			$quop = $this->all();
		}
		return $quop->orderBy('quop_id')->get();
	}

	public function store($values) {
		$quop_id = $values['quop_id'];
		if ($quop_id > 0) {
			$quop = $this->updateOrCreate([ 'quop_id' => $quop_id ], $values);
		}else {
			$quop = $this->create();
		}
		return $quop;
	}

}