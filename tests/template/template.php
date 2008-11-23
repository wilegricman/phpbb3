<?php
/**
*
* @package testing
* @version $Id$
* @copyright (c) 2008 phpBB Group
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
*
*/

define('IN_PHPBB', true);
define('PHP_EXT', 'php');
define('PHPBB_ROOT_PATH', '../phpBB/');

require_once 'PHPUnit/Framework.php';

require_once '../phpBB/includes/constants.php';
require_once '../phpBB/includes/functions.php';
require_once '../phpBB/includes/template.php';

if (!isset($config))
{
	$config = array();
}
$config += array(
	'load_tplcompile' => true
);

class phpbb_template_template_test extends PHPUnit_Framework_TestCase
{
	private $template;
	private static $ran_non_cached = false;

	private function display($handle)
	{
		ob_start();
		$this->assertTrue($this->template->display($handle, false));
		$contents = str_replace("\n\n", "\n", implode("\n", array_map('trim', explode("\n", trim(ob_get_contents())))));
		ob_end_clean();

		return $contents;
	}

	private function setup_engine($clear_cache = false)
	{
		$this->template = new template;
		$this->template->set_custom_template(dirname(__FILE__) . '/templates/', 'tests');

		if ($clear_cache)
		{
			foreach (glob($this->template->cachepath . '*') as $file)
			{
				unlink($file);
			}
		}
	}

	protected function setUp()
	{
		// Test the engine can be used
		$this->setup_engine();

		if (!is_writable(dirname($this->template->cachepath)))
		{
			$this->markTestSkipped("Template cache directory is not writable.");
		}
	}

	/**
	 * @todo put test data into templates/xyz.test
	 */
	public static function template_data()
	{
		return array(
			/*
			array(
				'', // File
				array(), // vars
				array(), // block vars
				'', // Expected result
			),
			*/
			array(
				'variable.html',
				array('VARIABLE' => 'value'),
				array(),
				'value',
			),
			array(
				'if.html',
				array(),
				array(),
				'0',
			),
			array(
				'if.html',
				array('S_VALUE' => true),
				array(),
				'1',
			),
			array(
				'loop.html',
				array(),
				array(),
				"noloop\nnoloop",
			),
			array(
				'loop.html',
				array(),
				array('loop' => array(array())),
				"loop\nloop",
			),
			array(
				'loop.html',
				array(),
				array('loop' => array(array(), array())),
				"loop\nloop\nloop\nloop",
			),
			array(
				'loop_vars.html',
				array(),
				array('loop' => array(array('VARIABLE' => 'x'))),
				"first\n0\n0\nx\nlast",
			),
			array(
				'loop_vars.html',
				array(),
				array('loop' => array(array('VARIABLE' => 'x'), array('VARIABLE' => 'y'))),
				"first\n0\n0\nx\n1\n1\ny\nlast",
			),
			array(
				'loop_vars.html',
				array(),
				array('loop' => array(array('VARIABLE' => 'x'), array('VARIABLE' => 'y')), 'loop.inner' => array(array(), array())),
				"first\n0\n0\nx\n1\n1\ny\nlast\n0\n1",
			),
			array(
				'define.html',
				array(),
				array(),
				"xyz\nabc",
			),
			array(
				'expressions.html',
				array(),
				array(),
				trim(str_repeat("pass\n", 38)),
			),
			array(
				'include.html',
				array('VARIABLE' => 'value'),
				array(),
				'value',
			),
		);			
	}

	/**
	* @dataProvider template_data
	*/
	public function test_template_no_cache($file, array $vars, array $block_vars, $expected)
	{
		$this->setup_engine(true);
		$this->template->set_filenames(array('test' => $file));
		$this->template->assign_vars($vars);

		foreach ($block_vars as $block => $loops)
		{
			foreach ($loops as $_vars)
			{
				$this->template->assign_block_vars($block, $_vars);
			}
		}

		$this->assertEquals($expected, $this->display('test'), "Testing $file");

		self::$ran_non_cached = true;
	}

	/**
	* @dataProvider template_data
	*/
	public function test_template($file, array $vars, array $block_vars, $expected)
	{
		if (!self::$ran_non_cached)
		{
			$this->fail('Non cached tests failed to run first');
			return;
		}

		$this->setup_engine();
		$this->template->set_filenames(array('test' => $file));
		$this->template->assign_vars($vars);

		foreach ($block_vars as $block => $loops)
		{
			foreach ($loops as $_vars)
			{
				$this->template->assign_block_vars($block, $_vars);
			}
		}

		$this->assertEquals($expected, $this->display('test'), "Testing $file");
	}
}
?>