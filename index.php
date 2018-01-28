<?php
echo'Here is Number Alone!';

// Setting
$w = $max = 9; // means num range is 1~n; width and height for whole
$u = sqrt($w); // width and height for unit
if(floor($u) != $u) throw new Exception('Wrong setting!');
$len = pow($w, 2); // number counts for all
for($i = 0; $i < $w; $i++){ $s[$i+1] = $i+1; } // single num range array

for($i = 0; $i < $len; $i++ ){ 
    // whole
    $r = bcdiv($i, $w) + 1; // how many in/have => row
    $c = bcmod($i, $w) + 1; // how many left    => column
    $n = $i + 1; 
    // block
    $b = bcdiv($r - 1, $u) * $u + bcdiv($c - 1, $u) + 1; // Block
    $br = bcmod($r - 1, $u) + 1;
    $bc = bcmod($c - 1, $u) + 1;
    $bn = ($br - 1) * $u + $bc;
    // bind
    $m[$n] = $s; // matrix
    $m['R'][$r][$c]  = &$m[$n];
    $m['C'][$c][$r]  = &$m[$n];
    $m['B'][$b][$bn] = &$m[$n];
}

function show($arr, $max = 9){
    $w = $max = 9; // means num range is 1~n; width and height for whole
    $u = sqrt($w); // width and height for unit
    if(floor($u) != $u) throw new Exception('Wrong setting!');
    $len = pow($w, 2); // number counts for all
    echo '</br>';
    for($i = 0; $i < $len; $i++){
        // whole
        $r = bcdiv($i, $w) + 1; // how many in/have => row
        $c = bcmod($i, $w) + 1; // how many left    => column
        $n = $i + 1; 
        // block
        $b = bcdiv($r - 1, $u) * $u + bcdiv($c - 1, $u) + 1; // Block
        $br = bcmod($r - 1, $u) + 1;
        $bc = bcmod($c - 1, $u) + 1;
        $bn = ($br - 1) * $u + $bc;
        
        $le = $r % $u == 0 ? '</br>' : '</br>';
        $sp = $c % $w == 0 ? $le : '|';
        $cur = array_shift($arr);
        if(is_null($cur)) $cur = '-';
        echo $cur.$sp;
    }   
}

$q = [ 
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

function rcbDel(&$rcb, $n){
    foreach($rcb as $k => &$rg){
        if(is_array($rg) && in_array($n, $rg)) unset($rg[$n]);
    }
}

foreach($q as $i => $num){
    // whole
    $r = bcdiv($i, $w) + 1; // how many in/have => row
    $c = bcmod($i, $w) + 1; // how many left    => column
    $n = $i + 1; 
    // block
    $b = bcdiv($r - 1, $u) * $u + bcdiv($c - 1, $u) + 1; // Block
    $br = bcmod($r - 1, $u) + 1;
    $bc = bcmod($c - 1, $u) + 1;
    $bn = ($br - 1) * $u + $bc;

   // echo $k.'-'.$v.'</br>'.PHP_EOL;
   if(!is_null($num)){
        $m[$n] = $num;
        rcbDel($m['R'][$r], $num);
        rcbDel($m['C'][$r], $num);
        rcbDel($m['B'][$r], $num);
   }
}

show($q);

// show($m);

// $html = '<table>';
//     if($c % $w == 1) $html .= '<tr>';
//     
//     $html .= '<td><div style=" width: 28px; height: 28px; border: 1px grey solid; margin: 1px; padding: 1px;">
//                 <input type="text" name="i-{$n}" value="{$n}"/>
//             </div></td>';
//     if($c % $w == 0) $html .= '</tr>';
// $html = '</table>';
//echo $html;

var_dump($m);
// exit('End Script!');
