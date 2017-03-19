<?php
/**
 * Created by PhpStorm.
 * User: mycapital
 * Date: 2016/9/12
 * Time: 16:23
 */

require_once "common.php";
require_once "simple_html_dom-master/simple_html_dom.php";



if(isset($_POST["econtent"]))
    $econtent = $_POST["econtent"];
else
    $econtent = "";

while(substr($econtent, -2) == "\r\n"){
    $econtent = substr($econtent, 0, strlen($econtent)-2);
}

if(isset($_POST["changeTo"]) && $_POST["changeTo"] == 0){
    if(stristr($econtent, "<table") == false){ // excel table data
        $count_t = 0;
        for($i=0; $i < strlen($econtent); $i++){
            if ($econtent[$i] == "\t"){
                $count_t++;
            }
            if($econtent[$i] == "\r"){
                break;
            }
        }

        $line = "";
        for($i =0; $i <= $count_t; $i++){
            $line .= "|\t:------------\t";
        }
        $temp = preg_replace("/\t/","\t|\t",$econtent);
        $temp_str = preg_split("/\r\n/",$temp,2);
        $temp_str[1] = preg_replace("/\r\n/","\t|\r\n|\t",$temp_str[1]);
        $mcontent = "|\t".$temp_str[0]."\t|\r\n".$line."\t|\r\n|\t".$temp_str[1]."\t|";

    }else{ // html table data
        if(stristr($econtent, "rowspan") == true || stristr($econtent, "colspan") == true){
            $mcontent = "Can't convert complex html table to markdown!";
        }else{
            $html = str_get_html($econtent);
            $is_first_line = true;
            $td_count = 0;
            foreach($html->find('tr') as $tr) {
                $mcontent .= "|";

                foreach($tr->find('td') as $td){
                    $mcontent .= "\t".get_final_text($td) . "\t|";
                    if($is_first_line)
                        $td_count++;
                }
                $mcontent .= "\r\n";
                if($is_first_line){
                    for($i =0; $i < $td_count; $i++){
                        $mcontent .= "|\t:------------\t";
                    }
                    $mcontent .= "|\r\n";
                    $is_first_line = false;
                }
            }
        }
    }
} elseif(isset($_POST["changeTo"]) && $_POST["changeTo"] == 1){
    if(stristr($econtent, "<table") == false){ // excel table data
        $data = get_data_matrix($econtent);

        $attr_flag = add_matrix_attr($data);

        $row = count($data);
        $col = count($data[0]);

        $mcontent = "<table>\r\n";

        if(isset($_POST["header"]) && $_POST["header"] == "on") { // No thead tag
            for ($i = 0; $i < $row; $i++){
                $mcontent .= "\t<tr>\r\n";
                for ($j = 0; $j < $col; $j++){
                    if ($attr_flag[$i][$j]["rowspan"] == 0 && $attr_flag[$i][$j]["colspan"] == 0)
                        continue;
                    $mcontent .= "\t\t<td" . attr_matrix_add_span($attr_flag, $i, $j) . ">";
                    $mcontent .= $data[$i][$j] . "</td>\r\n";
                }
                $mcontent .= "\t</tr>\r\n";

            }
            $mcontent .= "</table>";
        }else{
            $is_first_line = true;
            for ($i = 0; $i < $row; $i++){
                if($is_first_line)
                    $mcontent .= "\t<thead>\r\n";
                $mcontent .= "\t<tr>\r\n";
                for ($j = 0; $j < $col; $j++){
                    if ($attr_flag[$i][$j]["rowspan"] == 0 && $attr_flag[$i][$j]["colspan"] == 0)
                        continue;
                    $mcontent .= "\t\t<td" . attr_matrix_add_span($attr_flag, $i, $j) . ">";
                    $mcontent .= $data[$i][$j] . "</td>\r\n";
                }
                $mcontent .= "\t</tr>\r\n";
                if($is_first_line){
                    $mcontent .= "\t</thead>\r\n";
                    $is_first_line = false;
                }
            }
            $mcontent .= "</table>";
        }
    }else{ // html table data
        $html = str_get_html($econtent);
        $mcontent = "<table>\r\n";

        if(isset($_POST["header"]) && $_POST["header"] == "on"){ // No thead tag
            foreach($html->find('tr') as $tr) {
                $mcontent .= "\t<tr" . attr_add_span($tr) . ">\r\n";

                foreach($tr->find('td') as $td){
                    $mcontent .= "\t\t<td" . attr_add_span($td) . ">";
                    $mcontent .= get_final_text($td) . "</td>\r\n";
                }
                $mcontent .= "\t</tr>\r\n";
            }
            $mcontent .= "</table>";
        }else{
            $is_first_line = true;
            foreach($html->find('tr') as $tr) {
                if($is_first_line)
                    $mcontent .= "\t<thead>\r\n";
                $mcontent .= "\t<tr" . attr_add_span($tr) . ">\r\n";

                foreach($tr->find('td') as $td){
                    $mcontent .= "\t\t<td" . attr_add_span($td) . ">";
                    $mcontent .= get_final_text($td) . "</td>\r\n";
                }
                $mcontent .= "\t</tr>\r\n";
                if($is_first_line){
                    $mcontent .= "\t</thead>\r\n";
                    $is_first_line = false;
                }
            }
            $mcontent .= "</table>";
        }
    }
} else{
    $mcontent = "";
}

END:
if(strlen($mcontent) < 20)
    $mcontent = "";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Convert Excel / HTML Table</title>
    <link rel="stylesheet" type="text/css" href="public/semantic.css">
    <style>
        .watermark_container{
            width: 100%;
        }
        label{
            width: 100%;
        }
    </style>
    <script type="text/javascript" src="public/jquery.min.js"></script>
    <script type="text/javascript" src="public/jquery.watermark.js"></script>
    <script type="text/javascript" src="public/semantic.min.js"></script>
</head>
<body>
<div class="ui container">
    <h2>Convert Your Excel/HTML Table Data To Pure Markdown/HTML Table</h2>
    <input id="change_to_back" hidden value="<?php echo $_POST["changeTo"]; ?>">
    <input id="header_back" hidden value="<?php echo $_POST["header"]; ?>">
    <form method="post" action="tableMaster.php">
        <div class="ui form">
            <div class="field">
                <label>Excel/HTML Table</label>
                <textarea style="width: 100%" class="jq_watermark" rows="21" id="econtent" name="econtent" placeholder="Please input your Excel/HTML table data (copy and paste)<br><br>
                    Excel Table:<br>
                    <textarea rows='4' style='width:95%'>
                    Tables&emsp;Are&emsp;Cool
                    col 3 is&emsp;right-aligned&emsp;$1,600
                    col 2 is&emsp;centered&emsp;$12
                    </textarea>

                    <br><br>

                    HTML Table:
                    <textarea rows='9' style='width:95%'>
                    <table border=0 cellpadding=0 cellspacing=0 width=776 style='border-collapse: collapse'>
                    <thead><tr height=19 style='height:14.25pt'>
                    &emsp;<td height=19 class=xl65 width=286 style='height:14.25pt;width:215pt'>名称</td>
                    &emsp;<td class=xl65 width=247 style='border-left:none;width:185pt'>简述</td>
                    </tr></thead>
                    <tr height=38 style='height:28.5pt'>
                    &emsp;<td height=38 class=xl67 width=243>茂源资本</td>
                    &emsp;<td height=38 class=xl68 width=243>没有球星的常胜球队</td>
                    </tr></table></textarea>"><?php echo $econtent; ?></textarea>
            </div>
            <div class="field">
                <label id="tableName">Markdown Table</label>
                <textarea rows="19" id="mcontent" placeholder="The result is here!" name="mcontent"><?php echo $mcontent; ?></textarea>
            </div>
        </div>
        <div class="ui grid" style="margin-top: 1em">
            <div class="row">
                <div class="left floated ten wide column">
                    <select class="ui dropdown" name="changeTo">
                        <option value="0">Markdown</option>
                        <option value="1">HTML Table</option>
                    </select>
                    <div class="ui slider checkbox" id="check_header" style="padding-left: 0.5em">
                        <input type="checkbox" name="header">
                        <label>Remove Header Style</label>
                    </div>
                </div>
                <div class="right floated right aligned six wide column">
                    <div class="ui animated button" onclick="submitbtn()">
                        <div class="visible content">Convert</div>
                        <div class="hidden content">
                            <i class="right arrow icon"></i>
                        </div>
                    </div>
                    <button id="submitBTN" hidden></button>
                </div>
            </div>
        </div>
    </form>
</div>
<script>
    if ($("#header_back").val() != "on" && $("#change_to_back").val() == 0)
        $('#check_header').checkbox('set disabled');
    else if($("#header_back").val() != "on" && $("#change_to_back").val() == 1)
        $('#check_header').checkbox('set enabled');
    else
        $('#check_header').checkbox('check');

    if ($("#change_to_back").val() == 0){
        $('.ui.dropdown')
            .dropdown('set selected', "Markdown");
        ;
        $('#tableName').html('Markdown Table');
    }else {
        $('.ui.dropdown')
            .dropdown('set selected', "HTML Table");
        ;
        $('#tableName').html('HTML Table');
    }

    $('.ui.dropdown').dropdown({
        onChange: function(value, text, $selectedItem) {
            if (value == 0) {
                $('#check_header').checkbox('set disabled');
                $('#check_header').checkbox('uncheck');
                $('#tableName').html('Markdown Table');
            } else {
                $('#check_header').checkbox('set enabled');
                $('#tableName').html('HTML Table');
            }
        }
    });

    function submitbtn() {
        $("#submitBTN").click();
    }
</script>
</body>
</html>