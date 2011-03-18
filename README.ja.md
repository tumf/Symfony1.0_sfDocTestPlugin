 = sfDocTestPlugin =

 sfDocTestPluginは、`DocTest`を実現するプラグインです。[http://d.hatena.ne.jp/kunit/20080205#1202142580 Maple_DocTest]にインスパイアされて作ってみました。

 == 追加されるタスク ==
 
 * symfony 1.0

   * doctest
   * doctest-coverage
 
 * symfony 1.2

  * test:doctest
 
 == インストール ==

 === プラグインインストール (準備中) ===
 
 * symfony 1.0
 
{{{
$ php symfony plugin-install http://plugins.symfony-project.com/sfDocTestPlugin
}}}

 * symfony 1.2
 
{{{
$ php symfony plugin:install sfDocTestPlugin
}}}

 === Subvesrionでチェックアウト  ===
 
{{{
cd plugins
svn co http://svn.tracfort.jp/svn/dino-symfony/branches/sfDocTestPlugin-0.2 sfDocTestPlugin
cd - && symfony cc
}}}

 == Testの設定 ==

`${SF_PROJECT_DIR}/config/doctest.yml`に以下のような細かい設定を書くことが出来ます。アプリケーション名の代わりにこの設定名を利用することができます。

{{{
---
default:
  app: frontend
  env: test
  test_browser: myTestBrowser # extends sfTestBrowser
  in: [apps, lib]
  prune: [validator, templates]
  # you helper (lib/helper/MyTestHelper.php)
  helpers: [MyTest]
  # setup関数(オプション)
  set_up: my_test_set_up
  # teardown関数(オプション)
  tear_down: my_test_tear_down
  # テストコードを functionで囲む(デフォルトは囲まない)
  function: on
  coverage:
    in: [apps, lib]
    prune: [config]
  
  
plugins:
  app: frontend
  env: test
  in: [plugins]
  prune: [validator, templates]

pre-release-check:
  app: frontend
  env: test
  in: [apps, lib]
  prune: [validator, templates]
  
}}}

 === setup / teardown ===
 
関数のプロトタイプは以下の通り

{{{

function my_test_set_up($lime, $browser){

}

function my_test_tear_down($lime, $browser){

}

}}}

 == テストの実施 ==

プラグインをインストール後、単に以下のようにすればテストを実施します。

 * symfony 1.0

{{{
symfony doctest frontend
}}}

 * symfony 1.2

{{{
symfony test:doctest frontend
}}}

 == テストの実装 ==
 

 === 基本 ===
 
テストが実装されていなければ、すべてのファイルのdoctestがパスします。
例えば以下のコードにテストケースをを実装するには、PHPファイルのコメント(/** 〜 */)に以下のようにコメントを実装していきます。

  * plugins/sfDocTestPlugin/doc/emphasis-1.php
  
{{{
<?php
/**
 * #test
 * <code>
 * #is(emphasis("great"),"great!!","add !! emphasised.");
 * </code>
 *
 */
function emphasis($word){
}
}}}

`#test`の後の<code>〜</code>がテストケースとして展開されます。#isはlime_testのメソッド->is()に対応しています。

このファイルのテストを実行してみます。

{{{
symfony test:doctest frontend emphasis-1.php
}}}

結果は以下のように失敗します。

[[Image(emphasis-1.png)]]


  * plugins/sfDocTestPlugin/doc/emphasis-2.php
  
{{{
<?php
/**
 * #test
 * <code>
 * #is(emphasis("great"),"great!!","add !! emphasised.");
 * </code>
 *
 */
function emphasis($word){
    return $word."!!";	 
}
}}}

今度は成功するでしょう。

{{{
symfony test:doctest frontend emphasis-2.php
}}}

[[Image(emphasis-2.png)]]

 === ファンクションテスト ===

symfonyに組み込みの`sfTestBrowser`を利用してファンクションテストをする時には、以下のようにします。
`#browser`は既に->initialize()済み`sfTestBrowser`のインスタンスです。

{{{
/**
 * #test form
 * <code>
 * #browser->get("/")->
 *   responseContains('form')->
 *   test(); //これはただのターミネータ
 * </code>
 */
}}}

 == カバレッジの計測 ==

以下のようにしてカバレッジの測定をすることができます。

{{{
symfony doctest-coverage "テスト名 or アプリケーション名" [file1 file2 file3 ...]
}}}

{{{
symfony test:doctest-coverage "テスト名 or アプリケーション名" [file1 file2 file3 ...]
}}}





