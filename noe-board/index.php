<?php
//--------------------------------------------------
//　おえかきけいじばん「noe-board」
//　by sakots https://sakots.red/
//--------------------------------------------------

//smarty-3.1.34
require_once(__DIR__.'/libs/Smarty.class.php');
$smarty = new Smarty();

//スクリプトのバージョン
$smarty->assign('ver','v0.17.2');

//設定の読み込み
require(__DIR__."/config.php");
require(__DIR__."/templates/".THEMEDIR."template_ini.php");

//var_dump($_POST);

$message = "(system message...)";
$self = PHP_SELF;

$smarty->assign('base',BASE);
$smarty->assign('btitle',TITLE);
$smarty->assign('home',HOME);
$smarty->assign('self',PHP_SELF);
$smarty->assign('message',$message);
$smarty->assign('pdefw',PDEF_W);
$smarty->assign('pdefh',PDEF_H);
$smarty->assign('pmaxw',PMAX_W);
$smarty->assign('pmaxh',PMAX_H);
$smarty->assign('skindir',THEMEDIR);
$smarty->assign('tver',TEMPLATE_VER);

$smarty->assign('dispid',DISP_ID);
$smarty->assign('updatemark',UPDATE_MARK);

$smarty->assign('useanime',USE_ANIME);
$smarty->assign('defanime',DEF_ANIME);

$smarty->assign('dptime',DSP_PAINTTIME);

$smarty->assign('share_button',SHARE_BUTTON);



//スパム無効化関数
function newstring($string) {
	$string = htmlspecialchars($string,ENT_QUOTES,'utf-8');
	$string = str_replace(",","，",$string);
	return $string;
}
//無効化ここまで

/* オートリンク */
function auto_link($proto){
	if(!(stripos($proto,"script")!==false)){//scriptがなければ続行
	$proto = preg_replace("{(https?|ftp)(://[[:alnum:]\+\$\;\?\.%,!#~*/:@&=_-]+)}","<a href=\"\\1\\2\" target=\"_blank\" rel=\"nofollow noopener noreferrer\">\\1\\2</a>",$proto);
	return $proto;
	}else{
	return $proto;
	}
}

$mode = newstring(filter_input(INPUT_POST, 'mode'));

//var_dump($_GET);
if(filter_input(INPUT_GET, 'mode')==="anime"){
	$pch = newstring(filter_input(INPUT_GET, 'pch'));
	//$shi = filter_input(INPUT_GET, 'shi',FILTER_VALIDATE_INT);
	$mode = "anime";
}
if(filter_input(INPUT_GET, 'mode')==="continue"){
	$no = filter_input(INPUT_GET, 'no',FILTER_VALIDATE_INT);
	$mode = "continue";
}
if(filter_input(INPUT_GET, 'mode')==="admin"){
	$mode = "admin";
}
if(filter_input(INPUT_GET, 'mode')==="admin_in"){
	$mode = "admin_in";
}
if(filter_input(INPUT_GET, 'mode')==="piccom"){
	$stime = filter_input(INPUT_GET, 'stime',FILTER_VALIDATE_INT);
	$resto = filter_input(INPUT_GET, 'resto',FILTER_VALIDATE_INT);
	$mode = "piccom";
}
if(filter_input(INPUT_GET, 'mode')==="picrep"){
	$no = filter_input(INPUT_GET, 'no',FILTER_VALIDATE_INT);
	$pwd = newstring(filter_input(INPUT_GET, 'pwd'));
	$repcode = newstring(filter_input(INPUT_GET, 'repcode'));
	$stime = filter_input(INPUT_GET, 'stime',FILTER_VALIDATE_INT);
	$mode = "picrep";
}
if(filter_input(INPUT_GET, 'mode')==="regist"){
	$mode = "regist";
}
if(filter_input(INPUT_GET, 'mode')==="res"){
	$mode = "res";
}
if(filter_input(INPUT_GET, 'mode')==="sodane"){
	$mode = "sodane";
	$resto = filter_input(INPUT_GET, 'resto',FILTER_VALIDATE_INT);
}
if(filter_input(INPUT_GET, 'mode')==="rsodane"){
	$mode = "rsodane";
	$resto = filter_input(INPUT_GET, 'resto',FILTER_VALIDATE_INT);
}
if(filter_input(INPUT_GET, 'mode')==="del"){
	$mode = "del";
}
if(filter_input(INPUT_GET, 'mode')==="edit"){
	$mode = "edit";
}
if(filter_input(INPUT_GET, 'mode')==="editexec"){
	$mode = "editexec";
}

$message ="";
$sub = ( isset( $_POST["sub"] ) === true ) ? newstring($_POST["sub"]): "";
$name = ( isset( $_POST["name"] ) === true ) ? newstring($_POST["name"]): "";
$url = ( isset( $_POST["url"] )  === true ) ? newstring(trim($_POST["url"]))  : "";
$mail = ( isset( $_POST["mail"] )  === true ) ? newstring(trim($_POST["mail"]))  : "";
$com = ( isset( $_POST["com"] )  === true ) ? newstring(trim($_POST["com"]))  : "";
$parent = ( isset( $_POST["parent"] )  === true ) ? newstring(trim($_POST["parent"]))  : "";
$picfile = ( isset( $_POST["picfile"] )  === true ) ? newstring(trim($_POST["picfile"]))  : "";
$invz = ( isset( $_POST["invz"] )  === true ) ? newstring(trim($_POST["invz"]))  : "";
$img_w = ( isset( $_POST["img_w"] )  === true ) ? newstring(trim($_POST["img_w"]))  : "";
$img_h = ( isset( $_POST["img_h"] )  === true ) ? newstring(trim($_POST["img_h"]))  : "";
$time = ( isset( $_POST["time"] )  === true ) ? newstring(trim($_POST["time"]))  : "";
$pwd = ( isset( $_POST["pwd"] )  === true ) ? newstring(trim($_POST["pwd"]))  : "";
$pwd = password_hash($pwd,PASSWORD_DEFAULT);
$exid = ( isset( $_POST["exid"] )  === true ) ? newstring(trim($_POST["exid"]))  : "";

//var_dump($_COOKIE);

$pwdc = filter_input(INPUT_COOKIE, 'pwdc');
$usercode = filter_input(INPUT_COOKIE, 'usercode');//nullならuser-codeを発行

//ユーザーip
function get_uip(){
	$userip = getenv("HTTP_CLIENT_IP");
	if(!$userip){
		$userip = getenv("HTTP_X_FORWARDED_FOR");
	} 
	if(!$userip){
		$userip = getenv("REMOTE_ADDR");
	} 
	return $userip;
}

$smarty->assign('usercode',$usercode);



//投稿があればデータベースへ保存する
/* 記事書き込み */
function regist() {
	global $name,$com,$sub,$parent,$picfile,$mail,$url,$time,$pwd,$exid,$invz;
	global $badstring,$badip;
	global $ptime;
	global $badstr_A,$badstr_B,$badname;
	global $smarty;
	$userip = get_uip();

	$sub = ( isset( $_POST["sub"] ) === true ) ? newstring($_POST["sub"]): "";
	$name = ( isset( $_POST["name"] ) === true ) ? newstring($_POST["name"]): "";
	$url = ( isset( $_POST["url"] )  === true ) ? newstring(trim($_POST["url"]))  : "";
	$mail = ( isset( $_POST["mail"] )  === true ) ? newstring(trim($_POST["mail"]))  : "";
	$com = ( isset( $_POST["com"] )  === true ) ? newstring(trim($_POST["com"]))  : "";
	$parent = ( isset( $_POST["parent"] )  === true ) ? newstring(trim($_POST["parent"]))  : "";
	$picfile = ( isset( $_POST["picfile"] )  === true ) ? newstring(trim($_POST["picfile"]))  : "";
	$invz = ( isset( $_POST["invz"] )  === true ) ? newstring(trim($_POST["invz"]))  : "";
	$img_w = ( isset( $_POST["img_w"] )  === true ) ? newstring(trim($_POST["img_w"]))  : "";
	$img_h = ( isset( $_POST["img_h"] )  === true ) ? newstring(trim($_POST["img_h"]))  : "";
	$stime = ( isset( $_POST["stime"] )  === true ) ? newstring(trim($_POST["stime"]))  : "";
	$pwd = ( isset( $_POST["pwd"] )  === true ) ? newstring(trim($_POST["pwd"]))  : "";
	$pwdh = password_hash($pwd,PASSWORD_DEFAULT);
	$exid = ( isset( $_POST["exid"] )  === true ) ? newstring(trim($_POST["exid"]))  : "";

	//チェックする項目から改行・スペース・タブを消す
	$chk_com  = preg_replace("/\s/u", "", $com );
	$chk_name = preg_replace("/\s/u", "", $name );
	$chk_sub = preg_replace("/\s/u", "", $sub );
	$chk_mail = preg_replace("/\s/u", "", $mail );

	//本文に日本語がなければ拒絶
	if (USE_JAPANESEFILTER) {
		mb_regex_encoding("UTF-8");
		if (strlen($com) > 0 && !preg_match("/[ぁ-んァ-ヶー一-龠]+/u",$chk_com)) {
			error(MSG035);
			exit;
		}
	}

	//本文へのURLの書き込みを禁止
	if(DENY_COMMENTS_URL && preg_match('/:\/\/|\.co|\.ly|\.gl|\.net|\.org|\.cc|\.ru|\.su|\.ua|\.gd/i', $com)) {error(MSG036); exit;}

	foreach($badstring as $value){//拒絶する文字列
		if($value===''){
		break;
		}
		if(preg_match("/$value/ui",$chk_com)||preg_match("/$value/ui",$chk_sub)||preg_match("/$value/ui",$chk_name)||preg_match("/$value/ui",$chk_mail)){
			error(MSG032);
			exit;
		}
	}
	unset($value);	
	if(isset($badname)){//使えない名前
		foreach($badname as $value){
			if($value===''){
			break;
			}
			if(preg_match("/$value/ui",$chk_name)){
				error(MSG037);
				exit;
			}
		}
		unset($value);	
	}

	$bstr_A_find=false;
	$bstr_B_find=false;

	foreach($badstr_A as $value){//指定文字列が2つあると拒絶
		if($value===''){
		break;
		}
		if(preg_match("/$value/ui",$chk_com)||preg_match("/$value/ui",$chk_sub)||preg_match("/$value/ui",$chk_name)||preg_match("/$value/ui",$chk_mail)){
			$bstr_A_find=true;
		break;
		}
	}
	unset($value);
	foreach($badstr_B as $value){
		if($value===''){
			break;
		}
		if(preg_match("/$value/ui",$chk_com)||preg_match("/$value/ui",$chk_sub)||preg_match("/$value/ui",$chk_name)||preg_match("/$value/ui",$chk_mail)){
			$bstr_B_find=true;
		break;
		}
	}
	unset($value);
	if($bstr_A_find && $bstr_B_find){
		error(MSG032);
		exit;
	}

	if(USE_NAME&&!$name) {error(MSG009);exit;}
	if(USE_COM&&!$com) {error(MSG008);exit;}
	if(USE_SUB&&!$sub) {error(MSG010);exit;}

	if(strlen($com) > MAX_COM) {error(MSG011);exit;}
	if(strlen($name) > MAX_NAME) {error(MSG012);exit;}
	if(strlen($mail) > MAX_EMAIL) {error(MSG013);exit;}
	if(strlen($sub) > MAX_SUB) {error(MSG014);exit;}

	//ホスト取得
	$host = gethostbyaddr($userip);

	foreach($badip as $value){ //拒絶host
		if(preg_match("/$value$/i",$host)) {error(MSG016);exit;}
	}
	//↑セキュリティ関連ここまで
	
	try {
		$db = new PDO("sqlite:noe.db");
		if (isset($_POST["send"] ) ===  true) {

			if ( $name   === "" ) $name = DEF_NAME;
			if ( $com  === "" ) $com  = DEF_COM;
			if ( $sub  === "" ) $sub  = DEF_SUB;

			$utime = time();
			if ($parent == 0 ) {
				$parent = $utime;
			}
			$tree = ($parent * 1000000000) - $utime;

			if($stime){
				$ptime = '';
				if($stime){
					$psec = time()-$stime;
					if($psec >= 86400){
						$D=($psec - ($psec % 86400)) / 86400;
						$ptime .= $D.PTIME_D;
						$psec -= $D*86400;
					}
					if($psec >= 3600){
						$H=($psec - ($psec % 3600)) / 3600;
						$ptime .= $H.PTIME_H;
						$psec -= $H*3600;
					}
					if($psec >= 60){
						$M=($psec - ($psec % 60)) / 60;
						$ptime .= $M.PTIME_M;
						$psec -= $M*60;
					}
					if($psec){
						$ptime .= $psec.PTIME_S;
					}
				}
				$smarty->assign('ptime',$ptime);
				$time = $ptime;
			}

			//画像ファイルとか処理
			if ( $picfile == true ) {
				$imagesize = getimagesize(TEMP_DIR.$picfile);
				$img_w = $imagesize[0];
				$img_h = $imagesize[1];
				rename( TEMP_DIR.$picfile , IMG_DIR.$picfile );
				chmod( IMG_DIR.$picfile , 0606);
				$picdat = strtr($picfile , 'png', 'dat');
				rename( TEMP_DIR.$picdat, IMG_DIR.$picdat );
				chmod( IMG_DIR.$picdat , 0606);

				$spchfile = str_replace('png','spch', $picfile);
				$pchfile = strtr($picfile , 'png', 'pch');
				
				if ( file_exists(TEMP_DIR.$pchfile) == TRUE ) {
					rename( TEMP_DIR.$pchfile, IMG_DIR.$pchfile );
					chmod( IMG_DIR.$pchfile , 0606);
				} elseif( file_exists(TEMP_DIR.$spchfile) == TRUE ) {
					rename( TEMP_DIR.$spchfile, IMG_DIR.$spchfile );
					chmod( IMG_DIR.$spchfile , 0606);
					$pchfile = $spchfile;
				} else {
					$pchfile = "";
				}
			} else {
				$img_w = 0;
				$img_h = 0;
				$pchfile = "";
			}

			// 二重投稿チェック
			if (empty($_POST["modid"])==true) {
				// スレ立ての場合
				$table = 'tablelog';
				$wid = 'tid';
			} else {
				// レスの場合
				$table = 'tabletree';
				$wid = 'iid';
			}
			//最新コメント取得
			$sqlw = "SELECT com FROM $table ORDER BY $wid DESC LIMIT 1";
			$msgw = $db->prepare($sqlw);
			$msgw->execute();
			$msgwc = $msgw->fetch();
			$msgwcom = $msgwc["com"]; //最新コメント取得できた
			//最新のホストも取得
			$sqlh = "SELECT host FROM $table ORDER BY $wid DESC LIMIT 1";
			$msgwh = $db->prepare($sqlh);
			$msgwh->execute();
			$msgwhc = $msgwh->fetch();
			$msgwhost = $msgwhc["host"]; //最新ホスト取得できた
			//どっちも一致すれば二重投稿だと思う
			if($com == $msgwcom && $host == $msgwhost ){
				$smarty->assign('message','二重投稿ですか？');
				$msgs = null;
				$msgw = null;
				$msgwh = null;
				$db = null; //db切断
				header('Location:'.PHP_SELF);
				def();
				exit;
			}

			// URLとメールにリンク
			if(AUTOLINK) $com = auto_link($com);
			// '>'色設定
			$com = preg_replace("/(^|>)((&gt;|＞)[^<]*)/i", "\\1".RE_START."\\2".RE_END, $com);

			// 連続する空行を一行
			$com = preg_replace("/\n((　| )*\n){3,}/","\n",$com);
			//改行をタグに
			if(TH_XHTML == 1){
				//<br />に
				$com = nl2br($com);
			} else {
				//<br>に
				$com = nl2br($com, false);
			}		

			//age_sageカウント 兼 レス数カウント
			$sql = "SELECT COUNT(*) as cnt FROM tabletree WHERE invz=0";
			$counts = $db->query("$sql");
			$count = $counts->fetch();
			$age = $count["cnt"];

			//スレッド数カウント
			$sql = "SELECT COUNT(*) as cnti FROM tablelog WHERE invz=0";
			$countsi = $db->query("$sql");
			$counti = $countsi->fetch();
			$logt = $counti["cnti"];

			// 値を追加する
			// スレ建ての場合
			if (empty($_POST["modid"])==true && $logt <= LOG_MAX_T) {
				//id生成
				$id = substr(crypt(md5($host.ID_SEED.date("Ymd", $utime)),'id'),-8);
				$sql = "INSERT INTO tablelog (created, modified, name, sub, com, mail, url, picfile, pchfile, img_w, img_h, utime, parent, time, pwd, id, exid, tree, age, invz, host) VALUES (datetime('now', 'localtime'), datetime('now', 'localtime'), '$name', '$sub', '$com', '$mail', '$url', '$picfile', '$pchfile', '$img_w', '$img_h', '$utime', '$parent', '$time', '$pwdh', '$id', '$exid', '$tree', '$age', '$invz', '$host')";
				$db = $db->exec($sql);
			} elseif(empty($_POST["modid"])==true && $logt > LOG_MAX_T) {
				//ログ行数オーバーの場合
				//id生成
				$id = substr(crypt(md5($host.ID_SEED.date("Ymd", $utime)),'id'),-8);
				$sql = "INSERT INTO tablelog (created, modified, name, sub, com, mail, url, picfile, pchfile, img_w, img_h, utime, parent, time, pwd, id, exid, tree, age, invz, host) VALUES (datetime('now', 'localtime'), datetime('now', 'localtime'), '$name', '$sub', '$com', '$mail', '$url', '$picfile', '$pchfile', '$img_w', '$img_h', '$utime', '$parent', '$time', '$pwdh', '$id', '$exid', '$tree', '$age', '$invz', '$host')";
				$db->exec($sql);
				//最初の行にある画像の名前を取得
				$sqlimg = "SELECT picfile FROM tablelog ORDER BY tid LIMIT 1";
				$msgs = $db->prepare($sqlimg);
				$msgs->execute();
				$msg = $msgs->fetch();
				$msgpic = $msg["picfile"]; //画像の名前取得できた
				//画像とかの削除処理
				if (file_exists(IMG_DIR.$msgpic)) {
					$msgdat = str_replace( strrchr($msgpic,"."), "", $msgpic); //拡張子除去
					if (file_exists(IMG_DIR.$msgdat.'.png')) {
						unlink(IMG_DIR.$msgdat.'.png');
					}
					if (file_exists(IMG_DIR.$msgdat.'.jpg')) {
						unlink(IMG_DIR.$msgdat.'.jpg'); //一応jpgも
					}
					if (file_exists(IMG_DIR.$msgdat.'.pch')) {
						unlink(IMG_DIR.$msgdat.'.pch'); 
					}
					if (file_exists(IMG_DIR.$msgdat.'.spch')) {
						unlink(IMG_DIR.$msgdat.'.spch'); 
					}
					if (file_exists(IMG_DIR.$msgdat.'.dat')) {
						unlink(IMG_DIR.$msgdat.'.dat'); 
					}
				}
				//↑画像とか削除処理完了
				//db最初の行を削除
				$sqldel = "DELETE FROM tablelog ORDER BY tid LIMIT 1";
				$db = $db->exec($sqldel);
			} elseif(empty($_POST["modid"])!=true && strpos($mail,'sage')!==false ) {
				//レスの場合でメール欄にsageが含まれる
				$tid = $_POST["modid"];
				//id生成
				$id = substr(crypt(md5($host.ID_SEED.date("Ymd", $utime)),'id'),-8);
				if ($age <= LOG_MAX_R) {
					$sql = "INSERT INTO tabletree (created, modified, tid, name, sub, com, mail, url, picfile, pchfile, img_w, img_h, utime, parent, time, pwd, id, exid, tree, invz, host) VALUES (datetime('now', 'localtime') , datetime('now', 'localtime') , '$tid', '$name', '$sub', '$com', '$mail', '$url', '$picfile', '$pchfile', '$img_w', '$img_h', '$utime', '$parent', '$time', '$pwdh', '$id', '$exid', '$tree', '$invz', '$host')";
					$db = $db->exec($sql);
				} else {
					//ログ行数オーバーの場合
					$sql = "INSERT INTO tabletree (created, modified, tid, name, sub, com, mail, url, picfile, pchfile, img_w, img_h, utime, parent, time, pwd, id, exid, tree, invz, host) VALUES (datetime('now', 'localtime') , datetime('now', 'localtime') , '$tid', '$name', '$sub', '$com', '$mail', '$url', '$picfile', '$pchfile', '$img_w', '$img_h', '$utime', '$parent', '$time', '$pwdh', '$id', '$exid', '$tree', '$invz', '$host')";
					$db->exec($sql);
					//レス画像貼りは今のところ未対応だけど念のため
					//最初の行にある画像の名前を取得
					$sqlimg = "SELECT picfile FROM tabletree ORDER BY iid LIMIT 1";
					$msgs = $db->prepare($sqlimg);
					$msgs->execute();
					$msg = $msgs->fetch();
					$msgpic = $msg["picfile"]; //画像の名前取得できた
					//画像とかの削除処理
					if (file_exists(IMG_DIR.$msgpic)) {
						$msgdat = str_replace( strrchr($msgpic,"."), "", $msgpic); //拡張子除去
						if (file_exists(IMG_DIR.$msgdat.'.png')) {
						unlink(IMG_DIR.$msgdat.'.png');
						}
						if (file_exists(IMG_DIR.$msgdat.'.jpg')) {
							unlink(IMG_DIR.$msgdat.'.jpg'); //一応jpgも
						}
						if (file_exists(IMG_DIR.$msgdat.'.pch')) {
							unlink(IMG_DIR.$msgdat.'.pch'); 
						}
						if (file_exists(IMG_DIR.$msgdat.'.spch')) {
							unlink(IMG_DIR.$msgdat.'.spch'); 
						}
						if (file_exists(IMG_DIR.$msgdat.'.dat')) {
							unlink(IMG_DIR.$msgdat.'.dat'); 
						}
					}
					//↑画像とか削除処理完了
					//db最初の行を削除
					$sqlresdel = "DELETE FROM tabletree ORDER BY iid LIMIT 1";
					$db = $db->exec($sqlresdel);
				}
			} else {
				//レスの場合でメール欄にsageが含まれない
				$tid = $_POST["modid"];
				//id生成
				$id = substr(crypt(md5($host.ID_SEED.date("Ymd", $utime)),'id'),-8);
				$nage = $age +1;
				if ($age <= LOG_MAX_R) {
					$sql = "INSERT INTO tabletree (created, modified, tid, name, sub, com, mail, url, picfile, pchfile, img_w, img_h, utime, parent, time, pwd, id, exid, tree, invz, host) VALUES (datetime('now', 'localtime') , datetime('now', 'localtime') , '$tid', '$name', '$sub', '$com', '$mail', '$url', '$picfile', '$pchfile', '$img_w', '$img_h', '$utime', '$parent', '$time', '$pwdh', '$id', '$exid', '$tree', '$invz', '$host'); UPDATE tablelog set age = '$nage' where tid = '$tid'";
					$db = $db->exec($sql);
				} else {
					//ログ行数オーバーの場合
					$sql = "INSERT INTO tabletree (created, modified, tid, name, sub, com, mail, url, picfile, pchfile, img_w, img_h, utime, parent, time, pwd, id, exid, tree, invz, host) VALUES (datetime('now', 'localtime') , datetime('now', 'localtime') , '$tid', '$name', '$sub', '$com', '$mail', '$url', '$picfile', '$pchfile', '$img_w', '$img_h', '$utime', '$parent', '$time', '$pwdh', '$id', '$exid', '$tree', '$invz', '$host')";
					$db->exec($sql);
					//レス画像貼りは今のところ未対応だけど念のため
					//最初の行にある画像の名前を取得
					$sqlimg = "SELECT picfile FROM tabletree ORDER BY iid LIMIT 1";
					$msgs = $db->prepare($sqlimg);
					$msgs->execute();
					$msg = $msgs->fetch();
					$msgpic = $msg["picfile"]; //画像の名前取得できた
					//画像とかの削除処理
					if (file_exists(IMG_DIR.$msgpic)) {
						$msgdat = str_replace( strrchr($msgpic,"."), "", $msgpic); //拡張子除去
						if (file_exists(IMG_DIR.$msgdat.'.png')) {
						unlink(IMG_DIR.$msgdat.'.png');
						}
						if (file_exists(IMG_DIR.$msgdat.'.jpg')) {
							unlink(IMG_DIR.$msgdat.'.jpg'); //一応jpgも
						}
						if (file_exists(IMG_DIR.$msgdat.'.pch')) {
							unlink(IMG_DIR.$msgdat.'.pch'); 
						}
						if (file_exists(IMG_DIR.$msgdat.'.spch')) {
							unlink(IMG_DIR.$msgdat.'.spch'); 
						}
						if (file_exists(IMG_DIR.$msgdat.'.dat')) {
							unlink(IMG_DIR.$msgdat.'.dat'); 
						}
					}
					//↑画像とか削除処理完了
					//db最初の行を削除
					$sqlresdel = "DELETE FROM tabletree ORDER BY iid LIMIT 1";
					$db = $db->exec($sqlresdel);
				}
			}

			$c_pass = $pwd;
			$names = $name;

			//-- クッキー保存 --
			//漢字を含まない項目はこちらの形式で追加
			setcookie ("pwdc", $c_pass,time()+(SAVE_COOKIE*24*3600));

			//クッキー項目："クッキー名<>クッキー値"　※漢字を含む項目はこちらに追加
			$cooks = array("namec<>".$names,"emailc<>".$mail,"urlc<>".$url);

			foreach ( $cooks as $cook ) {
				list($c_name,$c_cookie) = explode('<>',$cook);
				// $c_cookie = str_replace("&amp;", "&", $c_cookie);
				setcookie ($c_name, $c_cookie,time()+(SAVE_COOKIE*24*3600));
			}

			$smarty->assign('message','書き込みに成功しました。');
			$msgs = null;
			$msgw = null;
			$count = null;
			$counts = null;
			$db = null; //db切断
		}
	} catch (PDOException $e) {
		echo "DB接続エラー:" .$e->getMessage();
	}
	unset($name,$mail,$sub,$com,$url,$pwd,$pwdh,$resto,$pictmp,$picfile,$mode);
	header('Location:'.PHP_SELF);
	def();
}

//通常時
function def() {
	global $smarty;
	$dspres = DSP_RES;
	$page_def = PAGE_DEF;
	$smarty->assign('dspres',$dspres);

	//ページング
	try {
		$db = new PDO("sqlite:noe.db");
		if (isset($_GET['page']) && is_numeric($_GET['page'])) {
			$page = $_GET['page'];
			$page = max($page,1);
		} else {
			$page = 1;
		}
		$start = $page_def * ($page - 1);

		//最大何ページあるのか
		$sql = "SELECT COUNT(*) as cnt FROM tablelog WHERE invz=0";
		$counts = $db->query("$sql");
		$count = $counts->fetch();
		$max_page = floor($count["cnt"] / $page_def) + 1;
		$smarty->assign('max_page',$max_page);

		//リンク作成用
		$smarty->assign('nowpage',$page);
		$p = 1;
		$pp = array();
		$paging = array();
		while ($p <= $max_page) {
			$paging[($p)] = compact('p');
			$pp[] = $paging;
			$p++;
		}
		$smarty->assign('paging',$paging);
		$smarty->assign('pp',$pp);

		$smarty->assign('back',$page - 1);

		$smarty->assign('next',$page + 1);

		$db = null;
	} catch (PDOException $e) {
		echo "DB接続エラー:" .$e->getMessage();
	}
	//読み込み
	//1ページの全スレッド取得
	try {
		$db = new PDO("sqlite:noe.db");
		$sql = "SELECT * FROM tablelog WHERE invz=0 ORDER BY age DESC, tree DESC LIMIT $start,$page_def"; 
		$posts = $db->query($sql);
		$oya = array();
		while ($bbsline = $posts->fetch() ) {
			$oya[] = $bbsline;
		} 
		$smarty->assign('oya',$oya);

		//スレッドの記事を取得
		$sqli = "SELECT * FROM tabletree WHERE invz=0 ORDER BY tree DESC";
		$postsi = $db->query($sqli);
		$ko = array();
		while ($res = $postsi->fetch() ) {
			$ko[] = $res;
		}
		$smarty->assign('ko',$ko);


		$smarty->assign('path',IMG_DIR);


		//$smarty->debugging = true;
		$smarty->display(THEMEDIR.MAINFILE);
		$db = null;
	} catch (PDOException $e) {
		echo "DB接続エラー:" .$e->getMessage();
	}
}

//そうだね
function sodane(){
	global $resto;
	global $smarty;
	$resto = $_GET["resto"];
	try {
		$db = new PDO("sqlite:noe.db");
		$sql = "UPDATE tablelog set exid = exid+1 where tid = '$resto'";
		$db = $db->exec($sql);
		$db = null;
		$smarty->assign('message','そうだね。');
	} catch (PDOException $e) {
		echo "DB接続エラー:" .$e->getMessage();
	}
	header('Location:'.PHP_SELF);
	def();
}

//レスそうだね
function rsodane(){
	global $resto;
	global $smarty;
	$resto = $_GET["resto"];
	try {
		$db = new PDO("sqlite:noe.db");
		$sql = "UPDATE tabletree set exid = exid+1 where iid = '$resto'";
		$db = $db->exec($sql);
		$db = null;
		$smarty->assign('message','そうだね。');
	} catch (PDOException $e) {
		echo "DB接続エラー:" .$e->getMessage();
	}
	header('Location:'.PHP_SELF);
	def();
}

//レス画面

function res(){
	global $resno;
	global $smarty;
	$resno = $_GET["res"];
	$smarty->assign('resno',$resno);
	try {
		$db = new PDO("sqlite:noe.db");
		$sql = "SELECT * FROM tablelog WHERE tid=".$resno." ORDER BY tree DESC";
		$posts = $db->query($sql);
	
		$oya = array();
		while ($bbsline = $posts->fetch() ) {
			//スレッドの記事を取得
			$sqli = "SELECT * FROM tabletree WHERE (invz=0 AND tid=".$resno.") ORDER BY tree DESC";
			$postsi = $db->query($sqli);
			$ko = array();
			while ($res = $postsi->fetch()){
				$ko[] = $res;
				$smarty->assign('ko',$ko);
			}
			$oya[] = $bbsline;
			$smarty->assign('oya',$oya);
		}
		$db = null;
	} catch (PDOException $e) {
		echo "DB接続エラー:" .$e->getMessage();
	}
	
	$smarty->assign('path',IMG_DIR);
	
	$smarty->display( THEMEDIR.RESFILE );
}

//お絵描き画面

function paintform(){
	global $message,$usercode,$quality,$qualitys;
	global $smarty;

	//NEOを使う or しぃペインター
	if (filter_input(INPUT_POST, 'useneo')){
		$useneo = true;
		$smarty->assign('useneo',true);
	} else {
		$useneo = false;
		$smarty->assign('useneo',false);
	}

	$smarty->assign('mode','piccom');

	$smarty->assign('btitle',TITLE);
	$smarty->assign('home',HOME);
	$smarty->assign('self',PHP_SELF);
	$smarty->assign('message',$message);
	$smarty->assign('pdefw',PDEF_W);
	$smarty->assign('pdefh',PDEF_H);
	$smarty->assign('skindir',THEMEDIR);
	$smarty->assign('tver',TEMPLATE_VER);

	$picw = filter_input(INPUT_POST, 'picw',FILTER_VALIDATE_INT);
	$smarty->assign('picw',$picw);
	$pich = filter_input(INPUT_POST, 'pich',FILTER_VALIDATE_INT);
	$smarty->assign('pich',$pich);
	$anime = isset($_POST["anime"]) ? true : false;
	$smarty->assign('anime',$anime);
	
	if($picw < 300) $picw = 300;
	if($pich < 300) $pich = 300;
	if($picw > PMAX_W) $picw = PMAX_W;
	if($pich > PMAX_H) $pich = PMAX_H;

	if(!$useneo) { //しぃペインターの時の幅と高さ
		$ww = $picw + 510;
		$hh = $pich + 172;
	} else { //NEOのときの幅と高さ
		$ww = $picw + 150;
		$hh = $pich + 172;
	}
	if($hh < 560){$hh = 560;}//共通の最低高
	$smarty->assign('w',$ww);
	$smarty->assign('h',$hh);
	
	$smarty->assign('undo',UNDO);
	$smarty->assign('undo_in_mg',UNDO_IN_MG);

	$smarty->assign('useanime',USE_ANIME);

	$smarty->assign('path',IMG_DIR);

	$smarty->assign('usercode',$usercode);
	$smarty->assign('stime',time());

	$userip = get_uip();

	if((SECURITY_CLICK || SECURITY_TIMER) && SECURITY_URL){
		$smarty->assign('security',true);
		$smarty->assign('security_click',SECURITY_CLICK);
		$smarty->assign('security_timer',SECURITY_TIMER);
	}

	//しぃペインター
	$smarty->assign('layer_count',LAYER_COUNT);
	$qq = $quality ? $quality : $qualitys[0];
	$smarty->assign('quality',$qq);

	//出力
	$smarty->display( THEMEDIR.PAINTFILE );
}

//アニメ再生

function openpch($pch,$sp="") {
	global $smarty;
	$message = "";

	$pch = $_GET["pch"];
	$pchh = str_replace( strrchr($pch,"."), "", $pch); //拡張子除去
	$extn = substr($pch, strrpos($pch, '.') + 1); //拡張子取得

	$picfile = IMG_DIR.$pchh.".png";

	if($extn=='spch'){
		$pchfile = IMG_DIR.$pch;
		$smarty->assign('useneo',false); //拡張子がspchのときはしぃぺ
	}elseif($extn=='pch'){
		$pchfile = IMG_DIR.$pch;
		$smarty->assign('useneo',true); //拡張子がpchのときはNEO
	}else { //動画が無い時は処理しない
		$w=$h=$picw=$pich=$datasize="";
		$smarty->assign('useneo',true);
	}
	$datasize = filesize($pchfile);
	$size = getimagesize($picfile);
	if(!$sp) $sp = PCH_SPEED;
	$picw = $size[0];
	$pich = $size[1];
	$w = $picw;
	$h = $pich + 26;
	if($w < 300){$w = 300;}
	if($h < 326){$h = 326;}

	$smarty->assign('btitle',TITLE);
	$smarty->assign('home',HOME);
	$smarty->assign('self',PHP_SELF);
	$smarty->assign('message',$message);
	$smarty->assign('pdefw',PDEF_W);
	$smarty->assign('pdefh',PDEF_H);
	$smarty->assign('skindir',THEMEDIR);
	$smarty->assign('tver',TEMPLATE_VER);

	$smarty->assign('picw',$picw);
	$smarty->assign('pich',$pich);
	$smarty->assign('w',$w);
	$smarty->assign('h',$h);
	$smarty->assign('pchfile','./'.$pch);
	$smarty->assign('datasize',$datasize);

	$smarty->assign('speed',PCH_SPEED);

	$smarty->assign('path',IMG_DIR);

	$smarty->display( THEMEDIR.ANIMEFILE );
}

//お絵かき投稿
function paintcom(){
	global $usercode,$stime;
	global $smarty;

	$smarty->assign('btitle',TITLE);
	$smarty->assign('home',HOME);
	$smarty->assign('self',PHP_SELF);
	$message = "";
	$smarty->assign('message',$message);
	$smarty->assign('pdefw',PDEF_W);
	$smarty->assign('pdefh',PDEF_H);
	$smarty->assign('skindir',THEMEDIR);
	$smarty->assign('tver',TEMPLATE_VER);

	$smarty->assign('parent',$_SERVER['REQUEST_TIME']);
	$smarty->assign('stime',$stime);

	$smarty->assign('usercode',$usercode);

	//----------

	//var_dump($_POST);

	//テンポラリ画像リスト作成
	$tmplist = array();
	$handle = @opendir(TEMP_DIR);
	while ($file = readdir($handle)) {
		if(!is_dir($file) && preg_match("/\.(dat)$/i",$file)) {
			$fp = fopen(TEMP_DIR.$file, "r");
			$userdata = fread($fp, 1024);
			fclose($fp);
			list($uip,$uhost,$uagent,$imgext,$ucode,) = explode("\t", rtrim($userdata));
			$file_name = preg_replace("/\.(dat)$/i","",$file);
			if(@file_exists(TEMP_DIR.$file_name.$imgext)) //画像があればリストに追加
				$tmplist[] = $ucode."\t".$uip."\t".$file_name.$imgext;
		}
	}
	closedir($handle);
	$tmp = array();
	if(count($tmplist)!=0){
		//user-codeでチェック
		foreach($tmplist as $tmpimg){
			list($ucode,$uip,$ufilename) = explode("\t", $tmpimg);
			if($ucode == $usercode){
				$tmp[] = $ufilename;
			}
		}
		//user-codeでhitしなければIPで再チェック
		if(count($tmp)==0){
			$userip = getenv("HTTP_CLIENT_IP");
			if(!$userip) $userip = getenv("HTTP_X_FORWARDED_FOR");
			if(!$userip) $userip = getenv("REMOTE_ADDR");
			foreach($tmplist as $tmpimg){
				list($ucode,$uip,$ufilename) = explode("\t", $tmpimg);
				if(!IP_CHECK || $uip == $userip)
					$tmp[] = $ufilename;
			}
		}
	}

	$post_mode = true;
	$regist = true;
	if(IP_CHECK) $ipcheck = true;
	if(count($tmp)==0){
		$notmp = true;
		$pictmp = 1;
	}else{
		$pictmp = 2;
		sort($tmp);
		reset($tmp);
		$temp = array();
		foreach($tmp as $tmpfile){
			$src = TEMP_DIR.$tmpfile;
			$srcname = $tmpfile;
			$date = gmdate("Y/m/d H:i", filemtime($src)+9*60*60);
			$temp[] = compact('src','srcname','date');
		}
		$smarty->assign('temp', $temp);
	}

	$tmp2 = array();
	$smarty->assign('tmp',$tmp2);

	$smarty->assign('path',IMG_DIR);

	//$smarty->debugging = true;
	$smarty->display( THEMEDIR.PICFILE );
}

//削除くん

function delmode(){
	global $admin_pass;
	global $smarty;
	$delno = $_POST["delno"];
	$delt = $_POST["delt"]; //0親1レス削除

	if ($delt == 0) {
		$deltable = 'tablelog';
		$idk = "tid";
	} else {
		$deltable = 'tabletree';
		$idk = "iid";
	}
	//記事呼び出し
	try {
		$db = new PDO("sqlite:noe.db");

		//パスワードを取り出す
		$sql ="SELECT pwd FROM $deltable WHERE $idk = $delno";
		$msgs = $db->prepare($sql);
		$msgs->execute();
		$msg = $msgs->fetch();

		//削除記事の画像を取り出す
		$sqlp ="SELECT picfile FROM $deltable WHERE $idk = $delno";
		$msgsp = $db->prepare($sqlp);
		$msgsp->execute();
		$msgp = $msgsp->fetch();
		$msgpic = $msgp['picfile']; //画像の名前取得できた

		if (isset($_POST["admindel"]) == true) {
			$admindelmode = 1;
		} else {
			$admindelmode = 0;
		}

		if (password_verify($_POST["pwd"],$msg['pwd']) === true) {
			//画像とかファイル削除
			if (file_exists(IMG_DIR.$msgpic)) {
				$msgdat = str_replace( strrchr($msgpic,"."), "", $msgpic); //拡張子除去
				if (file_exists(IMG_DIR.$msgdat.'.png')) {
					unlink(IMG_DIR.$msgdat.'.png');
				}
				if (file_exists(IMG_DIR.$msgdat.'.jpg')) {
					unlink(IMG_DIR.$msgdat.'.jpg'); //一応jpgも
				}
				if (file_exists(IMG_DIR.$msgdat.'.pch')) {
					unlink(IMG_DIR.$msgdat.'.pch'); 
				}
				if (file_exists(IMG_DIR.$msgdat.'.spch')) {
					unlink(IMG_DIR.$msgdat.'.spch'); 
				}
				if (file_exists(IMG_DIR.$msgdat.'.dat')) {
					unlink(IMG_DIR.$msgdat.'.dat'); 
				}
			}
			//↑画像とか削除処理完了
			//データベースから削除
			$sql = "DELETE FROM $deltable WHERE $idk=$delno";
			$db = $db->exec($sql);
			$smarty->assign('message','削除しました。');
		} elseif ($admin_pass == $_POST["pwd"] && $admindelmode == 1) {
			//画像とかファイル削除
			if (file_exists(IMG_DIR.$msgpic)) {
				$msgdat = str_replace( strrchr($msgpic,"."), "", $msgpic); //拡張子除去
				if (file_exists(IMG_DIR.$msgdat.'.png')) {
					unlink(IMG_DIR.$msgdat.'.png');
				}
				if (file_exists(IMG_DIR.$msgdat.'.jpg')) {
					unlink(IMG_DIR.$msgdat.'.jpg'); //一応jpgも
				}
				if (file_exists(IMG_DIR.$msgdat.'.pch')) {
					unlink(IMG_DIR.$msgdat.'.pch'); 
				}
				if (file_exists(IMG_DIR.$msgdat.'.spch')) {
					unlink(IMG_DIR.$msgdat.'.spch'); 
				}
				if (file_exists(IMG_DIR.$msgdat.'.dat')) {
					unlink(IMG_DIR.$msgdat.'.dat'); 
				}
			}
			//↑画像とか削除処理完了
			//データベースから削除
			$sql = "DELETE FROM $deltable WHERE $idk=$delno";
			$db = $db->exec($sql);
			$smarty->assign('message','削除しました。');
		} elseif ($admin_pass == $_POST["pwd"] && $admindelmode != 1) {
			//管理モード以外での管理者削除は
			//データベースから削除はせずに非表示
			$sql = "UPDATE $deltable SET invz=1 WHERE $idk=$delno";
			$db = $db->exec($sql);
			$smarty->assign('message','削除しました。');
		} else {
			$smarty->assign('message','パスワードまたは記事番号が違います。');
		}
		$db = null; 
		$msgp = null;
		$msg = null;//db切断 
	} catch (PDOException $e) {
		echo "DB接続エラー:" .$e->getMessage();
	}
	//変数クリア
	unset($delno,$delt);
	header('Location:'.PHP_SELF);
	def();
}

//編集モードくん入口
function editform() {
	global $admin_pass;
	global $smarty;

	$editno = $_POST["delno"];
	$editt = $_POST["delt"]; //0親1レス
	if ($editt == 0) {
		$edittable = 'tablelog';
		$idk = "tid";
	} else {
		$edittable = 'tabletree';
		$idk = "iid";
		$smarty->assign('resedit','resedit');
	}
	//記事呼び出し
	try {
		$db = new PDO("sqlite:noe.db");

		//パスワードを取り出す
		$sql ="SELECT pwd FROM $edittable WHERE $idk = $editno";
		$msgs = $db->prepare($sql);
		$msgs->execute();
		$msg = $msgs->fetch();

		if (password_verify($_POST["pwd"],$msg['pwd']) === true) {
			//パスワードがあってたら
			$sqli ="SELECT * FROM $edittable WHERE $idk = $editno";
			$posts = $db->query($sqli);
			$oya = array();
			while ($bbsline = $posts->fetch() ) {
				$oya[] = $bbsline;
				$smarty->assign('oya',$oya);
			}
			$smarty->assign('message','編集モード...');
		} elseif ($admin_pass == $_POST["pwd"] ) {
			//管理者編集モード
			$sqli ="SELECT * FROM $edittable WHERE $idk = $editno";
			$posts = $db->query($sqli);
			$oya = array();
			while ($bbsline = $posts->fetch() ) {
				$oya[] = $bbsline;
				$smarty->assign('oya',$oya);
			}
			$smarty->assign('message','管理者編集モード...');
		} else {
			$smarty->assign('message','パスワードまたは記事番号が違います。');
			$db = null; 
			$msgs = null;
			$msg = null;//db切断 
			def();
			exit;
		}
		$db = null; 
		$msgs = null;
		$posts = null;
		$msg = null;//db切断 

		$smarty->assign('othermode','edit'); //編集モード
		$smarty->display( THEMEDIR.OTHERFILE );
	} catch (PDOException $e) {
		echo "DB接続エラー:" .$e->getMessage();
	}
}

//編集モードくん本体
function editexec(){
	global $smarty;
	global $badstring,$badip;
	global $badstr_A,$badstr_B,$badname;
	$userip = get_uip();

	$sub = ( isset( $_POST["sub"] ) === true ) ? newstring($_POST["sub"]): "";
	$name = ( isset( $_POST["name"] ) === true ) ? newstring($_POST["name"]): "";
	$url = ( isset( $_POST["url"] )  === true ) ? newstring(trim($_POST["url"]))  : "";
	$mail = ( isset( $_POST["mail"] )  === true ) ? newstring(trim($_POST["mail"]))  : "";
	$com = ( isset( $_POST["com"] )  === true ) ? newstring(trim($_POST["com"]))  : "";
	$pwd = ( isset( $_POST["pwd"] )  === true ) ? newstring(trim($_POST["pwd"]))  : "";
	$pwdh = password_hash($pwd,PASSWORD_DEFAULT);
	$exid = ( isset( $_POST["exid"] )  === true ) ? newstring(trim($_POST["exid"]))  : "";
	$resedit = ( isset( $_POST["resedit"] )  === true ) ? newstring(trim($_POST["resedit"]))  : "";
	$e_no = ( isset( $_POST["e_no"] )  === true ) ? newstring(trim($_POST["e_no"]))  : "";

	//チェックする項目から改行・スペース・タブを消す
	$chk_com  = preg_replace("/\s/u", "", $com );
	$chk_name = preg_replace("/\s/u", "", $name );
	$chk_sub = preg_replace("/\s/u", "", $sub );
	$chk_mail = preg_replace("/\s/u", "", $mail );

	//本文に日本語がなければ拒絶
	if (USE_JAPANESEFILTER) {
		mb_regex_encoding("UTF-8");
		if (strlen($com) > 0 && !preg_match("/[ぁ-んァ-ヶー一-龠]+/u",$chk_com)) {
			error(MSG035);
			exit;
		}
	}

	//本文へのURLの書き込みを禁止
	if(DENY_COMMENTS_URL && preg_match('/:\/\/|\.co|\.ly|\.gl|\.net|\.org|\.cc|\.ru|\.su|\.ua|\.gd/i', $com)) {error(MSG036); exit;}

	foreach($badstring as $value){//拒絶する文字列
		if($value===''){
		break;
		}
		if(preg_match("/$value/ui",$chk_com)||preg_match("/$value/ui",$chk_sub)||preg_match("/$value/ui",$chk_name)||preg_match("/$value/ui",$chk_mail)){
			error(MSG032);
			exit;
		}
	}
	unset($value);	
	if(isset($badname)){//使えない名前
		foreach($badname as $value){
			if($value===''){
			break;
			}
			if(preg_match("/$value/ui",$chk_name)){
				error(MSG037);
				exit;
			}
		}
		unset($value);	
	}

	$bstr_A_find=false;
	$bstr_B_find=false;

	foreach($badstr_A as $value){//指定文字列が2つあると拒絶
		if($value===''){
		break;
		}
		if(preg_match("/$value/ui",$chk_com)||preg_match("/$value/ui",$chk_sub)||preg_match("/$value/ui",$chk_name)||preg_match("/$value/ui",$chk_mail)){
			$bstr_A_find=true;
		break;
		}
	}
	unset($value);
	foreach($badstr_B as $value){
		if($value===''){
			break;
		}
		if(preg_match("/$value/ui",$chk_com)||preg_match("/$value/ui",$chk_sub)||preg_match("/$value/ui",$chk_name)||preg_match("/$value/ui",$chk_mail)){
			$bstr_B_find=true;
		break;
		}
	}
	unset($value);
	if($bstr_A_find && $bstr_B_find){
		error(MSG032);
		exit;
	}

	if(USE_NAME&&!$name) {error(MSG009);exit;}
	if(USE_COM&&!$com) {error(MSG008);exit;}
	if(USE_SUB&&!$sub) {error(MSG010);exit;}

	if(strlen($com) > MAX_COM) {error(MSG011);exit;}
	if(strlen($name) > MAX_NAME) {error(MSG012);exit;}
	if(strlen($mail) > MAX_EMAIL) {error(MSG013);exit;}
	if(strlen($sub) > MAX_SUB) {error(MSG014);exit;}

	//ホスト取得
	$host = gethostbyaddr($userip);

	foreach($badip as $value){ //拒絶host
		if(preg_match("/$value$/i",$host)) {error(MSG016);exit;}
	}
	//↑セキュリティ関連ここまで

	// URLとメールにリンク
	if(AUTOLINK) $com = auto_link($com);
	// '>'色設定
	$com = preg_replace("/(^|>)((&gt;|＞)[^<]*)/i", "\\1".RE_START."\\2".RE_END, $com);

	// 連続する空行を一行
	$com = preg_replace("/\n((　| )*\n){3,}/","\n",$com);
	//改行をタグに
	if(TH_XHTML == 1){
		//<br />に
		$com = nl2br($com);
	} else {
		//<br>に
		$com = nl2br($com, false);
	}	

	if($resedit == 1) {
		$edittable = 'tabletree';
		$eid = 'iid';
	} else {
		$edittable = 'tablelog';
		$eid = 'tid';
	}

	try {
		$db = new PDO("sqlite:noe.db");
		$sql = "UPDATE $edittable set modified = datetime('now', 'localtime'), name = '$name', mail = '$mail', sub = '$sub', com = '$com', url = '$url', host = '$host', exid = '$exid', pwd = '$pwdh' where $eid = $e_no";
		$db = $db->exec($sql);
		$db = null;
		$smarty->assign('message','編集完了しました。');
	} catch (PDOException $e) {
		echo "DB接続エラー:" .$e->getMessage();
	}
	unset($name,$mail,$sub,$com,$url,$pwd,$pwdh,$resto,$pictmp,$picfile,$mode);
	header('Location:'.PHP_SELF);
	def();
}

//管理モードin
function admin_in() {
	global $self;
	echo '<!DOCTYPE html>'."\n".'<head><meta charset="utf-8"><title>管理モードin</title></head>';
	echo '<body><h1>管理モードin</h1><form action="'.$self.'?mode=admin" method="post"><input type="hidden" name="admin" value="admin"><input class="form" type="password" name="adminpass" size="8">
	<input class="button" type="submit" value="SUBMIT"></form></body>';
}

//管理モード
function admin() {
	global $admin_pass;
	global $smarty;
	$message = "";

	$smarty->assign('btitle',TITLE);
	$smarty->assign('home',HOME);
	$smarty->assign('self',PHP_SELF);
	$smarty->assign('message',$message);
	$smarty->assign('pdefw',PDEF_W);
	$smarty->assign('pdefh',PDEF_H);
	$smarty->assign('skindir',THEMEDIR);
	$smarty->assign('tver',TEMPLATE_VER);

	$smarty->assign('useanime',USE_ANIME);
	$smarty->assign('defanime',DEF_ANIME);

	$smarty->assign('path',IMG_DIR);

	//ページング
	if (isset($_GET['page']) && is_numeric($_GET['page'])) {
		$page = $_GET['page'];
		$page = max($page,1);
	} else {
		$page = 1;
	}
	$page10 = PAGE_DEF * 10;
	$start = $page10 * ($page - 1);

	//最大何ページあるのか
	//記事呼び出しから
	try {
		$db = new PDO("sqlite:noe.db");
		$sql = "SELECT COUNT(*) as cnt FROM tablelog WHERE invz=0";
		$counts = $db->query("$sql");
		$count = $counts->fetch();
		$max_page = floor($count["cnt"] / $page10) + 1;
		$smarty->assign('max_page',$max_page);

		//リンク作成用
		$smarty->assign('nowpage',$page);
		$p = 1;
		$pp = array();
		$paging = array();
		while ($p <= $max_page) {
			$paging[($p)] = compact('p');
			$pp[] = $paging;
			$p++;
		}
		$smarty->assign('paging',$paging);
		$smarty->assign('pp',$pp);

		$smarty->assign('back',$page - 1);

		$smarty->assign('next',$page + 1);

		//読み込み
		$adminpass = ( isset( $_POST["adminpass"] ) === true ) ? newstring($_POST["adminpass"]): "";
		if ($adminpass == $admin_pass) {
			$sql = "SELECT * FROM tablelog ORDER BY tree DESC LIMIT ".$start.",".$page10;
			$oya = array();
			$posts = $db->query($sql);
			while ($bbsline = $posts->fetch() ) {
				$oya[] = $bbsline;
			} 
			$smarty->assign('oya',$oya);

			//スレッドの記事を取得
			$sqli = "SELECT * FROM tabletree WHERE invz=0 ORDER BY tree DESC";
			$ko = array();
			$postsi = $db->query($sqli);
			while ($res = $postsi->fetch()){
				$ko[] = $res;
			}
			$smarty->assign('ko',$ko);
			$smarty->display( THEMEDIR.ADMINFILE );
		} else {
			echo "管理パスを入力してください";
		}
		$db = null; //db切断
	} catch (PDOException $e) {
		echo "DB接続エラー:" .$e->getMessage();
	}
}

//エラー画面（画面？？）
function error($mes) {
	global $db;
	global $smarty;
	$db = null; //db切断
	$smarty->assign('message',$mes);
	header('Location:'.PHP_SELF);
	def();
}


//初期設定

function init(){
	try {
		if (!file_exists('noe.db')) {
			// はじめての実行なら、テーブルを作成
			$db = new PDO("sqlite:noe.db");
			$db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);  
			$sql = "CREATE TABLE tablelog (tid integer primary key autoincrement, created timestamp, modified TIMESTAMP, name VARCHAR(".MAX_NAME."), mail VARCHAR(".MAX_EMAIL."), sub VARCHAR(".MAX_SUB."), com VARCHAR(".MAX_COM."), url VARCHAR(".MAX_URL."), host TEXT, exid TEXT, id TEXT, pwd TEXT, utime INT, picfile TEXT, pchfile TEXT, img_w INT, img_h INT, time TEXT, tree BIGINT, parent INT, age INT, invz VARCHAR(1))";
			$db = $db->query($sql);
			$db = null; //db切断
			$db = new PDO("sqlite:noe.db");
			$sql = "CREATE TABLE tabletree (iid integer primary key autoincrement, tid INT, created timestamp, modified TIMESTAMP, name VARCHAR(".MAX_NAME."), mail VARCHAR(".MAX_EMAIL."), sub VARCHAR(".MAX_SUB."), com VARCHAR(".MAX_COM."), url VARCHAR(".MAX_URL."), host TEXT, exid TEXT, id TEXT, pwd TEXT, utime INT, picfile TEXT, pchfile TEXT, img_w INT, img_h INT, time TEXT, tree BIGINT, parent INT, invz VARCHAR(1))";
			$db = $db->query($sql);
			$db = null; //db切断
		} else {
			$db = new PDO("sqlite:noe.db");
			$db = null; //db切断
		}
	} catch (PDOException $e) {
		echo "DB接続エラー:" .$e->getMessage();
	}
	$err='';
	if(!is_writable(realpath("./")))error("カレントディレクトリに書けません<br>");
	if(!is_dir(realpath(IMG_DIR))){
		mkdir(IMG_DIR,0707);chmod(IMG_DIR,0707);
	}
	if(!is_dir(realpath(IMG_DIR)))$err.=IMG_DIR."がありません<br>";
	if(!is_writable(realpath(IMG_DIR)))$err.=IMG_DIR."を書けません<br>";
	if(!is_readable(realpath(IMG_DIR)))$err.=IMG_DIR."を読めません<br>";

	if(!is_dir(realpath(TEMP_DIR))){
		mkdir(TEMP_DIR,0707);chmod(TEMP_DIR,0707);
	}
	if(!is_dir(realpath(TEMP_DIR)))$err.=TEMP_DIR."がありません<br>";
	if(!is_writable(realpath(TEMP_DIR)))$err.=TEMP_DIR."を書けません<br>";
	if(!is_readable(realpath(TEMP_DIR)))$err.=TEMP_DIR."を読めません<br>";
	if($err)error($err);
}

/* テンポラリ内のゴミ除去 */
function deltemp(){
	$handle = opendir(TEMP_DIR);
	while ($file = readdir($handle)) {
		if(!is_dir($file)) {
			$lapse = time() - filemtime(TEMP_DIR.$file);
			if($lapse > (TEMP_LIMIT*24*3600)){
				unlink(TEMP_DIR.$file);
			}
			//pchアップロードペイントファイル削除
			if(preg_match("/\A(pchup-.*-tmp\.s?pch)\z/i",$file)) {
				$lapse = time() - filemtime(TEMP_DIR.$file);
				if($lapse > (300)){//5分
					unlink(TEMP_DIR.$file);
				}
			}
		}
	}
	closedir($handle);
}

/*-----------Main-------------*/
init();		//←初期設定後は不要なので削除可
deltemp();

//user-codeの発行
if(!$usercode){//falseなら発行
	$userip = get_uip();
	$usercode = substr(crypt(md5($userip.ID_SEED.date("Ymd", time())),'id'),-12);
	//念の為にエスケープ文字があればアルファベットに変換
	$usercode = strtr($usercode,"!\"#$%&'()+,/:;<=>?@[\\]^`/{|}~","ABCDEFGHIJKLMNOabcdefghijklmn");
}
setcookie("usercode", $usercode, time()+86400*365);//1年間

/*-----------mode-------------*/

switch($mode){
	case 'regist':
		regist();
		break;
	case 'res':
		res();
		break;
	case 'sodane':
		sodane();
		break;
	case 'rsodane':
		rsodane();
		break;
	case 'paint':
		$palette = "";
		paintform($palette);
		break;
	case 'piccom':
		paintcom();
		break;
	case 'anime':
		if(!isset($sp)){$sp="";}
		openpch($pch,$sp);
		break;
	case 'edit':
		editform();
		break;
	case 'editexec':
		editexec();
		break;
	case 'del':
		delmode();
		break;
	case 'admin_in':
		admin_in();
		break;
	case 'admin':
		admin();
		break;
	default:
		def();
}

?>
