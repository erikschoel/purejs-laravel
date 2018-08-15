<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Classes\Lambda;
use App\Models\QuesBasket;
use App\Models\QuesQuestion;
use App\Models\QuesSelector;

use App\Models\OrgModel;

function json_decode_nice($json, $assoc = true){
    $json = str_replace(array("\n","\r"),"\\n",$json);
    // $json = preg_replace('/([{,]+)(\s*)([^"]+?)\s*:/','$1"$3":',$json);
    // $json = preg_replace('/(,)\s*}$/','}',$json);
    return json_decode($json,$assoc);
}

class DbController extends Controller
{

    protected $idMap = [];

    /* ========== CORE ========== */
    private function dimension($root = 'sys', $filter = 0) {
        return DB::select('CALL stp_selectDimensions(:root, :filter)', [
            'root' => $root, 'filter' => $filter
        ]);
    }
    private function __attributes() {
        return $this->dimension('sys.attr', 1);
    }
    public function attributes() {
        return response()->json($this->__attributes(), 200);
    }
    public function types() {
        return $this->dimension('sys.type');
    }
    public function dimensions() {
        return $this->dimension('', 1);
    }
    public function relations() {
        return $this->dimension('', 2);
    }
    public function menus() {
        return $this->dimension('sys.type.app');
    }
    public function relation($code = '') {
        $result = DB::select('select concat(\'mare_\', cast(madi_id as char)) as name, t.* from main_dimension as t order by madi_code');
        return response()->json($result, 200);
    }
    public function selector($code = '') {
        $result = DB::select('select concat(\'mare_\', cast(madi_id as char)) as name, t.* from main_dimension as t order by madi_code');
        return response()->json($result, 200);
    }
    public function lookup() {
        return $this->data('sys.type%');
    }
    /* ========== DATA ========== */
    public function all($filter = '%', $lang = 'nl') {
        DB::select('CALL stp_selectRecords(:filter, :lang)', [
            'filter' => $filter, 'lang' => strpos($lang, '.') !== false ? $lang : 'sys.lang.'.$lang
        ]);
        $result = DB::select('select * from tmp_records');
        return response()->json($result, 200);
    }
    public function tree($code = 0, $lang = 'nl') {
        $madr = DB::select('SELECT func_getMadrID(:code) AS id', [ 'code' => $code ]);
        if (count($madr)) {
            $data = DB::select('CALL stp_selectRecursiveLevels(:record_id, :lang, :orderdim, -1, 0)', [
                'record_id' => $madr[0]->id, 'lang' => strpos($lang, '.') !== false ? $lang : 'sys.lang.'.$lang, 'orderdim' => true
            ]);
            $result = $this->__nodes($data);
            return response()->json($result, 200);
        }
        return response()->json([], 200);
    }
    public function recursive($record_id = 0, $lang = 'nl', $orderdim = false, $named = false) {
        $data = DB::select('CALL stp_selectRecursive(:record_id, :lang, :orderdim)', [
            'record_id' => $record_id, 'lang' => strpos($lang, '.') !== false ? $lang : 'sys.lang.'.$lang, 'orderdim' => $orderdim
        ]);
        if ($named) {
            $result = $this->__named($data);
        }else {
            $result = $this->__nodes($data);
        }
        return $result;
    }
    private function __named($data) {
        $result = [];
        $mapped = [ &$result ];
        foreach ($data as $key => $item) {
            $parts = explode('.', $item->tmp_sort_order);
            $count = count($parts);
            if ($count < count($mapped)) {
                $mapped = array_slice($mapped, 0, $count);
            }
            $target = &$mapped[$count - 1];
            $json = isset($item->json) ? json_decode_nice($item->json, true) : [];
            $info = isset($item->info) ? json_decode_nice($item->info, true) : [];
            $madi = $item->tmp_madi;//empty($item->tmp_marl) ? $item->tmp_madi : $item->tmp_marl;
            //$madi = $item->tmp_madi;
            $node = array_replace([
                'id' => $item->tmp_id,
                'dbid' => $item->tmp_dbid,
                'dimension' => $item->dimension,
                'madr' => $item->tmp_madr_id,
                'madi' => $item->tmp_madi,
                'marl' => $item->tmp_marl,
                'marr' => $item->tmp_marr_id,
                'format' => 'named',
                'count' => 0,
                'nodes' => [],
                'lvl' => $item->tmp_level
            ], array_replace_recursive($info ? $info : [], $json ? $json : []));
            if ($key === 0) {
                $node['madi'] = $node['marl'];
                $node['marl'] = $madi;
                $target[$madi] = $node;
                $mapped[] = &$target[$madi];
            }else {
                if (isset($target['nodes']) && !in_array($madi, $target['nodes'])) {
                    $target['nodes'][] = $madi;
                }
                if (!isset($target[$madi])) {
                    $target[$madi] = [];
                }
                $count = array_push($target[$madi], $node);
                $target['count'] += 1;
                $mapped[] = &$target[$madi][$count - 1];
            }
        }
        return $result;
    }
    private function __nodes($data) {
        $result = [ 'nodes' => [] ];
        $mapped = [ &$result ];
        foreach ($data as $item) {
            $parts = explode('.', $item->tmp_sort_order);
            $count = count($parts);
            if ($count < count($mapped)) {
                $mapped = array_slice($mapped, 0, $count);
            }
            $target = &$mapped[$count - 1];
            $json = isset($item->json) ? json_decode_nice($item->json, true) : [];
            $info = isset($item->info) ? json_decode_nice($item->info, true) : [];
            $target['count'] = array_push($target['nodes'], array_replace([
                'id' => $item->tmp_id,
                'dbid' => $item->tmp_dbid,// === $item->tmp_madi ? str_replace('madi', 'mare', $item->tmp_dbid) : $item->tmp_dbid,
                'dimension' => $item->dimension,
                'madr' => $item->tmp_madr_id,
                'madi' => $item->tmp_madi,
                'marl' => $item->tmp_marl,
                'marr' => $item->tmp_marr_id,
                'count' => 0,
                'format' => 'nodes',
                'nodes' => [],
                'lvl' => $item->tmp_level
            ], array_replace_recursive($info ? $info : [], $json ? $json : [])));
            $mapped[] = &$target['nodes'][$target['count'] - 1];
        }
        return $result['nodes'][0];
    }
    private function ___recursive($dim, $attr, $code, $order, $named = false) {
        $result = DB::select('SELECT func_getRecordID(:dim, :attr, :code) AS id', [
            'dim'  => $dim,
            'attr' => $attr,
            'code' => $code
        ]);
        if (count($result) && isset($result[0]->id)) {
            return $this->recursive($result[0]->id, 'nl', $order, $named);
        }else {
            return [];
        }
    }
    private function __recursive($dim, $attr, $code, $order, $named = false) {
        return response()->json($this->___recursive($dim, $attr, $code, $order, $named), 200);
    }
    private function __data($code = '', $lang = 'nl') {
        if (DB::select('CALL stp_selectDimensionRecords(:code, :lang)', [
            'code' => $code, 'lang' => strpos($lang, '.') !== false ? $lang : 'sys.lang.'.$lang
        ]) !== false) {
            $result = DB::select('SELECT * FROM tmp_records ORDER BY madr_desc');
        }else {
            $result = [];
        }
        return $result;
    }
    public function data($code = '', $lang = 'nl') {
        if (strpos($code, '.') === false) {
            return $this->{$code}();
        }else if ($code == 'sys.type.any') {
            return $this->lookup();
        }else {
            return response()->json($this->__data($code, $lang), 200);
        }
    }
    private function __create($code = '', $madi = '', $lang = 'nl') {
        $langid = DB::select('SELECT func_getDimensionID(:lang) AS id', [
            'lang' => strpos($lang, '.') !== false ? $lang : 'sys.lang.'.$lang
        ]);
        $madiid = DB::select('SELECT madi_code FROM main_dimension WHERE madi_id = func_getDimensionID(:code)', [
            'code' => $code
        ]);
        $result = [
            'dbid' => 'mare_0',
            'madi' => $code,
            'madr' => 0,
            'marr' => 0,
            'lnid' => $langid[0]->id,
            'dimension' => $madiid[0]->madi_code
        ];
        if ($madi) {
            $result['marl'] = $madi;
        }
        return [ $code => $result ];
    }
    public function create($code = '', $madi = '', $lang = 'nl') {
        return response()->json($this->__create($code, $madi, $lang), 200);
    }
    /* ========== META ========== */
    public function flush() {
        Cache::forget('meta');
        Cache::forget('menu');
        DB::select('TRUNCATE TABLE sys_record_data');
    }
    private function ___meta($code = '', $lang = 'nl') {
        return DB::select('CALL stp_selectMeta(:code, :lang)', [ 'code' => $code, 'lang' => strpos($lang, '.') !== false ? $lang : 'sys.lang.'.$lang ]);
    }
    private function __meta($code = '', $lang = 'nl') {
        if ($code) {
            $records = $this->___meta($code, $lang);
        }else {
            $records = Cache::get('meta');
            if (!$records) {
                $records = $this->___meta($code, $lang);
                Cache::forever('meta', $records);
            }
        }
        return $records;
    }
    private function __tags() {
        $tags = $this->__data('sys.attr.tag');
        $data = [];
        foreach($tags as $tag) {
            $data['mare_'.$tag->madr_fk_record] = $tag->madr_code;
        }
        return $data;
    }
    private function _meta($code = '', $lang = 'nl') {
        $records = $this->__meta($code, $lang);
        $tagvals = $this->__tags();
        if (count($records)) {
            $result = [];
            foreach($records as $rec) {
                $meta = json_decode_nice($rec->madi_parent_meta, true);
                $item = array_merge($meta, $rec->madi_meta ? json_decode_nice($rec->madi_meta, true) : []);//isset($meta['attr']) ? $meta['attr'] : $meta;
                if (isset($meta['id'])) $item['parent_id'] = 'madi_'.$meta['id'];
                if ($rec->madi_is_relation) {
                    $item['meta']['parent'] = isset($meta['id']) ? 'madi_'.$meta['id'] : '';
                }
                $item['id'] = $rec->madi_id;
                $item['code'] = $rec->madi_code;
                if (!empty($rec->madi_child_dim)) $item['child'] = $rec->madi_child_dim;
                if (false && $code) {
                    $item['madi'] = $code;
                    $target = &$item;
                }else {
                    $item['fields'] = [];
                    $target = &$item['fields'];
                }
                $fields = json_decode_nice($rec->madi_fields, true);
                foreach($fields as $field) {
                    if (isset($field['attr'])) {
                        $attr = $field['attr'];
                        if (isset($attr['tag']) && isset($tagvals[$attr['tag']])) $attr['tag'] = $tagvals[$attr['tag']];
                        $code = explode('.', $attr['code']);
                        if (count($code) > 2) array_shift($code);
                        $type = array_shift($code);
                        $target[$type][array_pop($code)] = [
                            'type' => 'string',
                            'dbid' => 'madi_'.$field['id'],
                            'elem' => array_merge([
                                'tag' => 'input',
                                'type' => 'string',
                                'label' => $attr['code']
                            ], $attr)
                        ];
                    }
                }
                $item['nodes']  = [];
                $nodes = json_decode_nice($rec->madi_nodes, true);
                foreach($nodes as $node) {
                    $item['nodes']['madi_'.$node['id']] = array_merge($node, [
                        'type' => 'schema',
                        'madi' => 'madi_'.$node['marl'],
                        'marl' => 'madi_'.$node['id'],
                        'code' => $node['code']
                    ]);
                }
                if (strpos($rec->madi_code, 'sys.attr') === 0) {
                    $item['options'] = [];
                    $options = json_decode_nice($rec->madi_values, true);
                    if ($options) {
                        foreach($options as $option) {
                            $item['options']['mare_'.$option['id']] = $option;
                        }
                    }
                    $item['nodes']['attrs'] = [ 'type' => 'schema', 'code' => 'attrs' ];
                    $item['nodes']['relas'] = [ 'type' => 'schema', 'code' => 'relas' ];
                    $item['nodes']['rcrds'] = [ 'type' => 'schema', 'code' => 'rcrds' ];
                }
                $result['madi_'.$rec->madi_id] = $item;
            }
        }else {
            $result = [];
        }
        return $result;
    }
    public function meta($code = '', $lang = 'nl') {
        return response()->json($this->_meta($code, $lang), 200);
    }
    /* ========== SYS ========== */
    public function sys_app_endpoint($code) {
        return $this->__recursive('sys.type.endpoint', 'sys.attr.code', $code, false);
    }
    public function sys_app_menu($code) {
        $records = Cache::get('menu');
        if (!$records) {
            $records = $this->___recursive('sys.type.app.item', 'sys.attr.code', $code, false);
            Cache::forever('menu', $records);
        }
        return response()->json($records, 200);
    }
    public function sys_type_entity($code) {
        return $this->sys_app_endpoint($code);
        return $this->__recursive('sys.type.entity', 'sys.attr.code', $code, true);
    }
    public function sys_type_basket($code) {
        //return $this->sys_app_menu('main');
        return $this->__recursive('sys.type.basket', 'sys.attr.code', $code, true);
    }
    private function __single($result) {
      return array_shift($result);
    }
    public function sys_type_load($type, $code) {
      return $this->__recursive($type, 'sys.attr.code', $code, false);
    }
    private function __load($code, $madi = '', $lang = 'nl') {
        if (strpos($code, 'madi') === 0) {
            $item = DB::select('CALL stp_selectDimension(:code, :lang)', [
                'code' => $code, 'lang' => strpos($lang, '.') !== false ? $lang : 'sys.lang.'.$lang
            ]);
            if (count($item)) {
                $item = array_shift($item);
                $json = isset($item->json) ? json_decode_nice($item->json, true) : [];
                $info = isset($item->info) ? json_decode_nice($item->info, true) : [];
                $node = array_merge([
                    'id' => $item->tmp_id,
                    'dbid' => $item->tmp_dbid,
                    'dimension' => $item->dimension,
                    'madr' => $item->tmp_madr_id,
                    'madi' => $item->tmp_madi,
                    'format' => 'named',
                    'nodes' => [],
                    'lvl' => $item->tmp_level
                ], array_replace_recursive($info ? $info : [], $json ? $json : []));
                if ($madi) {
                    $node['madi'] = $madi;
                }else if ($node['madi'] === 'madi' || strpos($node['dimension'], 'sys.attr') === 0) {
                    $node['nodes'] = [ 'attrs', 'relas', 'rcrds', 'values' ];
                    $records = DB::select('CALL stp_selectMeta(:code, :lang)', [
                        'code' => $code, 'lang' => strpos($lang, '.') !== false ? $lang : 'sys.lang.'.$lang
                    ]);
                    if (count($records)) {
                        $records = array_shift($records);
                        $attrs = json_decode_nice($records->madi_fields, true);
                        $node['attrs'] = [];
                        foreach($attrs as &$attr) {
                            if (isset($attr['id'])) {
                                $attr['madi'] = 'madi_'.$attr['id'];
                                $attr['marl'] = 'attrs';
                                $attr['lvl'] = 1;
                                $attr['dbid'] = 'mare_'.$attr['id'];
                                $attr['madr'] = 0;
                                $node['attrs'][] = $attr;
                            }
                        }
                        $relas = json_decode_nice($records->madi_nodes, true);
                        $node['relas'] = [];
                        foreach($relas as &$rela) {
                            if (isset($rela['id']) && isset($rela['code'])) {
                                $rela['attr'] = [ 'code' => $rela['code'] ];
                                $rela['lnid'] = $node['lnid'];
                                $rela['madi'] = 'madi_'.$rela['id'];
                                $rela['marl'] = 'relas';
                                $rela['lvl'] = 1;
                                $rela['dbid'] = 'mare_'.$rela['id'];
                                $rela['marl'] = ''; // 'madi_'.$rela['id'];
                                $rela['madr'] = 0;
                                $node['relas'][] = $rela;
                            }
                        }
                        $node['values'] = [];
                    }else {
                        $node['attrs']  = [];
                        $node['relas']  = [];
                        $node['values'] = [];
                    }
                    $rcrds = DB::select('CALL stp_selectRecursiveLevels(:code, :lang, FALSE, 0, 0)', [
                        'code' => $code, 'lang' => strpos($lang, '.') !== false ? $lang : 'sys.lang.'.$lang
                    ]);
                    $node['rcrds'] = [];
                    foreach($rcrds as $item) {
                        if ($item->json) {
                            $rcrd = array_replace_recursive(json_decode_nice($item->json, true), json_decode_nice($item->info, true));
                            $rcrd['madi'] = $item->tmp_madi;
                            $rcrd['marl'] = 'rcrds';
                            $rcrd['lvl'] = 1;
                            $rcrd['dbid'] = $item->tmp_dbid;
                            $rcrd['madr'] = $item->tmp_madr_id;
                            $rcrd['marr'] = $item->tmp_marr_id;
                            $rcrd['type'] = $item->dimension;
                            $node['rcrds'][] = $rcrd;
                        }
                    }
                }
                // $node['dbid'] = str_replace('madi_', 'mare_', $code);
                return [ $code => $node ];
            }
        }else if (strpos($code, 'create') === 0) {
            return $this->__create(str_replace('create', 'madi', $code), $madi, $lang);
        }else {
            $madr = DB::select('SELECT func_getMadrID(:code) AS id', [ 'code' => $code ]);
            if (count($madr)) {
                if ($madi) {
                    $result = $this->__named(DB::select('CALL stp_selectRecursiveLevels(:madr, :lang, FALSE, 0, 0)', [
                        'madr' => $madr[0]->id, 'lang' => strpos($lang, '.') !== false ? $lang : 'sys.lang.'.$lang
                    ]));
                }else {
                    $result = $this->recursive($madr[0]->id, $lang, false, true);
                }
                $result = strpos($code, 'madr') === 0 ? $this->__single($result) : $result;
                if ($madi) {
                    $result['marl'] = $result['madi'];
                    $result['madi'] = $madi;
                }
                return $result;
            }
        }
        return [];
    }
    public function sys_load($code, $madi = '', $lang = 'nl') {
        if (strpos($code, 'mare') === 0 && !empty(DB::selectOne('SELECT SIGN(COALESCE((SELECT madi_id FROM main_dimension WHERE madi_id = :id), 0)) AS madi_id', [
            'id' => str_replace('mare_', '', $code)
        ])->madi_id)) {
            $result = $this->__load(str_replace('mare_', 'madi_', $code), $madi, $lang);
            $result = array_shift($result);
            $result['dbid'] = $code;
            return response()->json([ $result['madi'] => $result ], 200);
        }else {
            return response()->json($this->__load($code, $madi, $lang), 200);
        }
    }
    private function ___save($info, $data) {
        if (isset($data['dbid'])) $info['dbid'] = strpos($data['dbid'], 'create') === 0 ? 0 : substr($data['dbid'], strpos($data['dbid'], '_') + 1);
        if (isset($data['lnid'])) $info['lnid'] = $data['lnid'];
        if (isset($data['marl'])) $info['madi'] = $data['marl'];
        else if (isset($data['madi'])) $info['madi'] = $data['madi'];

        if (empty($data['madr'])) {
            if (DB::select('CALL stp_upsertDimensionRecord(:dimension, :code, @rec_id)', [
                'dimension' => $data['marl'],
                'code' => isset($data['attr']['code']) ? $data['attr']['code'] : (isset($data['code']) ? $data['code'] : '')
            ]) !== false) {
                $val = DB::selectOne('select
                    madr_id as id, mava_value as code, madr_fk_record as rec_id
                    from main_dimension_record
                        join main_record_value
                            on mava_fk_record = madr_fk_record
                           and mava_fk_dimension = func_getDimensionID(:code)
                           and mava_fk_language = 1
                    where madr_id = @rec_id', [ 'code' => 'sys.attr.code' ]);
                if ($val) {
                    $data['madr'] = $val->id;
                    if (isset($data['attr'])) $data['attr']['code'] = $val->code;
                }
            }
        }
        if (empty($data['marr']) && !empty($data['madr'])) {
            // func_getRelationID(:madi_p, :madi_c)
            if (DB::select('CALL stp_upsertRelationRecord(func_getDimensionID(:madi), func_getMadrID(:parent), :child, :order, @rel_id)', [
                //'madi_p' => $info['parent_madi'], 'madi_c' => $info['madi'],
                'madi' => $data['madi'],
                'parent' => $info['parent_id'], 'child' => $data['madr'], 'order' => 0
            ]) !== false) {
                $rcrd = DB::selectOne('select madr_id, madr_fk_record as marr_id from main_dimension_record where madr_fk_record = @rel_id');
                if ($rcrd && isset($rcrd->madr_id)) {
                    $data['madr'] = $rcrd->madr_id;
                    $data['marr'] = $rcrd->marr_id;
                    $data['dbid'] = 'mare_'.$rcrd->marr_id;
                }
            }
        }
        return $data;
    }
    private function __save($info, $data) {
        if (isset($data['dbid'])) $info['dbid'] = substr($data['dbid'], strpos($data['dbid'], '_') + 1);
        if (isset($data['lnid'])) $info['lnid'] = $data['lnid'];
        else if (!isset($info['lnid'])) $info['lnid'] = 1;
        if (!isset($info['madi']) && isset($data['madi'])) $info['madi'] = $data['madi'];

        if (isset($data['nodes'])) {
            foreach($data['nodes'] as $madi) {
                if (strpos($madi, 'madi_') === 0) {

                    $base = [ 'madi' => $madi, 'parent_madi' => $info['madi'], 'parent_id' => $data['madr'] ];
                    foreach($data[$madi] as $key => &$node) {
                        if (isset($node['dbid']) && strpos($node['dbid'], 'create') === 0) {
                            $node['jsid'] = $key;
                        }
                    }
                    $data[$madi] = array_reduce(

                        $data[$madi],

                        function($result, $node) use ($base) {

                            if (isset($node['marl'])) {

                                $node = $this->___save($base, $node);
                                if (isset($node['jsid'])) {
                                    $this->addToMap($node['jsid'], $node['dbid']);
                                }
                                $node = $this->__save([ 'parent_madi' => $node['madi'], 'parent_id' => $node['madr'] ], $node);
                                $result[$node['dbid']] = $node;

                            }
                            return $result;

                        }, []);

                }else if ($madi === 'attrs') {
                    foreach ($data[$madi] as $key => $value) {
                        DB::select('INSERT INTO main_dimension_record
                            SELECT
                              0 as madr_id,
                              mare.madi_id AS madr_fk_dimension,
                              madi.madi_id AS madr_fk_record,
                              0 AS madr_order
                            FROM main_dimension AS madi
                                JOIN main_dimension AS mare ON mare.madi_id = func_getDimensionID(:rec_id)
                            WHERE madi.madi_id = func_getDimensionID(:madi_id)
                              AND NOT EXISTS
                                (SELECT 1 FROM main_dimension_record
                                    WHERE madr_fk_dimension = mare.madi_id -- IN (mare.madi_id, mare.madi_fk_parent)
                                      AND madr_fk_record = madi.madi_id
                        )', [
                            'rec_id' => $info['dbid'], 'madi_id' => $value['dbid']
                        ]);
                    }
                }else if ($madi === 'relas') {
                    if (isset($data['attr']['code'])) {
                        foreach ($data[$madi] as $key => $value) {
                            if (isset($value['attr']['code'])) {
                                DB::select('CALL stp_upsertRelation(:parent, :child, @rela_id)', [
                                    'parent' => $data['attr']['code'], 'child' => $value['attr']['code']
                                ]);
                            }
                        }
                    }
                }else if ($madi === 'rcrds') {
                    if (isset($data['attr']['code'])) {
                        foreach ($data[$madi] as $key => $value) {
                            if (empty($value['madr']) && isset($value['attr']['code'])) {
                                DB::select('CALL stp_upsertDimensionRecord(:madi, :code, @rec_id)', [
                                    'madi' => $data['attr']['code'], 'code' => $value['attr']['code']
                                ]);
                                $rcrd = DB::select('select @rec_id as id');
                                if (count($rcrd)) {
                                    $value['madr'] = $rcrd[0]->id;
                                }
                            }
                            if (!empty($value['madr'])) {
                                $rcrd = DB::selectOne('select madr_fk_record as id from main_dimension_record where madr_id = :madr', [
                                    'madr' => $value['madr']
                                ]);
                                $this->__save(
                                    [ 'dbid' => $rcrd->id, 'lnid' => $info['lnid'], 'madi' => $data['attr']['code'] ],
                                    array_merge($value, [ 'nodes' => [ 'attr' ] ])
                                );
                            }
                        }
                    }
                }else if ($madi === 'remove') {
                    foreach ($data[$madi] as $key => $value) {
                        if (isset($value['madi'])) {
                            if ($value['madi'] == 'attrs') {
                                DB::select('DELETE FROM main_dimension_record
                                    WHERE madr_fk_record = func_getDimensionID(:madi_id)
                                      AND madr_fk_dimension = func_getDimensionID(:rec_id)', [
                                    'rec_id' => $info['dbid'], 'madi_id' => $value['dbid']
                                ]);
                            }else if ($value['madi'] == 'relas') {
                                if (isset($value['attr']['code'])) {
                                    DB::select('CALL stp_deleteDimension(func_getRelationID(:parent, :child), FALSE, FALSE)', [
                                        'parent' => $data['attr']['code'], 'child' => $value['attr']['code']
                                    ]);
                                }
                            }else if (strpos($value['madi'], 'madi_') === 0) {
                                if (!empty($value['marr'])) {
                                    DB::select('CALL stp_removeRelationRecord(:marr)', [
                                        'marr' => $value['marr']
                                    ]);
                                }
                            }else if ($value['madi'] == 'rcrds') {
                                if (!empty($value['madr'])) {
                                    DB::select('CALL stp_removeDimensionRecord(:madr)', [
                                        'madr' => $value['madr']
                                    ]);
                                }
                            }
                        }
                    }
                }else if (strpos($madi, 'create_') === 0) {
                    // Should be saved from somewhere else
                }else {
                    foreach ($data[$madi] as $key => $value) {
                        if (!empty($data['madr'])) {
                            DB::select('CALL stp_upsertDimensionRecordValue(:record_id, :dimension, :language, :value, :lookup, @val_id)', [
                                'record_id' => $data['madr'],
                                'dimension' => 'sys.'.$madi.'.'.$key,
                                'language'  => $info['lnid'],
                                'value'     => $value,
                                'lookup'    => strpos($value, 'mare_') === 0
                            ]);
                        }else {
                            DB::select('CALL stp_upsertRecordValue(:record_id, :dimension, :language, :value, :lookup, @val_id)', [
                                'record_id' => $info['dbid'], 'dimension' => 'sys.'.$madi.'.'.$key, 'language' => $info['lnid'], 'value' => $value, 'lookup' => false
                            ]);
                        }
                    }
                }
            }
        }
        return $data;
    }
    private function __createDimension($data) {
        $part = explode('.', $data['attr']['code']);
        $code = array_pop($part);
        $madi = implode('.', $part);
        if (DB::select('CALL stp_upsertDimension(:madi, :code, @rec_id)', [
            'madi' => $madi, 'code' => $code
        ]) !== false) {
            $node = DB::select('select @rec_id as id');
            if (count($node)) {
                return 'madi_'.$node[0]->id;
            }
        }
        return false;
    }
    private function __createDimensionRecord($data) {
        if (DB::select('CALL stp_upsertDimensionRecord(:madi, :code, @rec_id)', [
            'madi' => $data['madi'], 'code' => ''
        ]) !== false) {
            $rcrd = DB::select('select madr_fk_record as rec_id, madr_id from main_dimension_record where madr_id = @rec_id');
            if (count($rcrd)) {
                return [ 'dbid' => 'mare_'.$rcrd[0]->rec_id, 'madr' => $rcrd[0]->madr_id ];
            }
        }
        return false;
    }
    private function addToMap($from, $to) {
        $this->idMap[$from] = $to;
    }
    private function __do_save($data) {
        $this->idMap = [];

        $meta = $data['dbid'] === 'madi_1' ? true : false;
        if ($meta) {
            $dbid = $this->__createDimension($data);
            if ($dbid) {
                $data['dbid'] = $dbid;
            }
        }else if ($data['dbid'] === 'mare_0') {
            $dbid = $this->__createDimensionRecord($data);
            if ($dbid) {
                $data = array_merge($data, $dbid);
            }
        }
        if (isset($data['dbid'])) {
            $meta = $meta || (isset($data['madi']) && $data['madi'] === 'madi');
            $data = $this->__save([ 'parent_id' => 0 ], $data);
            if ($meta) {
                $this->flush();
                return [ 'dbid' => $data['dbid'], 'meta' => $this->_meta($data['dbid']) ];
            }else if ((isset($data['type']) && strpos($data['type'], 'sys.type.app') !== false)) {
                Cache::forget('menu');
            }
            if (isset($data['madr'])) {
                return $this->__load('madr_'.$data['madr']);
            }else {
                return $this->__load($data['dbid']);
            }
        }else {
            return $data;
        }
    }
    public function sys_save() {
        if (DB::selectOne('CALL stp_startTransaction(@tran_id)') !== false) {
            $tran = DB::selectOne('SELECT @tran_id AS tran_id');
            $result = $this->__do_save(Input::all());
            $result['idmap'] = $this->idMap;
            DB::select('CALL stp_commitTransaction(:tran_id)', [ 'tran_id' => $tran->tran_id ]);
            return response()->json($result, 200);
        }
    }   
    private function __delete($data) {
        if (!empty($data['marr'])) {
            DB::select('CALL stp_removeRelationRecord(:marr)', [
                'marr' => $data['marr']
            ]);
        }else if (!empty($data['madr'])) {
            DB::select('CALL stp_removeDimensionRecord(:madr)', [
                'madr' => $data['madr']
            ]);
        }else {
            DB::select('CALL stp_deleteDimension(:dimension, FALSE, FALSE)', [
                'dimension' => $data['dbid']
            ]);
        }
        return $data;
    }
    public function sys_delete() {
        $data = Input::all();
        if (isset($data['dbid'])) {
            if (isset($data['madi']) && $data['madi'] === 'madi') {
                $this->flush('meta');
            }
            if (DB::select('CALL stp_startTransaction(@tran_id)') !== false) {
                $tran = DB::selectOne('select @tran_id as tran_id');
                $result = $this->__delete($data);
                DB::select('CALL stp_commitTransaction(:tran_id)', [ 'tran_id' => $tran->tran_id ]);
                return response()->json($result, 200);
            }
        }else {
            return response()->json($data, 200);
        }
    }
}
