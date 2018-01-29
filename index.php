<?php
/**
 * 数独是 一个数列(甚至是符号集合) 填满 行列块 而 不重复 
 */
class Sudoku{
    // means num range is 1~n;
    public $max = 9;

    // width and height for whole
    public $w = null;

    // width and height for unit
    public $u = null;

    // numbers collect for each view
    public $s = null;

    // number counts for all
    public $c = null;

    // Qus
    public $Q = [];

    // Matrix
    public $m = [];

    public $I = []; // id 维度

    public $R = []; // row 

    public $C = []; // column

    public $B = []; // block

    public function __construct(Array $Qus = []){
        if(!empty($Qus)) $this->loadQues($Qus);
    }

    // 可以实例化自身
    public static function Qus(Array $Qus){
        return new Sudoku($Qus);
    }

    // 构建初始状态 - 如参为已知的题目，未考虑题目出错的情况
    public function loadQues(Array $Qus){
        $this->Q = $Qus;                // 内置属性
        $this->c = $c = count($Qus);    // 数字个数总计
        $this->w = $w = sqrt($c);       // 整边长
        $this->u = $u = sqrt($w);       // 块边长

        if(floor($u) != $u) throw new Exception('Wrong Question!');

        for($i = 0; $i < $w; $i++){ $s[$i+1] = $i+1; } // 构建每个位置可能的数字集合, 键值对应相同，方便排除

        $m = [];
        for($i = 0; $i < $c; $i++){ 
            // Matrix bind
            // $n = $s;
            $n = is_int($Qus[$i]) ? $Qus[$i] : $s;                             // 该位置的数字
            $p = array_merge(['n' => $n], self::getInfoById($i, $w, $u));      // 所有相关信息

            $m['I'][$p['i']] = $p; // 整体 id 绑定
            $m['R'][$p['r']][$p['c']]  = &$m['I'][$p['i']];
            $m['C'][$p['c']][$p['r']]  = &$m['I'][$p['i']];
            $m['B'][$p['b']][$p['bi']] = &$m['I'][$p['i']];            
        }
        $this->m = $m;
        // $this->I = $m['I'];
        // $this->R = $m['R'];
        // $this->C = $m['C'];
        // $this->B = $m['B'];
    }

    // id begin from 0
    public static function getInfoById($id, $w = null, $u = null){
        $i = $id; // 换个名字

        // whole
        $r = bcdiv($i, $w) + 1; // how many in/have => row
        $c = bcmod($i, $w) + 1; // how many left    => column
        // block
        $b = bcdiv($r - 1, $u) * $u + bcdiv($c - 1, $u) + 1; // Block
        // info in block
        $br = bcmod($r - 1, $u) + 1;
        $bc = bcmod($c - 1, $u) + 1;
        $bi = ($br - 1) * $u + $bc - 1; // 统一全局 id 与块 id 都是从 0 开始的

        $info = [
            'r' => $r,
            'c' => $c,
            'i' => $i,
            'b' => $b,
            'br' => $br,
            'bc' => $bc,
            'bi' => $bi,
            'e'  => false, // 标识位，是否触发过维度排除
        ];

        return $info;
    }

    // solve the question
    public function solve(){
        foreach($this->m['I'] as $i => &$p){
            // if(is_array($p['n']) || $p['e']) continue; // 如若是数组 或 已经触发过的数字，则不能触发维度排除
            $this->setNumber($p);
        }
        $this->m['I'] = array_map(function($p){
            return $p['n'];
        }, $this->m['I']);

        return $this->m['I'];
    }

    public function setNumber(&$p, $l = 0){
        if(is_array($p['n']) || $p['e']) return; // 如若是数组 或 已经触发过的数字，则不能触发维度排除

        $str = "第 {$l} 层，由第 {$p['r']} 行第 {$p['c']} 列位于第 {$p['b']} 块的ID为 {$p['i']} 的数字 {$p['n']} 发起的独立！\r\n";
        // echo str_repeat('-', $l).'>'. $str.'</br>';
        $p['e'] = true; // 开始前先修改标识，防止在递归中死循环

        // 需要充分思考互斥的可能

        // 由多减少思维，从每一格视角，由已经确定的格，排除掉同行列块的其他格的候选值可能性，若只剩下一种可能，则可以设置此格内的值为确定值。但是作为解题用描述还不够。
        // 当一个数字确定时，同行列块的其他位置不可能为该数字 // 只是第一层解题逻辑，完全不够解决普通数独问题
        $this->rcbDel($p, $l);

        // 整体到局部的定值
        // 单维度视角：如果这个纬度中这个值只有一个可能性，那么就是它! .1 统计纬度可能值 .2 确立所有唯一的可能
        $this->rcbCt($l);
        // 确定了某个值只可能在某个单元内，某行 列 ，则该行列的其他单元格子内的该值都应该排除

    }

    public function rcbDel(Array $p, $l){
        $this->sdDel($this->m['R'][$p['r']], $p['n'], $l); // 行
        $this->sdDel($this->m['C'][$p['c']], $p['n'], $l); // 列
        $this->sdDel($this->m['B'][$p['b']], $p['n'], $l); // 块
    }

    public function sdDel(Array &$rcb, $n, $l){ // Single dimension 单维度排除可能值
        foreach($rcb as $i => &$p){ // i 为维度中成员编号 行成员编号等于列号 。。。 | 三个维度
            // 单格视角：如果这个格子内只有这一个可能，那么这个格子内就一定是这个值!
            if(is_array($p['n'])){
                if(in_array($n, $p['n'])){
                    unset($p['n'][$n]);
                    if(count($p['n']) == 1){  // left only one
                        $p['n'] = array_shift($p['n']);
                        $this->setNumber($p, ++$l);
                    }
                }
            }
        }
    }

    public function rcbCt($l){
        // 1. 用作统计 2. 确定 i 以根据统计来定位
        $this->sdCt($this->m['R'], 'R', $l);
        $this->sdCt($this->m['C'], 'C', $l);
        $this->sdCt($this->m['B'], 'B', $l);
    }

    // 单维度 分析
    public function sdCt(Array &$rcbs, $d, $l){
        foreach($rcbs as $wi => &$rcb){ // 单维度视角遍历分析  i 为行号 列号 或者 块号
            $ns = $ni = $setNum = [];
            foreach($rcb as $i => &$p){
                if(!is_array($p['n'])) {
                    $setNum[] = $p['n'];
                    // $this->setNumber($p, ++$l); // 此处开不开不影响
                    continue; // 已经确定的跳过
                }

                $ns = array_merge($ns, $p['n']); // 放在一起准备统计
                foreach($p['n'] as $n){
                    $ni[$n][] = $i;
                } 
                // 其实一个数出现的多个位置也可以用于次数的计算
            }
            // $ct = array_count_values($ns); // 一个函数直接计算中出现次数
            foreach($ni as $n => $i){
                $ic = count($i); // may be id counts
                if($ic == 1 && !in_array($n, $setNum)) {
                    // $ds = ['R' => '行', 'C'=> '列', 'B'=>'块'];
                    // $str = "第 {$l} 层，尝试从 {$ds[$d]} 维度计数定值，当前第 {$wi} {$ds[$d]} 数值 {$n} 或有结果 \r\n";
                    // $str .= "已经有的数字为：".implode(',', $setNum).' 可定值位置为：'.implode(',', $i);
                    // echo str_repeat('-', $l).'>'. $str.'</br>';
                    $di = array_shift($i);
                    $rcb[$di]['n'] = $n;
                    $this->setNumber($rcb[$di], ++$l);
                }
            }
        }
    }

    public static function ShowPage($Q = [], $A = []){

        $TB_Q = empty($Q) ? '' : self::tbCode($Q);
        $TB_A = empty($Q) ? '' : self::tbCode($A);

        $HTML = <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta http-equiv="X-UA-Compatible" content="ie=edge">
            <title>Document</title>
            <style>
                .group {
                    width: 100%;
                    overflow: hidden;
                    margin-top: 10px;
                }
                .tb_box {
                    float: left;
                    margin: 5px;
                }

                table {
                    border: 2px black solid;
                    border-collapse: collapse;  
                    border-spacing: 0;  
                }
                td {
                    width: 45px;
                    height: 45px;
                    /* display: block; */
                    /* float: left; */
                    text-align:center;
                    valign: middle;
                    border: 1px rgba(146, 158, 158, 0.933) solid;
                }

                .t { border-top:    2px grey solid; }
                .r { border-right:  2px grey solid; }
                .b { border-bottom: 2px grey solid; }
                .l { border-left:   2px grey solid; }

                td i {
                    margin: -1px;
                    padding: 0;                    
                    width: 15px;
                    height: 15px;
                    display: block;
                    float: left;
                    border: 1px #eee solid;
                    color: red;
                    font-size: 13px;
                }
                td span {
                    font-size: 32px;
                }


            </style>
        </head>
        <body>
            <header><h1>Sudoku</h1></header>
            <div class="group">
                <div class="tb_box">{$TB_Q}</div>    
                <div class="tb_box">{$TB_A}</div>    
            </div>
        </body>
        </html>
HTML;
        echo $HTML;

    }

    // 展示
    public static function tbCode(Array $m){
        $c = count($m);     // 获取矩阵数字总计
        $w = sqrt($c);      // 整边长
        $u = sqrt($w);      // 块边长

        $table = '<table>';
        for($i = 0; $i < $c; $i++){
            // Matrix // 所有相关信息 $delimiter
            $p = array_merge(['n' => $m[$i]], self::getInfoById($i, $w, $u));
            
            if(is_null($m[$i])){
                $cell = '';
            }elseif(is_array($m[$i])){
                $cell = '';
                for($j = 0; $j < $w; $j++ ){
                    $n = $j+1;
                    $cell .= in_array($n, $m[$i]) ? '<i>'.$n.'</i>' : '<i></i>';
                }
            }else{
                $cell = '<span>'.$m[$i].'</span>';
            }

            if($p['c'] == 1) $table .= '<tr>';

            $tc = [];
            if($p['bc'] == 1 && $p['c'] !== 1) $tc[] = 'l';      // 块左边
            if(!($p['bc'] % $u) && $p['c'] % $w) $tc[] = 'r';   // 块右边
            if($p['br'] == 1 && $p['r'] !== 1) $tc[] = 't';      // 块上边
            if(!($p['br'] % $u) && $p['r'] % $w) $tc[] = 'b';   // 块下边

            $tc = implode(' ', $tc);
            
            // 边线 上 右 下 左
            $table .= '<td class="'.$tc.'">'.$cell.'</td>';

            if(!($p['c'] % $w)) $table .= '</tr>';
            // echo $table;    die('QQQQQQ');
        }   
        $table .= '</table>';

        return $table;
    }

}


// 数独题目
$Q = [
    5   , null, null,  null, 7   , null,  8   , null, null,
    null, null, null,  3   , 4   , null,  null, null, 9   ,
    null, 6   , null,  5   , null, null,  7   , null, null,

    null, 8   , null,  null, 6   , null,  null, null, 7   ,
    3   , 9   , null,  null, null, null,  null, 8   , 1   ,
    6   , null, null,  null, 8   , null,  null, 2   , null,

    null, null, 3   ,  null, null, 7   ,  null, 1   , null,  
    4   , null, null,  null, 3   , 5   ,  null, null, null,
    null, null, 9   ,  null, 2   , null,  null, null, 5   ,
];


try{
    $A = Sudoku::Qus($Q)->solve();
    Sudoku::ShowPage($Q, $A);
}catch(Exception $e){
    var_dump($e);
    die('DIE');
}