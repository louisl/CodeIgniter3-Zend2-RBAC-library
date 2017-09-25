<?php
/**
 * Zend2 RBAC config
 *
 * Part of Zend2 RBAC library for CodeIgniter 3.
 *
 * @package    CodeIgniter\application\config
 * @author     Louis Linehan <louis.linehan@gmail.com>
 * @copyright  2015-2017 Louis Linehan
 * @license    https://github.com/louisl/CodeIgniter3-Zend2-Rbac-Library/blob/master/LICENSE MIT License
 * @link       https://github.com/louisl/CodeIgniter3-Zend2-Rbac-Library
 * @version    0.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Roles table name
 */
$config['role_table'] = 'role';

/**
 * Resource table name
 */
$config['resource_table'] = 'resource';

/**
 * Rules table name
 */
$config['rule_table'] = 'rule';

/**
 * Users roles table name
 */
$config['user_to_role_table'] = 'user_to_role';

/**
 * Database
 */
$config['database_group'] = 'default';

/**
 * Super users group name
 */
$config['super_users_group'] = 'super_users';

/**
 * Debug
 *
 * If true the a debug array will be available:
 * print join('<br>', $this->zend2_rbac->get_debug_array());
 */
$config['debug'] = FALSE;

/**
 * Log errors.
 *
 * If true the error log will contain information about missing roles,
 * resources and privileges. Useful for debugging.
 */
$config['log_errors'] = FALSE;

/**
 * Privileges
 *
 * These can be anything you like, for example you could add 'publish'
 * and 'archive'. Use this array for select options where you set privileges.
 * The keys of this array should match the privileges you use in your database.
 */
$config['privileges'] = [
	'create' => 'Create',
	'read'   => 'Read',
	'update' => 'Update',
	'delete' => 'Delete',
];
