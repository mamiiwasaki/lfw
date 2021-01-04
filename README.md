# LFW 1.4
test

PHP5.x,7.x向けの軽量フレームワーク LFW についての解説です。
これは2016/10/30に新たに書き起こした文書です。

2018/03/02 php7 対応

##LFWとは

LFWは、PHP5.x上で動作する軽量型のフレームワークです。このフレームワークでは、HTMLへのリクエストをトリガーとして関連するPHPをキックする構造を持ち、HTMLデザイナーとプログラマーのコーディング競合を最小限にすることと、HTMLへのリクエストURLとPHPのプログラムのファイルの場所の直交性を担保することを目的としています。

重量級のフレームワークでの学習速度、習熟度の問題と、ＵＲＬとの直交性の低さ、またＨＴＭＬを加工してしまうことによるデザイナーとの連携の低下を最小化し、開発効率を最大化することが目的のフレームワークです。

2006年以後継続してメンテナンスされ、内部外部UTF-8化、データベースI/OのPDO切り替えなどを経て現在に至ります。

## 環境構築(macosx)
(1) git clone ->  /home/lfw 

(2) mkdir /home/lfw/status
	* check directory permission (allow to apache user)

(3) mkdir /home/lfw/logs
	* check directory permission (allow to apache user)

(4) $ sudo echo '127.0.0.1 lfw.local' >> /etc/hosts

(5) $ sudo ln -s /home/lfw/conf/lfw.conf /etc/apache2/other/

(6) edit httpd.conf ->  Comment out : Require all denied
                    ->  Allow override All
(7) edit /usr/local/php5/entropy-php.conf
            AddType application/x-httpd-php .php .html

(8) apachectl restart

(9) open url  "http://lfw.local" in your brouser


## PHP Version 合わせ(macosx)

(1) php-osx.lip.ch を使う場合

    curl -s https://php-osx.liip.ch/install.sh | bash -s 7.1 
    export PATH=/usr/local/php5/bin:$PATH 

のような感じで、使いたいPHPのバージョンを変えられます。

5.6　と 7.1 の環境があればほぼカバーできるはずです。

(2) 切り替え方

	sudo ln -s /usr/local/php5-5.3.29-20141019-211753 /usr/local/php5
	のように、希望のPHPのディレクトリを、 /usr/local/php5 にシンボリックリンクしてから apacheを再起動すれば切り替わります。
	

## ルール
LFWでアプリケーションを作成する上で、次のルールを守ってください。

* HTML に PHP を埋め込まない。（埋め込めない）
* classes フォルダ配下のプログラムは軽い気持ちで変更しない。
* init.php は軽い気持ちで変更しない。
* 生SQLを書かない
* echo しない。logsaveする。

まずは上記のルールだけ守っておけば広域事故が防げる構造となっています。


##主要なフォルダ構造
一般的なLFWのアプリケーションは、 /home/ フォルダ直下に、アプリケーションを構成します。UNIX アカウントを一つ用意してその中に閉じ込めることで、一つのサーバー上に複数のコンテンツが競合なく同居できるように配置します。主なフォルダとファイルは以下の通りとなります。

### 主要フォルダ
	/home/(app)/					アプリケーションの起点となります。
	/home/(app)/conf/				設定ファイルが格納されます。
	/home/(app)/code				PHPのコードが格納されます。
	/home/(app)/code/lib/			各種ライブラリです。
	/home/(app)/code/module/		アプリケーションが配置されます。
	/home/(app)/htdocs/				HTMLが格納されます。
	/home/(app)/logs/				httpd のログが格納されます。
	/home/(app)/status/				アプリケーションログが格納されます。

### conf フォルダ
	conf/config.inc					全体の設定を行います。
	conf/(app).conf					httpd のバーチャルドメイン設定
		※このファイルをhttpdの設定フォルダへシンボリックリンクを張ります。

### code フォルダ
	init.php							preloadモジュールです。
		※.htaccess から autoprepend で呼び出すことで事前処理を行います。

### code/lib フォルダ
	classess/							クラスファイルが格納されます。
		actionBase					レンダラーを含むメインアクティビティ
		factory						ファクトリーチェインを構成
		dataContainer					セッションをつなぐデータコンテナー
		user							ユーザー情報を保持
		template						テンプレートを構成
		pdo								データベース I/O
	function/							広域共有関数
		debug							デバッグに関するもの
		utilities						便利ツール
		mailsend						メール送信
	dao/								Data Access Object
		xxxxx.dao						目的ごとに
	package/							パッケージツール
		PHPExcelやqdmail などのパッケージを保管

### modules フォルダ
modulesフォルダは、htdocs配下のフォルダと１対１で紐付きます。そのmoduleフォルダは一つのアプリケーションとして動作し、動作に必要なファイル群がフォルダに格納されます。
	
	(appname)/								htdocs/以下のフォルダと相関します
	(appname)/modules/config.php
	(appname)/modules/actions/				相関するHTMLと同名のPHPを配置します。
	(appname)/modules/dao/					使用するdaoを配置します。
	(appname)/lib/							ライブラリを配置します。
	(appname)/templates/					テンプレートを配置します。

ＵＲＬとappnameの相関関係は次の通りとなります。

	フォルダの相関関係
	http://(server)/testApp/ -> /modules/testApp/
		※ testApp がアプリ名
		
	ファイルの相関関係
	http://(server)/testApp/index.html 
				-> /modules/testApp/actions/index.php

このとき、例外として次の３つが存在します。

	(1)http://(server)/    -> /modules/index/
		※ルートフォルダは index として表現
	(2)modules が存在しない場合は、 common モジュールが呼ばれます。
	
	(3)actions配下にPHPが存在しない場合は、 common.php が呼ばれます。

## configuration
lfwを使用するためには、以下の初期設定を行います。

* conf/config.incの SYSID と URL , DBコネクション設定を適切に指定します。
* conf/(site).conf の ディレクトリとバーチャルホストを適切に指定します。
* (site).conf を httpd に設定します
* htdocs/.htaccess を配置します。code/init.php を php_autoprepend します。
* html , php を配置します。

上記設定が完了した段階でlfwは使用可能になります。


## example
システム名を testApp とします。
ＵＲＬは、 http://testApp/
アプリケーションとして、
　/app1/index.html
　/app1/test.html
がするとした場合、以下のようなファイル構成になります。(lib等を除く)

	・HTML
		/home/testApp/htdocs/app1/index.html    (A)
		/home/testApp/htdocs/app1/test.html     (B)
	・PHP
		/home/testApp/code/module/app1/actions/index.php  (A)
		/home/testApp/code/module/app1/actions/test.php   (B)

(A)、(B)がそれぞれ相関関係を持ち、ブラウザ等から、
http://testApp/app1/index.html が呼ばれると、 index.php が起動され、
http://testApp/app1/test.html が呼ばれると、 test.php が起動されます。

この仕組みにより、呼び出されるURLとPHPプログラムの直交性を維持します。

## actions の記述方法
html と action は以下のように記述できます。

### example.html
	<html>
		これは静的なHTMLです<br />
		___HOGE___ はダイナミックに生成されました<br />
		

### example.php
	<?php
	require_once(dirname(__FILE__).DIRECTORY_SEPARATOR."..".DIRECTORY_SEPARATOR."config.php");
	class app_main extends ActionBase
	{
	        //      initialize
	        public function initialize(dataContainer $data, userContainer $user, factory $factory)
	        {
	                $this->debug_echo   = false ;
	                $this->debug_status = false ;
	                return ;
	        }
	        //      dispatch
	        public function dispatch(dataContainer $data, userContainer $user, factory $factory)
	        {
	        			$this->viewitem["HOGE"] = "fuga" . date("Y/m/d H:i:s") ;
	                return ;
	        }
	}
	/* -- end of text ------------------------------------------------------------*/

html はターゲットとするブラウザが許容する範囲でどのように記載しても問題なく、css , javascript 等についても一切の制限はありません。

しかし、PHPをHTML内部に記述することはできず、PHPからの出力は、 $viewitem を使用して行います。
viewitem 変数は、そのキーに指定された内容を、HTMLに差し込みます。$viewitem["HOGE"]は、HTMLの _ _ _ HOGE _ _ _ と置換されます。_ が３つ前後につくものを差し込みキーワードと呼びます。

処理の流れは、

	(1)html が呼び出される
	(2).htaccess の php_autoprepend 経由で init.php が呼び出される
	(3)相関する actions が呼び出される
	(4)phpのコードが呼ばれる
	(5)acTionBase の render が起動して 差し込み処理が行われる

となるため、PHPでechoすると、ＨＴＭＬより前に出力されることになります。(header処理などを処理できます）

###Options

* debug_echo を true にすることで、echo した内容をオーバーレイします。
* debug_status を true にすることで、メモリー使用量やSESSION_IDを表示します。


## dataContainer
datacontainer (以下DC) は、ウェブベースのサービスでデータを運ぶ機構です。DCは、session を使用して、data構造を自動的に保持するため、毎回session 操作を行う必要がなく、連想配列に格納できるデータ構造をそのまま持ち回ります。DCは、actionbase の initialize と dispatch に引き渡される $data です。

	ＤＣへデータをセットする
		$data->setAttribute("KEY" , "VAL") ;

	ＤＣからデータを呼び出す
		$data->getAttribute("KEY");

上記の操作で、ページの読み直しや画面遷移時にもデータを持ち回れます。
DCは、高速、大量反応が必要な場合は、ramdisk や memchachedに乗せることができますし、容量の大きなデータを持ち回る必要があるなら、ハードディスクに格納することができます。 /var/lib/php/session の場所と、PHPのmemcachedサポートを利用できます。

## database I/O
database との I/O は PDO を使用します。PDOのラッパーを内蔵しています。

	$con = new DBIO();
	$con->connect();
	$sql = "select * from table where id='?'";
 	$prm = array('bar');
	$con->sqbind($sql,$prm);
 	$result = $con->fetch();
 	$con->close();
 
事故防止のため、直接記述のSQLを避け、プレースホルダーを使用してください。また、目的語と、スキーマーごとに daoを用意して、ＳＱＬのアクセスをカプセル化するようにしてください。

デフォルトのRDBMSは mysqlです。

全メソッドについては、 pdo.inc を参照してください。


## templateTrait
template機能が実装されています。smarty等を使用してもかまいませんが、軽量なテンプレートで良いなら内蔵テンプレートを使用したほうがレスポンスが早くなります。

	actionBase  に trait を読み込みます。
		use templateTrait;

	$items = array();
		items の連想配列に差し込みたいデータをセット

	$this->init_tpl("(template name)");
		template name というファイルを読み込み
		
	$buff = $this->fetch_tpl("KEYWORD");
		<!--##KEYWORD--> で囲まれた部分を切り出し
		
	$html = $this->assign_tpl($buff, $items);
		連想配列をマッピングして戻します。


## userContainer
userを識別するタイプのアプリケーションの場合、ログインや認証を個別に実装すると手間がかかりますが、このフレームワークでは、userContainerという形で任意実装が可能になっています。
userContainer は actionBaseに対して $user としてひきわたされますので、最低限 $user->isLogin の真偽値を判断することでユーザーの認証、排除が行えます。

## logsave
このフレームワークでは、echoしてもHTMLの前に出力されてしまうためデバッグしにくいはずです。そのためlogsaveという機能があります。

	logsave("facility" , "message");

この機能を使用すると、 status/ 配下に 指定した facility へログを出力します。
このログを tail 等してデバッグをしてください。複数名で開発をしているなら、 SESSION_ID で grep するとデバッグ対象のログだけが抽出できます。 echo して残してしまうバグが多発しますので、可能な限り logsaveを使用してください。


## その他
開発手法に関しては、一般的なPHPの記述方法を参照してください。開発ツールにつては、テキストエディタ、Dreamweaber等のHTMLエディタ、Netbeans や　Eclipse など、ＰＨＰをサポートするIDEが使用できます。

文字コードは内部UTF-8 , 外部UTF-8 ですが、レンダラーの設定で出力は変更可能です。


## このシステムを採用しているサイトの例
自社利用例

* JJOB					<https://jjob.j-tec-cor.co.jp/>

* JBS					<https://vstaff.info/>

社外利用例

* 全国生協連			<http://www.kyosai-cc.or.jp/>

* 株式会社富士経済		<https://www.fuji-keizai.co.jp/>

* 携帯キャリア向けコンテンツ 等
