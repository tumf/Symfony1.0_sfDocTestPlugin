<?php
pake_desc('doctest harness');
pake_task('doctest-harness','project_exists');

pake_desc('doctest coverage');
pake_task('doctest-coverage','project_exists');

pake_desc('doctest');
pake_task('doctest','project_exists');


require_once(sfConfig::get('sf_symfony_lib_dir').'/vendor/lime/lime.php');

function run_doctest($task,$args)
{
    define("sfDocTest_SF_VERSION_1_0",true);
    if (count($args) < 1){
        $config_name = "default";
    }else{
        $config_name = array_shift($args);
    }
    sfDocTest::runDocTest($config_name, $args);
}
function run_doctest_harness($task,$args)
{
    define("sfDocTest_SF_VERSION_1_0",true);
    if (count($args) < 1){
        $config_name = "default";
    }else{
        $config_name = array_shift($args);
    }
    sfDocTest::runDocTest($config_name, $args, true);
}

function run_doctest_coverage($tasks, $args)
{
    define("sfDocTest_SF_VERSION_1_0",true);
    
    if (count($args) < 1){
        $config_name = "default";
    }else{
        $config_name = array_shift($args);
    }
    sfDocTest::runDocTestCoverage($config_name, $args);
}

