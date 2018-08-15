<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuesQuestion extends Model
{
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'ques_question';

	/**
	 * The primary key associated with the model.
	 *
	 * @var string
	 */
	protected $primaryKey = 'ques_id';

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
    protected $fillable = [ 'ques_id','ques_fk_category','ques_type','ques_code','ques_description','ques_template' ];

	public function load($ques_id = 0) {
		if ($ques_id > 0) {
			$ques = $this->where('ques_id', $ques_id);
		}else {
			$ques = $this->all();
		}
		return $ques->orderBy('ques_id')->get();
	}

	public function store($values) {
		$ques_id = $values['ques_id'];
		if ($ques_id > 0) {
			$ques = $this->updateOrCreate([ 'ques_id' => $ques_id ], $values);
		}else {
			$ques = $this->create();
		}
		return $ques;
	}

    /**
     * Get the answer options for the question.
     */
    public function options()
    {
    	return $this->hasManyThrough(
    		'App\Models\QuesQuestionOption', // Post
    		'App\Models\QuesQuestionOptionLink', // User
            'qoli_fk_question', // Foreign key on users table...
            'qoli_fk_option', // Foreign key on posts table...
            'ques_id', // Local key on countries table...
            'quop_id' // Local key on users table...
    	);
    }

}