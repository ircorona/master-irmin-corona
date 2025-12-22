<?php
$host = "localhost";
$uname = "root";
$upass = ""; /* en caso de ser MAMP : $upass = "root"; */
$db = "northwind";

/* Section 2 : Variable */
$maxField = 6;
$lineperpage = 15;
$linenumber = true;
if(isset($_GET["p"])) $currentpage = $_GET["p"]; else $currentpage = 1;
if((int)phpversion() >= 7) $php7 = true; else $php7 = false;
if(isset($_GET["php"])) { if($_GET["php"] == "7") $php7 = true; else $php7 = false; }
$mytable = "<style>
.mytable { margin-left:auto; margin-right:auto; }
.mytable td {padding:3px; border-bottom: 1px solid #dddddd; }
.mytable tr:nth-child(even) {background-color: #ddffdd}
.mytable tr:nth-child(odd) {background-color: #ffffdd}
.mytable tr:hover {background-color: #ddddff}
</style>";
/* Section 3 : Connection */
if($php7) {
$connect = new mysqli($host, $uname, $upass, $db);
if ($connect->connect_error) die("Connection failed: " . $connect->connect_error);
} else {
if (function_exists('mysql_connect')) {
if(!$connect = mysql_connect($host, $uname, $upass)) die("Connect failed : ");
} else {
die("function mysql_connect : not exist in PHP");
}
}
/* Section 4 : SQL Command */
$sql = "select * from employees";
$sql_type = 0; /* SQL type */
if(isset($_GET["t"])) {
$sql_type = $_GET["t"];
switch ($_GET["t"]) {

}
}
/* Section 5 : Display data */
if(isset($_GET["sql"]) && $_GET["sql"] == "show") die($sql);
if($php7) {
$result = $connect->query($sql);
if ($result->num_rows == 0) die("Query : failed<br/>" . $sql);
$numField = mysqli_num_fields($result);
if($numField < $maxField) $maxField = $numField;
echo $mytable . "<table class='mytable'><tr style='background-color:black;color:white;'>";
if($linenumber) echo "<td>no.</td>";
$i = 0;
while ($fieldinfo=mysqli_fetch_field($result)) {
if ($i++ < $maxField) echo "<td style='text-align:center'>" . $fieldinfo->name . "</td>";
}
echo "</tr>";
$cntrec=1;
$totalRec = mysqli_num_rows($result);
while ($row = mysqli_fetch_array($result)) {
if ($cntrec >=firstrec($totalRec,$lineperpage,$currentpage) && $cntrec <=lastrec($totalRec,$lineperpage,$currentpage)) {
echo "<tr>";
if($linenumber) echo "<td>$cntrec</td>";
for ($i=0; $i<$maxField ; $i++ ) { echo "<td>$row[$i]</td>"; }
echo "</tr>";
}
$cntrec++;
}
echo "</table><div style='margin-left:auto;margin-right:auto;width:720px;text-align:center;background-color:#ffdddd'>";
for($i=1;$i<=totalpage($totalRec,$lineperpage);$i++) {
if ($i == $currentpage)
echo "$i : ";
else
echo "<a href='?t=$sql_type&p=$i'>$i</a> : ";
}
echo "Total $totalRec records";
$connect->close();
} else {
if (!$result=mysql_db_query($db,$sql)) die("Query : failed<br/>".$sql);
$numField = mysql_num_fields($result);
if($numField < $maxField) $maxField = $numField;
echo "<style>table,th,td {border: 1px solid #dddddd;border-spacing:1px}</style>";
echo "<table style='border-style:solid;border-width:2px;border-color:blue'><tr>";
if($linenumber) echo "<td>no.</td>";
for ($i=0; $i<$maxField ; $i++ ) {
echo "<td style='background-color:#ffffdd;text-align:center'>" . mysql_field_name($result, $i) . "</td>";
}
echo "</tr>";
$cntrec=1;
$totalRec = mysql_num_rows($result);
while ($row = mysql_fetch_array($result)) {
if ($cntrec >=firstrec($totalRec,$lineperpage,$currentpage) && $cntrec <=lastrec($totalRec,$lineperpage,$currentpage)) {
echo "<tr>";
if($linenumber) echo "<td>$cntrec</td>";
for ($i=0; $i<$maxField ; $i++ ) { echo "<td>$row[$i]</td>"; }
echo "</tr>";
}
$cntrec++;
}
/* while ($row = mysql_fetch_assoc($result)) { echo $row["CustomerID"]; } */
/* while ($row = mysql_fetch_object($result)) { echo $row->{"CustomerID"} หรือ $row->CustomerID } */
echo "</table><div style='margin-left:auto;margin-right:auto;width:720px;text-align:center;background-color:#dddddd'>";
for($i=1;$i<=totalpage($totalRec,$lineperpage);$i++) {
if ($i == $currentpage)
echo "$i : ";
else
echo "<a href='?t=$sql_type&p=$i'>$i</a> : ";
}
echo "Total $totalRec records";
mysql_close($connect);
}
echo '<br/><a href="?t=1">Table:Orders</a> : <a href="?t=0&sql=show">SQL show</a> : <a href="?t=1&php=5">PHP5</a> : <a href="?t=1&php=7">PHP7</a></div>';
/* Section 6 : Page number control */
function totalpage($totalrec,$lpp) { return ceil($totalrec / $lpp); }
function firstrec($totalrec,$lpp,$page) { return (($lpp * ($page - 1) + 1) > $totalrec ? 1 : ($lpp * ($page - 1) + 1)); }
function lastrec($totalrec,$lpp,$page) { return (($lpp * $page) > $totalrec ? $totalrec : ($lpp * $page)); }
?>