<?php
// noe-boardの最新画像をサイトの入り口のHTMLファイルに呼び出すphp
// noe_newimg.php(c)さこつ 2020 lot.200607
// https://sakots.red/
// さとぴあさんの https://github.com/satopian/potiboard_plugin
// を参考に作りました。
// フリーウェアですが著作権は放棄しません。

// 使い方
//noeのindex.phpと同じディレクトリにアップロードして
//HTMLファイルに画像を表示する時のように
//noe_newimg.php ←このファイルの名前をurlで指定します。

//例）
// <img src="https://hoge.ne.jp/bbs/noe_newimg.php" alt="" width="300">
//↑
//この例では横幅300px、高さの指定なし。

//---------------- 設定 ----------------

// 画像がない時に表示する画像を指定
$default='';
//例
// $default='https://hoge.ne.jp/image.png';
//設定しないなら初期値の
// $default='';
//で。

//--------- 説明と設定ここまで ---------

include(__DIR__.'/config.php');//config.phpの設定を読み込む

//db接続の前にdbがなかったらそもそも処理しない
//これを入れないとテーブルも何もないdbが作られていろいろ困る
if (!is_file("noe.db")) {
    $filename = $default;
} else {
    try {
        //db接続
        $db = new PDO("sqlite:noe.db"); 
        //modifiedが一番大きい=最新 の画像ファイル名を取り出す
        //ORDER BY picfile で picfile名（画像の最終更新）の順、DESCで大きい順を指定
        //LIMIT 1 で1行だけ取り出すので最新のものだけになる
        $sql ="SELECT picfile FROM tablelog ORDER BY picfile DESC LIMIT 1";
        $msgs = $db->prepare($sql);
        $msgs->execute();
        $msg = $msgs->fetch(); //取り出せた
        //配列$msg内のpicfileに格納されている
        //$msgがからっぽ=ログに画像がない場合はデフォルト画像
        if (empty($msg)) {
            $filename = $default;
        } else {
            $filename = IMG_DIR.$msg["picfile"]; 
        }
        $db = null;// db切断
    } catch (PDOException $e) {
        echo "DB接続エラー:" .$e->getMessage();
    }
}

//画像を出力
$img_type=mime_content_type($filename);

switch ($img_type):
	case 'image/png':
		header('Content-Type: image/png');
		break;
	case 'image/jpeg':
		header('Content-Type: image/jpeg');
		break;
	case 'image/gif':
		header('Content-Type: image/gif');
		break;
	default :
		header('Content-Type: image/png');
	endswitch;
		
readfile($filename);
?>