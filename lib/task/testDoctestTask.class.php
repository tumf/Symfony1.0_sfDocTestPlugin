<?php
require_once(sfConfig::get('sf_symfony_lib_dir').'/vendor/lime/lime.php');

class testDoctestTask extends sfBaseTask
{
  protected function configure()
  {
    // // add your own arguments here
    $this->addArguments
        (array(
               new sfCommandArgument('name', sfCommandArgument::OPTIONAL, 'name of configuration in config/doctest.yml','default'),
               new sfCommandArgument('files', sfCommandArgument::IS_ARRAY, 'files1 file2 file3 ...'),
               ));
    
    $this->addOptions(array(
    ));

    $this->namespace        = 'test';
    $this->name             = 'doctest';
    $this->briefDescription = 'execute doc testing.';
    $this->detailedDescription = <<<EOF
The [test:doctest|INFO] task does things.
Call it with:

  [php symfony test:doctest|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
     sfDocTest::runDocTest($arguments["name"],$arguments["files"]);
  }
}
