<?php
//--------------------------------------------------
//　おえかきけいじばん「noe-board」削除くん
//　by sakots https://sakots.red/
//--------------------------------------------------

//設定の読み込み
require(__DIR__."/config.php");
require(__DIR__."/templates/".THEMEDIR."template_ini.php");
//DB接続
require(__DIR__."/dbconnect.php");

//var_dump($_POST);

$delno = $_POST["delno"];
$delt = $_POST["delt"]; //0親1レス削除

if ($delt == 0) {
	$deltable = 'table';
	$id = "tid";
} else {
	$deltable = 'tabletree';
	$id = "iid";
}

//記事呼び出し
$sql ="SELECT * FROM ".$deltable." WHERE ".$id."=".$delno;
$msgs = $db->prepare($sql);
$msgs->execute(array($delno));
$msg = $msgs->fetch();


if (password_verify($_POST["pwd"],$msg['pwd']) == true) {
	$sql = "DELETE FROM ".$deltable." WHERE ".$id."=".$delno;
	$del = $db->prepare($sql);
	$del->execute(array($delno));
} elseif (ADMIN_PASS == $_POST["pwd"] && $_POST["admindel"] == 1) {
	$sql = "DELETE FROM ".$deltable." WHERE ".$id."=".$delno;
	$del = $db->prepare($sql);
	$del->execute(array($delno));
} elseif (ADMIN_PASS == $_POST["pwd"] && $_POST["admindel"] != 1) {
	$sql = "UPDATE ".$deltable." SET invz=1 WHERE ".$id."=".$delno;
	$del = $db->exec($sql);
} else {
	echo "パスワードまたは記事番号が違います";
	exit();
}

header('Location:'.PHP_SELF);
exit();

?>