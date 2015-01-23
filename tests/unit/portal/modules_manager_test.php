<?php
/**
 *
 * @package Board3 Portal Testing
 * @copyright (c) Board3 Group ( www.board3.de )
 * @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 *
 */

namespace board3\portal\portal\modules;

class board3_portal_modules_manager_test extends \board3\portal\tests\testframework\database_test_case
{
	protected $portal_columns;
	static public $is_ajax = false;

	/** @var \board3\portal\portal\modules\manager */
	protected $modules_manager;

	/** @var \board3\portal\portal\modules\constraints_handler */
	protected $constraints_handler;

	public function getDataSet()
	{
		return $this->createXMLDataSet(dirname(__FILE__) . '/../acp/fixtures/modules.xml');
	}

	public function setUp()
	{
		global $cache, $db;

		parent::setUp();

		$user = new \board3\portal\tests\mock\user();
		$request =new \phpbb_mock_request();

		$config = new \phpbb\config\config(array());

		$portal_helper = new \board3\portal\includes\helper(array(
			new \board3\portal\modules\clock($config, null),
			new \board3\portal\modules\birthday_list($config, null, $this->db, $user),
			new \board3\portal\modules\welcome($config, new \phpbb_mock_request, $this->db, $user, $this->phpbb_root_path, $this->phpEx),
			new \board3\portal\modules\donation($config, null, $user),
		));

		$this->portal_columns = new \board3\portal\portal\columns();
		$cache = $this->getMock('\phpbb\cache\cache', array('destroy', 'sql_exists', 'get', 'put'));
		$cache->expects($this->any())
			->method('destroy')
			->with($this->equalTo('portal_modules'));
		$cache->expects($this->any())
			->method('get')
			->with($this->anything())
			->will($this->returnValue(false));
		$cache->expects($this->any())
			->method('sql_exists')
			->with($this->anything());
		$cache->expects($this->any())
			->method('put')
			->with($this->anything());
		$db = $this->db;
		$user->set(array(
			'UNABLE_TO_MOVE'	=> 'UNABLE_TO_MOVE',
			'UNABLE_TO_MOVE_ROW'	=> 'UNABLE_TO_MOVE_ROW',
		));
		$this->database_handler = new \board3\portal\portal\modules\database_handler($db);
		$this->constraints_handler = new \board3\portal\portal\modules\constraints_handler($this->portal_columns, $user);
		$this->modules_manager = new \board3\portal\portal\modules\manager($cache, $db, $this->portal_columns, $portal_helper, $this->constraints_handler, $this->database_handler, $request, $user);
		$portal_modules = obtain_portal_modules();
		foreach($portal_modules as $cur_module)
		{
			$this->constraints_handler->module_column[$cur_module['module_classname']][] = $this->portal_columns->number_to_string($cur_module['module_column']);
		}
	}

	public function test_set_u_action()
	{
		$this->assertInstanceOf('\board3\portal\portal\modules\manager', $this->modules_manager->set_u_action('foobar'));
	}

	public function test_set_acp_class()
	{
		$this->assertInstanceOf('\board3\portal\portal\modules\manager', $this->modules_manager->set_acp_class('foobar'));
	}

	public function test_get_module_link()
	{
		$this->modules_manager->set_acp_class('foo\bar')->set_u_action('index.php?i=25&amp;mode=barfoo');
		$this->assertEquals('index.php?i=%5Cfoo%5Cbar&amp;mode=test&amp;module_id=5', $this->modules_manager->get_module_link('test', 5));
	}

	public function test_handle_ajax_request()
	{
		$this->assertNull($this->modules_manager->handle_ajax_request(array('foobar' => true)));
	}

	public function test_get_horizontal_move_action()
	{
		$this->setExpectedTriggerError(E_USER_NOTICE, 'UNABLE_TO_MOVE');
		$this->modules_manager->get_horizontal_move_action(array(), 6);
	}

	public function test_set_module_column()
	{
		$module_column = $this->constraints_handler->module_column;
		$this->constraints_handler->set_module_column(array());
		$this->assertEquals(array(), $this->constraints_handler->module_column);
		$this->constraints_handler->set_module_column($module_column);
		$this->assertEquals($module_column, $this->constraints_handler->module_column);
	}

	public function test_check_module_conflict()
	{
		phpbb_acp_move_module_test::$override_trigger_error = true;
		phpbb_acp_move_module_test::$error = '';
		phpbb_acp_move_module_test::$error_type = 0;
		$move_action = 1;
		$this->constraints_handler->check_module_conflict($this->modules_manager->get_move_module_data(2), $move_action);
		$this->assertEquals('UNABLE_TO_MOVE', phpbb_acp_move_module_test::$error);
		phpbb_acp_move_module_test::$override_trigger_error = false;
	}
}