<?php

function attr_add_span($node){
    if($node->attr['rowspan'] == null && $node->attr['colspan'] == null){

    }else if($node->attr['rowspan'] != null && $node->attr['colspan'] != null){
        return " rowspan='".$node->attr['rowspan']."' colspan='".$node->attr['colspan']."'";
    }else if($node->attr['rowspan'] != null){
        return " rowspan='".$node->attr['rowspan']."'";
    }else{
        return " colspan='".$node->attr['colspan']."'";
    }
}

function attr_matrix_add_span($flag, $row, $col){
    $rowspan = $flag[$row][$col]["rowspan"];
    $colspan = $flag[$row][$col]["colspan"];
    if($rowspan > 1 && $colspan > 1){
        return " rowspan='".$rowspan."' colspan='".$colspan."'";
    }else if($rowspan > 1 && $colspan == 1){
        return " rowspan='".$rowspan."'";
    }else if($colspan > 1 && $rowspan == 1){
        return " colspan='".$colspan."'";
    }
    return "";
}

function get_final_text($node){
    while(count($node->children) != 0){
        $node = $node->children[0];
    }
    return $node->innertext;
}

function get_data_matrix($input){
    $row = 0;
    $col = 0;
    $start = 0;
    for($i=0; $i < strlen($input); $i++){
        if ($input[$i] == "\t"){
            $end = $i;
            $data[$row][$col++] = substr($input, $start, $end-$start);
            $start = $i + 1;
        }
        if($input[$i] == "\r"){
            $end = $i;
            $data[$row++][$col] = substr($input, $start, $end-$start);
            $col = 0;
            $start = $i + 2;
        }
    }
    $data[$row++][$col] = substr($input, $start);
    return $data;
}

function sort_points($input){
    for ($i = 0; $i < count($input); $i++){
        for ($j = $i + 1; $j < count($input); $j++){
            if ($input[$i]->row < $input[$j]->row){
                $row = $input[$i]->row;
                $col = $input[$i]->col;
                $input[$i]->row = $input[$j]->row;
                $input[$i]->col = $input[$j]->col;
                $input[$j]->row = $row;
                $input[$j]->col = $col;
            }elseif ($input[$i]->row == $input[$j]->row && $input[$i]->col < $input[$j]->col){
                $row = $input[$i]->row;
                $col = $input[$i]->col;
                $input[$i]->row = $input[$j]->row;
                $input[$i]->col = $input[$j]->col;
                $input[$j]->row = $row;
                $input[$j]->col = $col;
            }
        }
    }
    return $input;
}

function count_row_span($input, $row, $col, $visited){
    $count = 1;
    $input_row = count($input);
    $visited[$row][$col] = true;
    while ($row < $input_row - 1 && $input[$row + 1][$col] == "" && $visited[$row + 1][$col] == false){
        $visited[$row + 1][$col] = true;
        $count++;
        $row++;
    }
    $result['count'] = $count;
    $result['visited'] = $visited;
    return $result;
}

function count_col_span($input, $row, $col, $visited){
    $count = 1;
    $input_col = count($input[0]);
    $visited[$row][$col] = true;
    while ($col < $input_col - 1 && $input[$row][$col + 1] == "" && $visited[$row][$col + 1] == false){
        $visited[$row][$col + 1] = true;
        $count++;
        $col++;
    }
    $result['count'] = $count;
    $result['visited'] = $visited;
    return $result;
}

function check_point_valid($input, $points){
    $row = count($input);
    $col = count($input[0]);
    for ($i = 0; $i<count($points); $i++){
        if ( $points[$i]->row < 0 || $points[$i]->row > $row
        || $points[$i]->col < 0 || $points[$i]->col > $col )
            return false;
    }
    return true;
}

function add_matrix_attr($input){
    if (count($input) == 0)
        return null;
    $row = count($input);
    $col = count($input[0]);
    $flag = array();
    $visited = array();
    for ($i = $row - 1; $i >= 0; $i--){
        for ($j = $col - 1; $j >= 0; $j--){
            if ($input[$i][$j] == ""){
                $flag[$i][$j]["rowspan"] = 0;
                $flag[$i][$j]["colspan"] = 0;
            }else{
                $count_row_span_result = count_row_span($input, $i, $j, $visited);
                $flag[$i][$j]["rowspan"] = $count_row_span_result['count'];
                $visited = $count_row_span_result['visited'];

                $count_col_span_result = count_col_span($input, $i, $j, $visited);
                $flag[$i][$j]["colspan"] = $count_col_span_result['count'];
                $visited = $count_col_span_result['visited'];
            }
        }
    }
    return $flag;
}

function process_span_point($input, $points, $flag, $visited){
    for ($i=0; $i<count($points); $i++){
        $row = $points[$i]->row;
        $col = $points[$i]->col;

        $count_row_span_result = count_row_span($input, $row, $col, $visited);
        $flag[$row][$col]["rowspan"] = $count_row_span_result['count'];
        $visited = $count_row_span_result['visited'];

        $count_col_span_result = count_col_span($input, $row, $col, $visited);
        $flag[$row][$col]["colspan"] = $count_col_span_result['count'];
        $visited = $count_col_span_result['visited'];
    }
    $result['flag'] = $flag;
    $result['visited'] = $visited;
    return $result;
}

function add_matrix_attr_span($input, $points){
    if (count($input) == 0)
        return null;
    $row = count($input);
    $col = count($input[0]);
    $flag = array();
    $visited = array();
    $process_span_point_result = process_span_point($input, $points, $flag, $visited);
    $flag = $process_span_point_result['flag'];
    $visited = $process_span_point_result['visited'];
    for ($i = $row - 1; $i >= 0; $i--){
        for ($j = $col - 1; $j >= 0; $j--){
            if ($input[$i][$j] == ""){
                $flag[$i][$j]["rowspan"] = 0;
                $flag[$i][$j]["colspan"] = 0;
            }else{
                if (isset($flag[$i][$j]["rowspan"]))
                    continue;
                $count_row_span_result = count_row_span($input, $i, $j, $visited);
                $flag[$i][$j]["rowspan"] = $count_row_span_result['count'];
                $visited = $count_row_span_result['visited'];

                $count_col_span_result = count_col_span($input, $i, $j, $visited);
                $flag[$i][$j]["colspan"] = $count_col_span_result['count'];
                $visited = $count_col_span_result['visited'];
            }
        }
    }
    return $flag;
}
