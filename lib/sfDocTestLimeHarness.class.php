<?php
/**
 *
 *
 *
 */
class sfDocTestLimeHarness extends lime_registration
{
  public $php_cli = '';
  public $stats = array();
  public $output = null;

  function __construct($output_instance, $php_cli = null)
  {
    if (getenv('PHP_PATH'))
    {
      $this->php_cli = getenv('PHP_PATH');

      if (!is_executable($this->php_cli))
      {
        throw new Exception('The defined PHP_PATH environment variable is not a valid PHP executable.');
      }
    }

    $this->php_cli = null === $php_cli ? PHP_BINDIR.DIRECTORY_SEPARATOR.'php' : $php_cli;

    if (!is_executable($this->php_cli))
    {
      $this->php_cli = $this->find_php_cli();
    }

    $this->output = $output_instance ? $output_instance : new lime_output();
  }

  protected function find_php_cli()
  {
    $path = getenv('PATH') ? getenv('PATH') : getenv('Path');
    $exe_suffixes = DIRECTORY_SEPARATOR == '\\' ? (getenv('PATHEXT') ? explode(PATH_SEPARATOR, getenv('PATHEXT')) : array('.exe', '.bat', '.cmd', '.com')) : array('');
    foreach (array('php5', 'php') as $php_cli)
    {
      foreach ($exe_suffixes as $suffix)
      {
        foreach (explode(PATH_SEPARATOR, $path) as $dir)
        {
          $file = $dir.DIRECTORY_SEPARATOR.$php_cli.$suffix;
          if (is_executable($file))
          {
            return $file;
          }
        }
      }
    }

    throw new Exception("Unable to find PHP executable.");
  }

  function run()
  {
    if (!count($this->files))
    {
      throw new Exception('You must register some test files before running them!');
    }

    // sort the files to be able to predict the order
    sort($this->files);

    $this->stats =array(
      '_failed_files' => array(),
      '_failed_tests' => 0,
      '_nb_tests'     => 0,
    );

    foreach ($this->files as $file)
    {
      $this->stats[$file] = array(
        'plan'     =>   null,
        'nb_tests' => 0,
        'failed'   => array(),
        'passed'   => array(),
      );
      $this->current_file = $file;
      $this->current_test = 0;
      $relative_file = $this->get_relative_file($file);
      $tested_file = $this->get_tested_file($file);
      
      ob_start(array($this, 'process_test_output'));
      passthru(sprintf('%s -d html_errors=off -d open_basedir= -q "%s" 2>&1', $this->php_cli, $file), $return);
      ob_end_clean();

      if ($return > 0)
      {
        $this->stats[$file]['status'] = 'dubious';
        $this->stats[$file]['status_code'] = $return;
      }
      else
      {
        $delta = $this->stats[$file]['plan'] - $this->stats[$file]['nb_tests'];
        if ($delta > 0)
        {
          $this->output->echoln(sprintf('%s%s%s', substr($tested_file, -min(67, strlen($tested_file))), str_repeat('.', 70 - min(67, strlen($tested_file))), $this->output->colorizer->colorize(sprintf('# Looks like you planned %d tests but only ran %d.', $this->stats[$file]['plan'], $this->stats[$file]['nb_tests']), 'COMMENT')));
          $this->stats[$file]['status'] = 'dubious';
          $this->stats[$file]['status_code'] = 255;
          $this->stats['_nb_tests'] += $delta;
          for ($i = 1; $i <= $delta; $i++)
          {
            $this->stats[$file]['failed'][] = $this->stats[$file]['nb_tests'] + $i;
          }
        }
        else if ($delta < 0)
        {
          $this->output->echoln(sprintf('%s%s%s', substr($tested_file, -min(67, strlen($tested_file))), str_repeat('.', 70 - min(67, strlen($tested_file))), $this->output->colorizer->colorize(sprintf('# Looks like you planned %s test but ran %s extra.', $this->stats[$file]['plan'], $this->stats[$file]['nb_tests'] - $this->stats[$file]['plan']), 'COMMENT')));
          $this->stats[$file]['status'] = 'dubious';
          $this->stats[$file]['status_code'] = 255;
          for ($i = 1; $i <= -$delta; $i++)
          {
            $this->stats[$file]['failed'][] = $this->stats[$file]['plan'] + $i;
          }
        }
        else
        {
          $this->stats[$file]['status_code'] = 0;
          $this->stats[$file]['status'] = $this->stats[$file]['failed'] ? 'not ok' : 'ok';
        }
      }

      $this->output->echoln(sprintf('%s%s%s', substr($tested_file, -min(67, strlen($tested_file))), str_repeat('.', 70 - min(67, strlen($tested_file))), $this->stats[$file]['status']));
      if (($nb = count($this->stats[$file]['failed'])) || $return > 0)
      {
        if ($nb)
        {
          $this->output->echoln(sprintf("    Failed tests: %s", implode(', ', $this->stats[$file]['failed'])));
        }
        $this->stats['_failed_files'][] = $file;
        $this->stats['_failed_tests']  += $nb;
      }

      if ('dubious' == $this->stats[$file]['status'])
      {
        $this->output->echoln(sprintf('    Test returned status %s', $this->stats[$file]['status_code']));
      }
    }

    if (count($this->stats['_failed_files']))
    {
      $format = "%-30s  %4s  %5s  %5s  %s";
      $this->output->echoln(sprintf($format, 'Failed Test', 'Stat', 'Total', 'Fail', 'List of Failed'));
      $this->output->echoln("------------------------------------------------------------------");
      foreach ($this->stats as $file => $file_stat)
      {
        if (!in_array($file, $this->stats['_failed_files'])) continue;

        $tested_file = $this->get_tested_file($file);
        $this->output->echoln(sprintf($format, substr($tested_file, -min(30, strlen($tested_file))), $file_stat['status_code'], count($file_stat['failed']) + count($file_stat['passed']), count($file_stat['failed']), implode(' ', $file_stat['failed'])));
      }

      $this->output->red_bar(sprintf('Failed %d/%d test scripts, %.2f%% okay. %d/%d subtests failed, %.2f%% okay.',
        $nb_failed_files = count($this->stats['_failed_files']),
        $nb_files = count($this->files),
        ($nb_files - $nb_failed_files) * 100 / $nb_files,
        $nb_failed_tests = $this->stats['_failed_tests'],
        $nb_tests = $this->stats['_nb_tests'],
        $nb_tests > 0 ? ($nb_tests - $nb_failed_tests) * 100 / $nb_tests : 0
      ));
    }
    else
    {
      $this->output->green_bar(' All tests successful.');
      $this->output->green_bar(sprintf(' Files=%d, Tests=%d', count($this->files), $this->stats['_nb_tests']));
    }

    return $this->stats['_failed_tests'] ? false : true;
  }
  protected function process_test_output($lines)
  {
    foreach (explode("\n", $lines) as $text)
    {
      if (false !== strpos($text, 'not ok '))
      {
        ++$this->current_test;
        $test_number = (int) substr($text, 7);
        $this->stats[$this->current_file]['failed'][] = $test_number;

        ++$this->stats[$this->current_file]['nb_tests'];
        ++$this->stats['_nb_tests'];
      }
      else if (false !== strpos($text, 'ok '))
      {
        ++$this->stats[$this->current_file]['nb_tests'];
        ++$this->stats['_nb_tests'];
      }
      else if (preg_match('/^1\.\.(\d+)/', $text, $match))
      {
        $this->stats[$this->current_file]['plan'] = $match[1];
      }
    }
    return;
  }
  protected function get_tested_file($file)
  {
    return substr($this->get_relative_file($file)
                  ,strlen($this->get_relative_file(sfDocTest::getCacheDir()."tests/"))+1);
  }
}