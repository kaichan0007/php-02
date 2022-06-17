<?php
//1. POSTデータ取得
//$name = filter_input( INPUT_GET, ","name" ); //こういうのもあるよ
//$email = filter_input( INPUT_POST, "email" ); //こういうのもあるよ

require_once 'C:/xampp/vendor/autoload.php';

use PHPHtmlParser\Dom;
use PHPHtmlParser\Options;

// 文字コードを設定する。
// 日本語だと文字コードの自動解析がうまく動かないようなので、
// ページに合わせて設定する必要があります
$options = new Options();
$options->setEnforceEncoding('utf8');

// 文字化けする場合は Shift JIS を試してみてください
// $options->setEnforceEncoding('sjis');

// ページを解析
$url = 'https://www.release.tdnet.info/inbs/I_list_001_'.$_POST["date"].'.html';
$dom = new Dom();
$dom->loadFromUrl($url, $options);

$c_code = $_POST["company_code"];

// 商品名を取得
$aaa = $dom->find('td');
// $even_name = $dom->find('.evennew-M kjName');
// $odd_name = $dom->find('.oddnew-M kjName');

// $even_title = $dom->find('.evennew-M kjTitle');
// $odd_title = $dom->find('.oddnew-M kjTitle');

// echo $even_name[0];

$c_name="";
$kaiji_title="";
$kaiji_url="";

for($i=0; $i<count($aaa); $i++)
{
    //echo $aaa[$i]."\n";
    if(strcmp($aaa[$i]->text,$c_code)==0){
        //echo "見つけました！";
        //echo $aaa[$i+1];
        //echo $aaa[$i+2];
        //echo $aaa[$i+2]->firstChild()->getAttribute("href");

        //$str = $aaa[$i+1]->text.",".$aaa[$i+2]->firstChild()->text.",".$aaa[$i+2]->firstChild()->getAttribute("href")."\n";

        $c_name = $aaa[$i+1]->text;
        $kaiji_title = $aaa[$i+2]->firstChild()->text;
        $kaiji_url = $aaa[$i+2]->firstChild()->getAttribute("href");
    }
}

//PDFの解析
$parser = new \Smalot\PdfParser\Parser();
$pdf    = $parser->parseFile('https://www.release.tdnet.info/inbs/'.$kaiji_url);

//echo 'https://www.release.tdnet.info/inbs/'.$kaiji_url;

//echo mb_convert_encoding($pdf->getText(), 'SJIS-win', 'UTF-8');

//echo $pdf->getText();

$keyword = $_POST["search_key"];

$count = substr_count($pdf->getText(), $keyword);


//2. DB接続します
try {
  //Password:MAMP='root',XAMPP=''
  //$pdo = new PDO('mysql:dbname=gs_bm;charset=utf8;host=localhost','root',''); //local
  $pdo = new PDO('mysql:dbname=kaichan007_gs_bm;charset=utf8;host=mysql57.kaichan007.sakura.ne.jp','kaichan007','kaichan07'); //さくらサーバ
} catch (PDOException $e) {
  exit('DB connection error:'.$e->getMessage());
}


//３．データ登録SQL作成
$stmt = $pdo->prepare("insert into gs_bm_table(c_name, title, url, content, count) values(:c_name, :title, :url, :content, :count)");
$stmt->bindValue(':c_name', $c_name, PDO::PARAM_STR);  //Integer（数値の場合 PDO::PARAM_INT)
$stmt->bindValue(':title', $kaiji_title, PDO::PARAM_STR);  //Integer（数値の場合 PDO::PARAM_INT)
$stmt->bindValue(':url', $kaiji_url, PDO::PARAM_STR);  //Integer（数値の場合 PDO::PARAM_INT)
$stmt->bindValue(':content', $pdf->getText(), PDO::PARAM_STR);  //Integer（数値の場合 PDO::PARAM_INT)
$stmt->bindValue(':count', $count, PDO::PARAM_INT);  //Integer（数値の場合 PDO::PARAM_INT)

$status = $stmt->execute();

//４．データ登録処理後
if($status==false){
  //SQL実行時にエラーがある場合（エラーオブジェクト取得して表示）
  $error = $stmt->errorInfo();
  exit("SQL ERROR:".$error[2]);
}else{
  //５．index.phpへリダイレクト
  header("Location: index.php");
  exit();
}
?>
