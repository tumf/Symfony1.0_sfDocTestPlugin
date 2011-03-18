<?php

class testDoctestcoverageTask extends sfBaseTask
{
  protected function configure()
  {
    $this->addArguments
        (array(
               new sfCommandArgument('name', sfCommandArgument::OPTIONAL, 'name of configuration in config/doctest.yml','default'),
               new sfCommandArgument('files', sfCommandArgument::IS_ARRAY, 'files1 file2 file3 ...'),
               ));
    
    $this->namespace        = 'test';
    $this->name             = 'doctest-coverage';
    $this->briefDescription = 'Output doctest coverages.';
    $this->detailedDescription = <<<EOF
The [test:doctest-coverage|INFO] task does things.
Call it with:

  [php symfony test:doctest-coverage|INFO]
EOF;
  }

  protected function execute($arguments = array(), $options = array())
  {
      sfDocTest::runDocTestCoverage($arguments["name"],$arguments["files"]);
  }
}
