# sfDocTestPlugin

`sfDocTestPlugin` enables DocTest which place tests in doc comments.

## task
 
 * symfony 1.0

   * doctest
   * doctest-coverage
 
 * symfony 1.2

   * test:doctest
   * test:doctest-converage

## Install

### symfony command

 * symfony 1.0

    $ php symfony plugin-install http://plugins.symfony-project.com/sfDocTestPlugin

 * symfony 1.2

    $ php symfony plugin:install sfDocTestPlugin


### Subvesrion
 
Checkout from Subversion repository as follows:

    cd plugins
    svn co http://svn.tracfort.jp/svn/dino-symfony/plugins/sfDocTestPlugin
    cd - && symfony cc

Or, download or install attached file.

### execute test

    symfony test:doctest frontend

or

    symfony test:doctest frontend target-file-name.class.php

### test configuration

You can set configuration in `${SF_PROJECT_DIR}/config/doctest.yml` file.


    default:
      app: frontend
      env: test
      test_browser: myTestBrowser # extends sfTestBrowser
      in: [apps, lib]
      prune: [validator, templates]
      # optional: setup
      set_up: my_test_set_up
      # you helper (lib/helper/MyTestHelper.php)
      helpers: [MyTest]
      # optional: teardown
      tear_down: my_test_tear_down
      # functionalize your test code(default off)
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

### setup / teardown 
 
setup / teardown function prototypes:


    [php]
    function my_test_set_up($lime, $browser){
    
    }
    function my_test_tear_down($lime, $browser){

    }


### implement test

All test will be passwd when test has not implemented yet.
Test cases must write in doc-commment (from /** to */) as follows:

#### plugins/sfDocTestPlugin/doc/emphasis-1.php
 
    [php]
    /**
     * #test
     * <code>
     * #is(emphasis("great"),"great!!","add !! emphasised.");
     * </code>
     *
     */
    function emphasis($word){
       // function has not implemented yet.
    }

 

DocTest expand as test case After `#test`.`#is` is map to `lime_test` class method `->is()`.

Execute this test.

    symfony doctest frontend emphasis-1.php

Result:

[[Image(emphasis-1.png)]]

It fails as you expects.

#### plugins/sfDocTestPlugin/doc/emphasis-2.php

 
    [php]
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

    
It is to be Success.

    symfony doctest frontend emphasis-2.php


[[Image(emphasis-2.png)]]



## coverage for (symfony 1.0)

 
    syomfony doctest-coverage "test or app" [file1 file2 file3 ...]


## coverage for (symfony 1.2)

    syomfony test:doctest-coverage "test or app" [file1 file2 file3 ...]
