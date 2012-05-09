<?php
/*******************************************************************************
 * Author Remy Lalanne
 * Copyright (c) 2005,2006,2007 Remy Lalanne
 ******************************************************************************/
session_start();
header('Content-type: application/xml');
echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";

include_once "service.php"; //error_code()

$err    = $ERR_NOERROR;
$query = arg("query");
$xsl   = arg("xsl");
$result = false;
$body    = "";
$head    = "";

$query = stripslashes($query);

echo "<!-- generated by ".$_SERVER['SCRIPT_NAME']."  -->\n";
if ($xsl)
	echo '<?xml-stylesheet type="text/xsl" href="'.$xsl.'"?>'."\n";
echo '<soap:Envelope xmlns:soap="http://www.w3.org/2003/05/soap-envelope">'."\n";
echo "\t<soap:Header>\n";

$err = damas_service::init();

if ($err==$ERR_NOERROR && !$query)
	$err = $ERR_MYSQL_QUERY;

# Forbidden SQL manipulation keywords
# ALTER CREATE DROP RENAME
# CALL DELETE DO HANDLER INSERT LOAD REPLACE TRUNCATE UPDATE

$querystr = str_replace("&","&amp;",$query);
$querystr = str_replace("<","&lt;",$querystr);
$querystr = str_replace(">","&gt;",$querystr);
$head .= "\t\t".'<query>'.$querystr.'</query>'."\n";


// converts an SQL result to XML
// <row>
//   <field1>value1</field1>
//   <field2>value2</field2>
// </row>
function mysql2xml_result_dump ($result, $node_name="row")
{
    $xml = "";
    while ($row = mysql_fetch_array($result)) {
        $xml .= "<row>\n";
        for ($i=0; $i<mysql_num_fields($result); $i++){
            $field_name = mysql_field_name($result,$i);
            $field_value = $row[$i];
            /*
            $field_name = str_replace("&","&amp;",$field_name);
            $field_value = str_replace("&","&amp;",$field_value);
            $field_value = str_replace("<","&lt;",$field_value);
            $field_value = str_replace(">","&gt;",$field_value);
            $field_value = str_replace('"',"",$field_value);
            */
            //$xml .= "\t<".$field_name.">".$field_value.'</'.$field_name.'>';
            $xml .= "\t<".$field_name.">".htmlspecialchars( $field_value, ENT_QUOTES ).'</'.$field_name.'>';
        }
        $xml .= "</row>\n";
    }
    return $xml;
}


if ($err==$ERR_NOERROR)
	$result = mysql_query($query);

if ($err==$ERR_NOERROR && !$result)
	$err = $ERR_MYSQL_QUERY;

if ($err!=$ERR_NOERROR)
	$head .= "\t\t<mysql_error>".mysql_error()."</mysql_error>\n";

if ($err==$ERR_NOERROR){
	if ($result === true){
	}
	else { // SELECT QUERY
		$head .= "\t\t".'<mysql_info>'.mysql_info()."</mysql_info>\n";
		//$body .= "\t<mysql-result query=\"$query\" database=\"$db_name\" matches=\"".mysql_num_rows($result)."\">\n";
		$body .= "\t\t<mysql-result>\n";
		$body .= mysql2xml_result_dump($result,"row");
		$body .= "\t\t</mysql-result>\n";
	}
}

/*
echo "\t<head>\n".$head."\t</head>\n";
echo "\t<body>\n".$body."\t</body>\n";
echo error_code($err, $error[$err]);
*/
$txt = $head;
$txt .= error_code($err, $error[$err]);
$txt .= debug_args();
$txt .= "\t<version>".$version."</version>\n";
$txt .= "\t</soap:Header>\n";
$txt .= "\t<soap:Body>\n";
$txt .= $body;
echo $txt;
echo "\t</soap:Body>\n";
echo "</soap:Envelope>\n";
?>
