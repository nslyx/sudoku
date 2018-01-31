<?php
/**
 * 数独是 一个数列(甚至是符号集合) 填满 行列块 而 不重复 
 */
class Sudoku{
    // Number Range  1~l;
    // public $l = 9;

    // Cell Counts
    public $c = null;

    // Whole [Rows | Columns | Block]
    public $w = null;

    // Block [Rows | Columns]
    public $u = null;

    // Single Cell Number[s]
    public $s = null;

    // Qus
    public $Q = [];

    // Matrix
    public $M = [];

    /**
     * 构建即更新出数列状态 - 能确定的都确定下来，不能确定的有剩余的可能
     * @param Mixed $Q Int whole width OR Array sn
     * @return Array $sn 最新状态的数列
     */
    public function __construct(){
    }

    /**
     * 依据入参，分析规格
     * @param   Mixed $Q  已有数列的状态 OR 已知数列数长， 默认数长 9
     * @return  Array $spec 总计多少阵位，整体边长，块边长
     */
    public static function spec($Q = 9){
        $c = $w = $u = 0; // 声明
        if(is_int($Q))   $c = pow($Q, 2);    // 矩阵位
        if(is_array($Q)) $c = count($Q);     // 矩阵位
        $w = sqrt($c);      // 整边长
        $u = sqrt($w);      // 块边长
        // 校验是否能构成含块级矩阵 - 4, 9, ...
        if( in_array($u, [0, 1]) || (floor($u) != $u) ) throw new Exception('Wrong Spec!'); 

        $spec = [
            'c' => $c,  // Counts All
            'w' => $w,  // Whole Width
            'u' => $u,  // Unit Width
        ];

        return $spec;
    }

    /**
     * Create an empty SN base on Counts of whole
     * @param   Int   $c    Counts
     * @return  Array $sn   An Empty sn
     */
    public static function EmptySn($c){
        $sn = [];
        for($i = 0; $i < $c; $i++){ $sn[] = null; }

        return $sn;
    }

    /**
     * Filter SN which alrready exists 主要是 待定 cell 表示方式统一为 null
     * @param   Array $sn   SN array
     * @return  Array $sn   An Filtered SN array
     */
    public static function FilterSn(Array $sn){
        return array_map(function($n){
            $n = is_int($n) ? (String) $n : $n;
            if(is_string($n)){
                // NULL 在数列中的表示方式
                $n = in_array(strtoupper($n), ['NULL', '', '-']) ? null : $n;
                $n = preg_match('/^\d+$/', $n) ? (int) $n : null; // 非数字转化为 Null
            }else{
                $n = null;
            }

            return $n;
        }, $sn);
    }

    // Matrix to sn
    public static function MatrixSn(Array $matrix){
        return array_column($matrix['I'], 'n');
    }

    public static function CellPv($w){ // Possible value
        for($i = 0; $i < $w; $i++){ $s[$i+1] = $i+1; } // 构建每个位置可能的数字集合, 键值对应相同，方便排除
        
        return $s;
    }


    /**
     * Cell Info
     * @param     Int     $id     Global id
     * @param     Int     $w      Global width
     * @param     Int     $u      Unit width
     * @return    Array   $cell   Cell Info
     */
    public static function CellInfo($id, $w, $u = null){
        if(is_null($u)) $u = sqrt($w); // 此处不考虑数据对错问题

        // Whole & Block
        $r = bcdiv($id, $w) + 1; // how many in/have => row
        $c = bcmod($id, $w) + 1; // how many left    => column
        $b = bcdiv($r - 1, $u) * $u + bcdiv($c - 1, $u) + 1; // Block
        $br = bcmod($r - 1, $u) + 1;
        $bc = bcmod($c - 1, $u) + 1;
        $bi = ($br - 1) * $u + $bc; // 不能与全局统一 要与维度统一
        // $bb = $bi + 1; // 块中的块 <=> 单元格

        $cell = [
            // 'n' => null,// For Number

            'i' => $id, // 0+ Whole Id
            'r' => $r,  // 1+ Whole Line
            'c' => $c,  // 1+ Whole Column
            'b' => $b,  // 1+ Whole Block

            'bi' => $bi,  // 1+ Block Id
            'br' => $br,  // 1+ Block Line
            'bc' => $bc,  // 1+ Block Column
            // 'bb' => $bb,  // 0+ Block Block

            'confirmId' => 0, // 是否已经确认, 已经确认的有确认次序的 id
        ];

        return $cell;
    }

    /** 
     * 构建矩阵关系 - 如参为已知的题目，未考虑题目出错的情况
     * @param Array $spec 依据规格构建原始矩阵关系 并填入初始可能值
     * @return Array $matrix 初始矩阵关系
     */
    public static function Matrix(Array $spec){
        // $spec = is_empty($spec) ? self::spec($sn) : $spec; // 默认处理
        $cpv = self::CellPv($spec['w']); // 每个为空的格子内的最初全部的可能值

        // Matrix 数组容器
        $m = [
            'spec' => $spec,
            'confirmedNum' => 0, // 已经确认 cell 的计数
        ]; 

        // 单维度
        for($i = 0; $i < $spec['w']; $i++){
            $rcb = $i + 1;
            $m['D']['R'][$rcb] = $m['D']['C'][$rcb] = $m['D']['B'][$rcb] = $cpv; // 行列块 维度剩余的可填数字
        }
        
        // 矩阵关系
        for($i = 0; $i < $spec['c']; $i++){
            // $cn = is_null($sn[$i]) ? $cpv : $sn[$i]; // cell number
            $cell = array_merge(['n' => $cpv], self::CellInfo($i, $spec['w'], $spec['u'])); // 所有相关信息
            // Position by id, Default gives all possibilities
            $m['I'][$cell['i']] = $cell; // Id bind
            // Entrance bind, 利用引用将 单元格 在全局行列块的 入口 维度 指向 该单元格
            $m['R'][$cell['r']][$cell['c']]  = &$m['I'][$cell['i']]; // 行
            $m['C'][$cell['c']][$cell['r']]  = &$m['I'][$cell['i']]; // 列
            $m['B'][$cell['b']][$cell['bi']] = &$m['I'][$cell['i']]; // 块
            // 可增加单元格在块中的行列信息绑定
        }

        return $m;
    }

    // solve the question
    public static function Solve($Q = 9){
        $spec = self::spec($Q); // 计算规格

        $sn = is_array($Q) ? self::FilterSn($Q) : self::EmptySn($spec['c']); // 

        $matrix = self::Matrix($spec); // 构建初始矩阵关系

        foreach($sn as $i => $n){
            if(is_null($n)) continue; // 空就跳过
            self::CellConfirm($i, $n, $matrix);
        }

        return $matrix;
    }

    /**
     * 确定一个格子的值 
     */
    public static function CellConfirm($i, $n, &$matrix, $l = 0){
        $m = &$matrix; // 别名
        $cell = &$m['I'][$i];
        
        if($cell['confirmId']) return;          // 不再确认已经确认过的格子
        $m['confirmedNum']++;
        $cell['confirmId'] = $m['confirmedNum'];   //  确认的第一步锁定已经走了确定步骤

        // 1. 设置这个 Cell 的值
        $cell['n'] = $n;

        // 2. 减少当前 Cell 相关维度 可填值
        unset($m['D']['R'][$cell['r']][$n]);
        unset($m['D']['C'][$cell['c']][$n]);
        unset($m['D']['B'][$cell['b']][$n]);
        
        // 3. 排除当前 Cell 相关维度 其他 Cell 可选值
        self::CellPvDel($n, $m['R'][$cell['r']], $l, $m);
        self::CellPvDel($n, $m['C'][$cell['c']], $l, $m);
        self::CellPvDel($n, $m['B'][$cell['b']], $l, $m);

        // 4. Chain Confirm - 连锁确认 Cell
        self::ChainConfirm($m, $l);
    }

    // 排除同维度其他 待定 Cell 的可能值
    public static function CellPvDel($n, &$rcb, $l, &$matrix){
        // i 为维度中成员编号 行成员编号等于列号 。。。 | 三个维度
        foreach($rcb as $i => &$cell){
            if(!is_array($cell['n']) || !in_array($n, $cell['n'])) continue; // 单格视角：如果这个格子内只有这一个可能，那么这个格子内就一定是这个值!
            unset($cell['n'][$n]); // 排除这个可能的数字
            if(count($cell['n']) == 1){ // 唯一的可能 即为 确定  分开描述
                $cn = array_shift($cell['n']);
                self::CellConfirm($cell['i'], $cn, $matrix, ++$l);
            }
        }
    }

    public static function DimensionAna(Array &$rcbs, $l, &$matrix, $d){
        // 若全部确认了，则无需再继续整体尝试确认
        if(self::HasDone($matrix)) return;

        $Ds = [ 'R' => '行', 'C' => '列', 'B' => '块'];
        // 单维度透视 行 列 块 遍历分析  i 为行号 列号 或者 块号
        foreach($rcbs as $i => &$rcb){
            $Confirmed = $Vids =[]; // 该维度内待定值 的可能的 维度内 value psb id in Dimension
            foreach($rcb as $di => &$cell){ // 维度内 id => 维度内 cell
                if(!is_array($cell['n'])) { $Confirmed[] = $cell['n']; continue;} // 已确定的跳过
                // 一个数出现的多个位置也可以用于次数的计算，数字可能的位置
                foreach( $cell['n'] as $n ){ $Vids[$n][] = $di; }
            }
            unset($cell); // 传的引用手动断链

            // array_count_values($arr) 可以统计出现次数
            foreach($Vids as $n => $ids){
                if( count($ids) == 1 && !in_array($n, $Confirmed)){
                    // $str  = "{$l}层[{$Ds[$d]}],已有[" . implode(',', $Confirmed). "],可确定[{$n}], 在{$Ds[$d]}内第".implode(',', $ids)."格";
                    // echo str_repeat('-', $l).'>'. $str.'</br>';
                    $di = array_shift($ids);
                    self::CellConfirm($rcb[$di]['i'], $n, $matrix, ++$l);
                }
            }
        }
    }

    // 整体上检查确认
    public static function ChainConfirm(&$matrix, $l){
        $m = &$matrix; // 别名
        // 从不同维度查看, 如果某个单元格内的某种可能性，是该维度的唯一可能性，则可确认该 Cell
        self::DimensionAna($m['R'], $l, $m, 'R');
        self::DimensionAna($m['C'], $l, $m, 'C');
        self::DimensionAna($m['B'], $l, $m, 'B');
    }

    /**
     * @return Boolean 是否已经完成 
     */
    public static function HasDone(Array &$matrix){
         // 已经全部确认了，无需再继续整体尝试确认
        return ($matrix['spec']['c'] == $matrix['confirmedNum']) ;
    }



    /** 
     * Show Page
     */
    public static function ShowPage($Q = 9){
        $sn = is_array($Q) ? $Q : Sudoku::MatrixSn(Sudoku::Solve($Q));
        $sudoku = self::tbCode($sn);

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
                    text-align: center;
                    vertical-align: middle;
                    border: 1px rgba(146, 158, 158, 0.933) solid;
                }

                .t { border-top:    2px grey solid; }
                .r { border-right:  2px grey solid; }
                .b { border-bottom: 2px grey solid; }
                .l { border-left:   2px grey solid; }

                td i {
                    margin: -1px;
                    padding: 0;
                    border: 1px #eee solid;
                    width: 15px;
                    height: 15px;
                    display: block;
                    float: left;
                    color: lightpink;
                    font-size: 13px;
                }
                td span {
                    font-size: 32px;
                }

                .cell {
                    padding: 0;
                    margin: -1px;
                    width: 43px;
                    height: 43px;
                    display: none;
                    text-align:center;
                    font-size: 32px;
                }

                .err {
                    background: lightpink;
                }

            </style>
        </head>
        <body>
            <header><h1>Sudoku</h1></header>
            <div class="group">
                <div class="tb_box">{$sudoku}</div>
            </div>
        </body>
        <script src="http://code.jquery.com/jquery-3.3.1.min.js"
                integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
                crossorigin="anonymous"></script>

        <script>
            function checkInput(obj){
                let bl = /\D/.test(obj.value); // 如果不是 数字 则返回 1 有问题
                if(bl){
                    alert('只能输入数字');
                    obj.value=obj.value.replace(/\D/g,'');
                }
                return bl;
            }
            function curSn(){
                let sn = [];
                let cells = $('.cell');
                let len =  cells.length
                for (let i = 0; i < len; i++) {
                    sn.push(cells.eq(i).val());
                }

                return sn;
            }
            

            function updateSn(curSn, row, column, number, cell){
                // console.log(curSn)
                $.ajax({
                    url: "#",
                    type: "POST",
                    data:{ 
                        sn: curSn.join(','),
                        r : row,
                        c : column,
                        n : number
                    },
                    success: function(res){
                        if(res.err){
                            $(cell).addClass("err");
                            console.log(res);
                        }else{
                            sn = res.sn;
                            // console.log(sn);
                            tds = $('td');
                            for(i in sn){
                                celli = $(tds[i]).find('i');
                                if(typeof(sn[i]) == 'string' || typeof(sn[i]) == 'number'){
                                    celli.hide();
                                    celli.text('');
                                    $(tds[i]).find('input').val(sn[i]);
                                    $(tds[i]).find('input').show();
                                }else{
                                    len = celli.length;
                                    for(j = 0; j < len; j++ ){
                                        if(sn[i][j+1] == undefined){
                                            $(celli[j]).html('');
                                        }else{
                                            $(celli[j]).html(sn[i][j+1]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                });
            }

            $('td').on('click', function(){
                let i = $(this).find('i');
                let input = $(this).find('input');
                if(!$(this).find('span').text()){
                    i.hide();
                    input.show().focus();
                }

            });

            $('td > input').on('blur', function(){
                let input = $(this);
                let i = input.parent('td').find('i');
                // console.log(input.val());
                if(input.val() == ''){ 
                    input.hide();
                    i.show();
                }
            });

            // $('td > input').on('click', function(){ });
            $('td > input').on('change', function(){ 
                let input = $(this);
                input.removeClass('err');
                if(!checkInput(this)){ 
                    let r = $(this).parent().parent().index() + 1;
                    let c = $(this).parent().index() + 1;
                    let n = $(this).val();
                    // console.log('获取更新!');
                    updateSn(curSn(), r, c, n, this);
                };
            });
            
        </script>
        </html>
HTML;
        echo $HTML;
    }

    /**
     * @param   Array   $sn Current SN
     * @param   Int     $io Type descript Input Or Output 
     * @return  String  $tb table html for rander
     */
    public static function tbCode(Array $sn){
        $spec = self::spec($sn);
        $c = $spec['c'];    // 矩阵位
        $w = $spec['w'];    // 整边长
        $u = $spec['u'];    // 块边长

        $tb = '<table>';
        for($i = 0; $i < $c; $i++){
            // Current Number & Cell in Matrix
            $n = $sn[$i];
            $cell = self::CellInfo($i, $w, $u);

            // Position
            $tc = []; // td classes
            if($cell['br'] == 1 && $cell['r'] !== 1)   $tc[] = 't';   // 块上边
            if(!($cell['bc'] % $u) && $cell['c'] % $w) $tc[] = 'r';   // 块右边
            if(!($cell['br'] % $u) && $cell['r'] % $w) $tc[] = 'b';   // 块下边
            if($cell['bc'] == 1 && $cell['c'] !== 1)   $tc[] = 'l';   // 块左边

            // Contents

            // td begin
            $td = '<td class="'.implode(' ', $tc).'">';
            $tp = strtoupper(gettype($n));
            switch($tp){
                case 'ARRAY': // 数组
                    for($ci = 0; $ci < $w; $ci++ ){ // Id in cell
                        $cn = $ci + 1; // Number in cell
                        $td .= in_array($cn, $n) ? '<i>'.$cn.'</i>' : '<i></i>';
                    }
                    $n = '';
                    break;
                case 'NULL': // 格内为空
                    $n = '';
                    break;
                case 'INTEGER':
                default:
                    $td .= '<span>'.$n.'</span>';
                    break;
            }
            $td .= '<input class="cell" maxlength="1" value="'.$n.'"/>';
            $td .= '</td>';
            // td end

            // tr begin
            $trl = $cell['c'] == 1 ? '<tr>' : '';
            $trr = $cell['c'] % $w ? '' : '</tr>';
            // tr end

            $tb .= $trl.$td.$trr;
        }   
        $tb .= '</table>';

        return $tb;
    }

    /** 
     * 更新页面传来的数列字串
     * @param String $sn 逗号间隔的数列字串
     * @return NUll  $sn 更新数列数组，并响应请求，无返回值
     */
    public static function updateSn($args){
        // Process
        $sn = explode(',', $args['sn']); // 转为数组
        $r = $args['r'];
        $c = $args['c'];
        $n = $args['n'];
        $spec = self::spec($sn);
        $id = ($r - 1) * $spec['w'] + $c - 1; // 计算出 id
        $m = Sudoku::Solve($sn);
        $pv = $m['I'][$id]['n'];
        $pv = is_array($pv) ? $pv : [$pv];
        if(!empty($n) && !in_array($n, $pv)){
            $ret = [ // 填入值不合法
                'err' => 1,
                'msg' => '填入值非法！',
                'sn' => Sudoku::MatrixSn($m),
            ];
        }else{
            // 增量确认Cell
            if( !self::HasDone($m) && !empty($n)) Sudoku::CellConfirm($id, $n, $m); // 完成的话不再填
            $sn = Sudoku::MatrixSn($m);
            $ret = [ // 填入值不合法
                'err' => 0,
                'msg' =>  '刷新成功！',
                'sn' =>  $sn,
            ];
        }
        
        // Response
        header('Content-type: application/json; charset=utf-8');
        echo json_encode($ret, 256);
    }
}


// 题
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

/**
 * 渲染页面
 * 处理改动
 *  
 */
try{ empty($_POST) ? Sudoku::ShowPage() : Sudoku::updateSn($_POST);
} catch (Exception $e) { echo $e->getMessage(); }