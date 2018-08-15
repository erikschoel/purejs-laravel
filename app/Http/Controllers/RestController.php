<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Classes\Lambda;
use App\Models\QuesBasket;
use App\Models\QuesQuestion;
use App\Models\QuesSelector;

use App\Models\OrgModel;

class RestController extends Controller
{
    private function _auth($fetch_entity_id = true) {
        if (Auth::check()) {
            $user = Auth::getUser()->getAttributes();
        }else {
            $user = array('user_id' => 0);
        }
        return $user;
    }
    public function auth() {
        $user = $this->_auth();
        return response()->json($user, 200);
    }
    public function login() {
        if (Auth::attempt([ 'maus_username' => Input::get('username'), 'maus_password' => Input::get('password') ])) {
            $user = Auth::getUser()->getAttributes();
        }else {
            $user = array('user_id' => 0);
        }
        return response()->json($user, 200);
    }
    public function logout() {
        Auth::logout();
        return response()->json([
            'logged_out' => true
        ], 200);
    }
    public function basket($bask_id = 0)
    {
        if ($bask_id) {
            $basket = DB::select('select * from ques_basket where bask_id = :bask_id', [
                'bask_id' => $bask_id
            ]);
            if (count($basket)) {
                $basket = array_shift($basket);
                $basket->items = DB::select('select item.*, mare_code as item_code, mare_description as item_desc from ques_item as item join main_record on mare_id = item_id where item_fk_basket = :bask_id', [
                    'bask_id' => $bask_id
                ]);
                // $basket->questions = DB::select('select ques.* from ques_question as ques join main_record on mare_id = item_id where item_fk_basket = :bask_id', [
                //     'bask_id' => $bask_id
                // ]);
            }
        }else {
            $basket = DB::select('select * from ques_basket');
        }
        return response()->json($basket, 200);
    }
    public function saveBasket()
    {
        $model  = new QuesBasket();
        $result = $model->store(Input::all());
        return response()->json($result, 200);
    }
    public function item($item_id = 0, $output_type = '')
    {
        $query = 'select item.*, mare_code as item_code, mare_description as item_desc from ques_item as item join main_record on mare_id = item_id';
        if ($item_id) {
            $item = DB::select($query.' where item_id = :item_id', [
                'item_id' => $item_id
            ]);
            if (count($item)) {
                $item = array_shift($item);
                if ($output_type == 'cockpit') {
                    $task = DB::select('select * from main_record where mare_fk_parent = :item_id and mare_maintype = \'org_program_task\';', [
                        'item_id' => $item->item_id
                    ]);
                    // $item->tasks = [];
                    // foreach ($tasks as $task) {
                    //     $task->questions = DB::select('CALL stp_getQuestions(:entity_id, :item_id)', [
                    //         'entity_id' => 0,
                    //         'item_id' => $task->mare_id
                    //     ]);
                    //     $item->tasks[] = $task;
                    // }
                    $item->tasks = DB::select('CALL stp_getProgramTasks(:entity_id, :task_id)', [
                        'entity_id' => 0, 'task_id' => $task[0]->mare_id
                    ]);
                }else {
                    $item->selector = DB::select('CALL stp_getQuesSelector(:sele_id, :item_id)', [
                        'sele_id' => 0, 'item_id' => $item->item_id
                    ]);
                }
            }
        }else {
            $item = DB::select($query);
        }
        return response()->json($item, 200);
    }
    public function saveItem()
    {
        $model  = new QuesItem();
        $result = $model->store(Input::all());
        return response()->json($result, 200);
    }
    public function model($omod_code = '')
    {
        if ($omod_code) {
            $model = new OrgModel;
            $model = $model->withUnits($omod_code);
        }else {
            $model = DB::select('select * from org_model');
        }
        return response()->json($model, 200);
    }
    public function saveModel()
    {
        $model  = new OrgModel();
        $result = $model->store(Input::all());
        return response()->json($result, 200);
    }
    public function role($orgr_id = 0)
    {
        if ($orgr_id) {
            $role = DB::select('select * from org_role where orgr_id = :orgr_id', [
                'orgr_id' => $orgr_id
            ]);
            if (count($role)) {
                $role = array_shift($role);
            }
        }else {
            $role = DB::select('select * from org_role');
        }
        return response()->json($role, 200);
    }
    public function saveRole()
    {
        $model  = new QuesBasket();
        $result = $model->store(Input::all());
        return response()->json($result, 200);
    }
    public function selector($sele_id = 0)
    {
        $selector = DB::select('CALL stp_getQuesSelector(:sele_id, :item_id)', [
            'sele_id' => $sele_id, 'item_id' => 0
        ]);
        return response()->json($sele_id ? array_shift($selector) : $selector, 200);
    }
    public function saveSelector()
    {
        $model  = new QuesSelector();
        $result = $model->store(Input::all());
        return response()->json($result, 200);
    }
    public function program($prog_code = '', $prog_type = 'org')
    {
        if (is_numeric($prog_code)) {
            $program = DB::select('select * from '.$prog_type.'_program where '.($prog_type == 'org' ? 'prgm_id' : 'prog_id').' = :id', [
                'id' => $prog_code
            ]);
            if (count($program)) {
                $program = array_shift($program);
            }else {
                $program = DB::select('select mare_id as prog_id, mare_description as prog_description from main_record where mare_id = :id', [
                    'id' => $prog_code
                ]);
            }
        }else if ($prog_type == 'ques') {
            $program = DB::select('CALL stp_getNextProgram(:entity_id, :prog_code)', [
                'entity_id' => 0,
                'prog_code' => $prog_code
            ]);
        }else {
            $program = DB::select('select * from org_program order by prgm_description');
        }
        return response()->json($program, 200);
    }
    public function questionnaire($prog_code = '') {
        return $this->program($prog_code, 'ques');
    }
    private function __program($prog_code) {
        if (is_numeric($prog_code)) {
            $record = DB::select('select mare_id from main_record where mare_id = :prog_code', [
                'prog_code' => $prog_code
            ]);
        }else {
            $record = DB::select('select func_getRecordID(:dim_code, :attr_code, :prog_code) as mare_id', [
                'dim_code'  => 'sys.type.program',
                'attr_code' => 'sys.attr.code',
                'prog_code' => $prog_code
            ]);
        }
        return $record;
    }
    public function questions($prog_code = '', $entity_id = 0)
    {
        $record = $this->__program($prog_code);
        if (count($record)) {
            $record = array_shift($record);
            $questions = DB::select('CALL stp_getQuestions(:entity_id, :record_id)', [
            	'entity_id' => $entity_id,
            	'record_id' => $record->mare_id
            ]);
        }else {
            $questions = [];
        }
        return response()->json($questions, 200);
    }
    private function __question($ques_id, $entity_id = 0)
    {
        return response()->json($this->_question($ques_id, $entity_id), 200);
    }
    private function _question($ques_id, $entity_id = 0)
    {
        $question = DB::select('CALL stp_getQuestion(:ques_id, :entity_id, :lang_id)', [
            'ques_id'   => $ques_id,
            'entity_id' => $entity_id,
            'lang_id' => 'sys.lang.nl'
        ]);
        if (count($question)) {
            $question = $question[0];
            $question->options = DB::select('CALL stp_getQuestionOption(:ques_id, :entity_id, :lang_id)', [
                'ques_id'   => $ques_id,
                'entity_id' => $entity_id,
                'lang_id' => 'sys.lang.nl'
            ]);
        }
        return $question;
    }
    public function question($ques_id = 0, $entity_id = 0)
    {
        return response()->json([ $this->_question($ques_id, $entity_id) ]);
    }
    public function saveQuestion()
    {
        $model  = new QuesQuestion();
        $result = $model->store(Input::all());
        return response()->json($result, 200);
    }
    public function nextQuestion($prog_code = 'INTK', $after_id = '0', $until_id = '0', $entity_id = '0')
    {
        $record = $this->__program($prog_code);
        if (count($record)) {
            $record = array_shift($record);
            $question = DB::select('CALL stp_getNextQuestion(:entity_id, :record_id, :after_id, :until_id)', [
                'entity_id' => $entity_id,
                'record_id' => $record->mare_id,
                'after_id'  => $after_id,
                'until_id'  => $until_id
            ]);
            if (count($question)) {
                $result = array_map(Lambda::instance(function($entity_id, $ques) {
                    return $this->_question($ques->ques_id, $entity_id);
                })->__invoke($entity_id), $question);
                return response()->json($result, 200);
            }
        }else {
            $question = [];
        }
        return response()->json($question, 200);
    }
    public function resetQuestion($prog_code = 'INTK', $after_id = '0', $entity_id = '0')
    {
        $question = DB::select('CALL stp_resetQuestion(:entity_id, :prog_code, :after_id)', [
            'entity_id' => $entity_id,
            'prog_code' => $prog_code,
            'after_id'  => $after_id
        ]);
        if (count($question)) {
            $question = $question[0];
            return $this->__question($question->ques_id, 0);
        }
        return response()->json($question, 200);
    }
    public function answer()
    {
        $answer = DB::select('CALL stp_saveAnswer(:ques_id, :entity_id, :record_id, :answer)', [
            'ques_id'   => Input::get('ques_id'),
            'entity_id' => Input::get('entity_id'),
            'record_id' => Input::get('record_id') || 0,
            'answer'    => Input::get('answer')
        ]);
        return response()->json($answer, 200);
    }
    public function basketItems($prog_code = '', $entity_id = 0)
    {
        $items = DB::select('CALL stp_getBasketItems(:entity_id, :prog_code)', [
            'entity_id' => $entity_id,
            'prog_code' => $prog_code
        ]);

        $result = array();
        foreach($items as $item) {
            if (!isset($result[$item->bask_id])) {
                $result[$item->bask_id] = array(
                    'bask_id'   => $item->bask_id,
                    'bask_code' => $item->bask_code,
                    'bask_desc' => $item->bask_desc,
                    'items' => array()                   
                );
            }
            $result[$item->bask_id]['items'][] = array(
                'item_id'     => $item->item_id,
                'item_code'   => $item->item_code,
                'item_desc'   => $item->item_desc,
                'item_count'  => $item->item_count,
                'item_cp_url' => '#cockpit/'.$item->item_id,
                'item_qn_url' => '#player/'.$item->item_id
            );
        }
        return response()->json(array_values($result), 200);
    }
}
