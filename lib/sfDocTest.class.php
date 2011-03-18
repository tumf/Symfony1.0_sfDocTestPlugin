<?php
/**
 * class for DocTest
 */
class sfDocTest
{
  public static function getCacheDir(){
    return preg_replace("|/+|","/",sfConfig::get('sf_cache_dir')
                        ."/".SF_APP
                        ."/".SF_ENVIRONMENT."/sfDocTestPlugin");
  }
  public static function getTestFile($file){
    $tester =  str_replace(SF_ROOT_DIR."/","",$file);
    $dir = sfDocTest::getCacheDir()."/tests/".dirname($tester);
    if(!is_dir($dir)){
      mkdir($dir,0777,true);
    }
    return sprintf("%s/test_%s",$dir,basename($tester));
  }
  public static function compile_if_modified($file,$config){
    if(defined("sfDocTest_SF_VERSION_1_0")){
      $func =  new sfFunctionCache(sfDoctest::getCacheDir());
    }else{
      $cache = new sfFileCache(array('cache_dir' => sfDocTest::getCacheDir()));
      $func = new sfFunctionCache($cache);
    }
    if(defined("sfDocTest_SF_VERSION_1_0")){
      $id = md5(serialize(array("sfDocTest::compile", $file, $config)));
      if( $func->lastModified($id) < filemtime($file)){
        $func->remove($id);
      }
      $test = $func->call("sfDocTest::compile",$file,$config);
    }else{
      $id = md5(serialize("sfDocTest::compile").serialize(array($file)));
      if($cache->getLastModified($id) < filemtime($file)){
        $cache->remove($id);
      }
      $test = $func->call("sfDocTest::compile",array($file,$config));
    }
    $testfile = sfDocTest::getTestFile($file);
    file_put_contents($testfile,$test);
    return $testfile;
  }
  /**
   */
  public static function compile($file,$config){
    $body = file_get_contents($file);
    $docs = sfDocTest::parse($body);
    //if(!count($docs)) return;
    $out = "<?php\n";
    $out .= "
define('SF_ROOT_DIR',    '".SF_ROOT_DIR."');
define('SF_APP',         '".SF_APP."');
define('SF_ENVIRONMENT', '".SF_ENVIRONMENT."');
define('SF_DEBUG',       1);";

    if(defined("sfDocTest_SF_VERSION_1_0")){
      $out .= "
require_once(SF_ROOT_DIR.DIRECTORY_SEPARATOR.'apps'.DIRECTORY_SEPARATOR.SF_APP.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php');
require_once(\$sf_symfony_lib_dir.'/vendor/lime/lime.php');
";
      $out .= "
\$databaseManager = new sfDatabaseManager();
\$databaseManager->initialize();
";
    }else{
      $out .= "
\$app = SF_APP;
require_once(SF_ROOT_DIR.DIRECTORY_SEPARATOR.'test'.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'functional.php');
new sfDatabaseManager(ProjectConfiguration::getApplicationConfiguration(SF_APP, SF_ENVIRONMENT , true));
";
            
    }        
        
    $out .= "
if(!function_exists('pake_desc')){
  function pake_desc(){}
}
if(!function_exists('pake_task')){
  function pake_task(){}
}
";
    $br = "
\$__test_browser = new ".$config["test_browser"].";
\$__test_browser->initialize();
\$__test = \$__test_browser->test();
";
    $config["init_browser"] = $br;
    $out .= $br;
    $out .= "
\$__test->comment('file: $file');
";

    if(isset($config["helpers"])){
      foreach($config["helpers"] as $helper){
        $out .= "sfLoader::loadHelpers('$helper');\n";
      }
    }
    
    $out .= "require_once \"${file}\";\n";
        
    foreach($docs as $doc){
      $out.=sfDocTest::compile_doc($doc,$config);
    }
    return $out;
  }
  public static function compile_doc($doc,$config){
    
    $lines = explode("\n",$doc);
    $start = false;
    $compiled = "";
    $code =false;
    static $count = 0;
    foreach($lines as $n => $line){
      if(preg_match("/ +\* #test *(.*)/",$line,$m)){
        $start = true;
        $compiled.=sprintf("#comment(\"test: %s\");\n",$m[1]);
        continue;
      }
      if($start){
        if(preg_match("/^ +\* @/",$line)){
          break;
        }
        if(preg_match("/^ +\* <code> */",$line)){
          if (isset($config["function"])
              && sfToolkit::literalize($config["function"])){
            $count += 1;
            $compiled .= "function doctest_".$count."(\$__test,\$__test_browser){\n";
            //$compiled .= $config["init_browser"];
          }
          
          if (isset($config["set_up"])){
            $compiled .= "call_user_func('".$config["set_up"]."',\$__test,\$__test_browser);\n";
          }
          $code = true;
          continue;
        }
        if(preg_match("/^ +\* <\/code> */",$line)){
          if (isset($config["tear_down"])){
            $compiled .= "call_user_func('".$config["tear_down"]."',\$__test,\$__test_browser);\n";
          }
          if (isset($config["function"])
              && sfToolkit::literalize($config["function"])){
            $compiled .= "}\ndoctest_".$count."(\$__test,\$__test_browser);\n";
          }
          $code = false;
          continue;
        }
        if($code){
          if(preg_match("/^ +\* +(.*)/",$line,$m)){
            $compiled.=$m[1];
            $compiled.="\n";
          }
        }
      }
    }
    return sfDocTest::expand_macro($compiled);
  }
  static function var_dump($var)
  {
    return var_export($var,true);
  }
  public static function expand_macro($compiled){
    $compiled
      = preg_replace("/^#dump\((.*)\);/m","#diag(sfDocTest::var_dump(\${1}));",$compiled);
    $compiled
      = preg_replace("/^#eq\(/m","#is(",$compiled);
    $compiled
      = preg_replace("/^#true\(/m","#ok(",$compiled);
    $compiled
      = preg_replace("/^#false\(/m","#ok(!",$compiled);
    $compiled
      = preg_replace("/^#browser->/m","\$__test_browser->",$compiled);
    return preg_replace("/^#([a-z_]+)\((.*)/m","\$__test->\${1}(\${2}",$compiled);
  }
  public static function parse($body){
    $tokens = token_get_all($body);
    $doccoments = array();
    foreach($tokens as $token){
      if(!is_string($token)){
        list($id, $text) = $token;
        switch ($id) {
        case T_DOC_COMMENT:
          if(preg_match("/ +\* +#test */",$text)){
            $doccoments[] = $text;
          }
          break;
        }
      }
    }
    return $doccoments;
  }
  static function loadConfig($config_name){
    $config_file = sprintf("config".DIRECTORY_SEPARATOR."doctest.yml");
        
    $configs = array();
    if(file_exists($config_file)){
      //throw new sfException($config_file." not found.");
      $configs = sfYaml::load($config_file);
    }
        
    $config = array();
        
    if(isset($configs[$config_name])){
      $config = $configs[$config_name];
    }elseif (is_dir(sfConfig::get('sf_root_dir').'/apps/'.$config_name)){
      $config["app"] = $config_name;
    }else{
      throw new sfException('You must provide the app or a config name to test.');
    }

    // config check
    if(empty($config["app"])){
      throw new sfException('You must provide the app .');
    }
    if(empty($config["env"])){
      $config["env"] = "test";
    }
    if(empty($config["in"])){
      $config["in"] = array("apps","lib");
    }
    if(empty($config["test_browser"])){
      $config["test_browser"] = "sfTestBrowser";
    }
        
    // set constants
    define('SF_ROOT_DIR', sfConfig::get('sf_root_dir'));
    define('SF_APP',         $config["app"]);
    define('SF_ENVIRONMENT', $config["env"]);

        
    return $config;
  }
  static function findSubjects($config, $files=array()){
    // find files
    $ins = array();
    if(count($files) > 0){
      $ins = $files;
    }else{
      $ins = $config["in"];
    }
    $files = array();
        
    foreach($ins as $in){
      if(is_dir($in)){
        $finder = sfFinder::type('file')
          ->ignore_version_control()
          ->prune(array("om","map","vendor"))
          ->follow_link()->name("*.php");
        if(isset($config["prune"])){
          $finder->prune($config["prune"]);
        }
        $files = array_merge($files,$finder->in($in));
      }else{
        if(is_readable($in)){
          $files[] = $in;
        }else{
          throw new sfException($in.' file not found or readable.');
        }
      }
    }
    return $files;
  }
  static function findCoverages($config, $files=array()){
    if(isset($config["coverage"]) &&!empty($config["coverage"])){
      return self::findSubjects($config["coverage"],$files);
    }
    return self::findSubjects($config,$files);
  }
  static function getTty(){
    // tty check
    if (DIRECTORY_SEPARATOR == '\\' || !function_exists('posix_isatty') || !@posix_isatty(STDOUT)){
      $tty = "";
    }else{
      $tty = ">".@posix_ttyname(STDOUT);
    }
    return $tty;
  }
  static function runDocTest($config_name,$files,$harness = false){
    $config = sfDocTest::loadConfig($config_name);
    $files = sfDocTest::findSubjects($config,$files);
    $tty = sfDocTest::getTty();
    $h = new sfDocTestLimeHarness(new lime_output_color());
    foreach($files as $file){
      if(is_readable($file)){
        if ($harness){
          $h->register(sfDocTest::compile_if_modified($file,$config));
        }else{
          passthru(sprintf
                   ("%s -d html_errors=off -d open_basedir= -q %s 2>&1 %s"
                    ,$h->php_cli
                    ,sfDocTest::compile_if_modified($file,$config)
                    ,$tty));
        }
      }
    }
    if ($harness){
      $h->run();
    }
  }
  static function runDocTestCoverage($config_name,$files){
    
    $config = sfDocTest::loadConfig($config_name);
    $tests = sfDocTest::findSubjects($config, $files);
    $tty = sfDocTest::getTty();
        
    // do test
    $h = new sfDocTestLimeHarness(new lime_output_color);
    foreach($tests as $file){
      if(is_readable($file)){
        $h->register(sfDocTest::compile_if_modified($file,$config));
      }
    }

    // coverage
    $files = sfDocTest::findCoverages($config, $files);
    $c = new lime_coverage($h);
    foreach($files as $file){
      $c->register($file);
    }
    $c->run();
  }
    
}

