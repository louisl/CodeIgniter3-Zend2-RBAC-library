<?php
/**
 * Zend2 RBAC library
 *
 * Zend2 RBAC library for CodeIgniter 3.
 *
 * @package    CodeIgniter
 * @author     Louis Linehan <louis.linehan@gmail.com>
 * @copyright  2015-2017 Louis Linehan
 * @license    https://github.com/louisl/CodeIgniter3-Zend2-Rbac-Library/blob/master/LICENSE MIT License
 * @link       https://github.com/louisl/CodeIgniter3-Zend2-Rbac-Library
 * @version    0.0.0
 * @filesource
 */
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Zend2 RBAC library
 *
 * @package CodeIgniter\application\libraries
 * @author  Louis Linehan <louis.linehan@gmail.com>
 */
class Zend2_rbac {

	/**
	 * Rbac
	 *
	 * @var Zend\Permissions\Rbac\Rbac
	 */
	public $rbac;

	/**
	 * CodeIgniter
	 *
	 * @var object
	 */
	protected $ci;

	/**
	 * Privileges array
	 *
	 * @var array
	 */
	protected $privileges = [];

	/**
	 * Roles table
	 *
	 * @var string
	 */
	protected $role_table;

	/**
	 * Resources table
	 *
	 * @var string
	 */
	protected $resource_table;

	/**
	 * Rules table
	 *
	 * @var string
	 */
	protected $rule_table;

	/**
	 * Super users group
	 *
	 * @var string
	 */
	protected $super_users_group;

	/**
	 * Debug
	 *
	 * @var boolean
	 */
	protected $debug = FALSE;

	/**
	 * Debug array
	 *
	 * @var boolean
	 */
	protected $debug_array = [];

	/**
	 * Log errors
	 *
	 * @var boolean
	 */
	protected $log_errors = FALSE;

	/**
	 * Construct
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->ci =& get_instance();

		$this->ci->load->config('zend2_rbac', TRUE);

		$this->ci->load->database($this->ci->config->item('database_group', 'zend2_rbac'));

		$this->role_table        = $this->ci->config->item('role_table', 'zend2_rbac');
		$this->resource_table    = $this->ci->config->item('resource_table', 'zend2_rbac');
		$this->rule_table        = $this->ci->config->item('rule_table', 'zend2_rbac');
		$this->super_users_group = $this->ci->config->item('super_users_group', 'zend2_rbac');
		$this->privileges        = $this->ci->config->item('privileges', 'zend2_rbac');
		$this->debug             = $this->ci->config->item('debug', 'zend2_rbac');

		$this->rbac = new Zend\Permissions\Rbac\Rbac();
	}

	/**
	 * Init
	 *
	 * Creates RBAC from database.
	 *
	 * @return void
	 */
	public function init()
	{
		$this->_add_roles(NULL);
		$this->_add_super_user_rules();
		$this->_add_rules();
	}

	/**
	 * Add roles
	 *
	 * Add all the roles in the db to the RBAC.
	 * If the role is a child add it as a child of the parent role.
	 *
	 * @param integer $parent_id Parent role id
	 * @param string  $name      Role name
	 *
	 * @return void
	 */
	private function _add_roles($parent_id, $name = NULL)
	{
		$this->ci->db->order_by('parent_id', 'ASC');

		$this->ci->db->where('parent_id', $parent_id);

		$query = $this->ci->db->get($this->role_table);

		if ( ! empty($query) && $query->num_rows() > 0)
		{
			foreach ($query->result() as $role)
			{
				$new_role = new Zend\Permissions\Rbac\Role($role->name);

				if ( ! empty($name))
				{
					if ($this->debug)
					{
						$this->debug_array[] = 'getRole(' . $name . ')->addChild(' . $role->name . ')';
					}

					$this->rbac->getRole($name)->addChild($new_role);
				}
				else
				{
					if ($this->debug)
					{
						$this->debug_array[] = 'addRole(' . $role->name . ')';
					}

					$this->rbac->addRole($new_role);
				}

				$this->_add_roles($role->id, $new_role);
			}
		}
	}

	/**
	 * Add super user rules
	 *
	 * Super users will inherit rules from children but we also want the
	 * super user to access things that no children have access to so give
	 * them permission to every resource and every privilege. This means we dont
	 * have to add specific rules for the super user in the db.
	 *
	 * @return void
	 */
	private function _add_super_user_rules()
	{
		$query = $this->ci->db->get($this->resource_table);

		if ( ! empty($query) && $query->num_rows() > 0)
		{
			foreach ($query->result() as $resource)
			{
				foreach ($this->privileges as $privilege => $description)
				{
					$this->rbac->getRole($this->super_users_group)->addPermission($resource->name . '.' . $privilege);

					if ($this->debug)
					{
						$this->debug_array[] = 'getRole(' . $this->super_users_group . ')->addPermission(' . $resource->name . '.' . $privilege . ')';
					}
				}
			}
		}
	}

	/**
	 * Add rules
	 *
	 * Add all the rules in the db to the RBAC.
	 *
	 * @return void
	 */
	private function _add_rules()
	{
		$this->ci->db->select($this->rule_table . '.*');
		$this->ci->db->select($this->role_table . '.name AS role');
		$this->ci->db->select($this->resource_table . '.name AS resource');

		$this->ci->db->from($this->rule_table);

		$this->ci->db->join($this->role_table, $this->role_table . '.id = ' . $this->rule_table . '.role_id', 'inner');
		$this->ci->db->join($this->resource_table, $this->resource_table . '.id = ' . $this->rule_table . '.resource_id', 'inner');

		$query = $this->ci->db->get();

		if ( ! empty($query) && $query->num_rows() > 0)
		{
			foreach ($query->result() as $rule)
			{
				$this->rbac->getRole($rule->role)->addPermission($rule->resource . '.' . $rule->privilege);

				if ($this->debug)
				{
					$this->debug_array[] = 'getRole(' . $rule->role . ')->addPermission(' . $rule->resource . '.' . $rule->privilege . ')';
				}
			}
		}
	}

	/**
	 * Any granted
	 *
	 * Check if any roles are allowed to access the resource.privilege.
	 * Loop through the roles array and return TRUE if any one of
	 * the roles has permission granted.
	 *
	 * @param array  $roles     Array of role name strings
	 * @param string $resource  Resource
	 * @param string $privilege Privilege
	 * @param object $assertion Assertion
	 *
	 * @return boolean
	 */
	public function any_granted(array $roles, $resource, $privilege, $assertion = NULL)
	{
		if (is_array($roles) && ! empty($roles))
		{
			foreach ($roles as $key => $role)
			{
				if ($this->is_granted($role, $resource, $privilege, $assertion) === TRUE)
				{
					return TRUE;
				}
			}
		}

		return FALSE;
	}

	/**
	 * Is granted
	 *
	 * Check if a single role is allowed to access the resource.privilege.
	 *
	 * @param string $role_name Role name
	 * @param string $resource  Resource
	 * @param string $privilege Privilege
	 * @param object $assertion Assertion
	 *
	 * @return boolean
	 */
	public function is_granted($role_name, $resource, $privilege, $assertion = NULL)
	{
		if (empty($role_name) === TRUE OR empty($resource) === TRUE OR empty($privilege) === TRUE)
		{
			if ($this->log_errors)
			{
				log_message('error', 'zend2_rbac->is_granted() called with empty variable(s) role name:' . $role_name . ', resource: ' . $resource . ', privilege: ' . $privilege);
			}

			return FALSE;
		}

		if ($this->rbac->hasRole($role_name) === FALSE)
		{
			if ($this->log_errors)
			{
				log_message('error', 'zend2_rbac->is_granted() called with a non existant role name:' . $role_name);
			}

			return FALSE;
		}

		if ($assertion !== NULL)
		{
			return $this->rbac->isGranted($this->rbac->getRole($role_name), $resource . '.' . $privilege, $assertion) ? TRUE : FALSE;
		}
		else
		{
			return $this->rbac->isGranted($this->rbac->getRole($role_name), $resource . '.' . $privilege) ? TRUE : FALSE;
		}
	}

	/**
	 * Get roles for permissions
	 *
	 * @return object Query result
	 */
	public function get_roles_for_permissions()
	{
		// Super users get everything so exclude it here.
		$this->ci->db->where('name <>', $this->super_users_group);

		$this->ci->db->where('is_group', TRUE);

		$this->ci->db->order_by('parent_id', 'ASC');

		$query = $this->ci->db->get($this->role_table);

		return $query;
	}

	/**
	 * Get resources for permissions
	 *
	 * @return object Query result
	 */
	public function get_resources_for_permissions()
	{
		$query = $this->ci->db->get($this->resource_table);

		return $query;
	}

	/**
	 * Get permissions array
	 *
	 * @return array
	 */
	public function get_permissions_array()
	{
		$roles_query = $this->get_roles_for_permissions();

		$resources_query = $this->get_resources_for_permissions();

		$permissions_array = [];

		$i = 0;
		foreach ($roles_query->result() as $role)
		{
			foreach ($resources_query->result() as $resource)
			{
				$permissions_array[$i] = [
					'role'     => $role,
					'resource' => $resource,
				];

				$privileges = [];
				foreach ($this->privileges as $privilege_key => $privilege_desc)
				{
					$privileges[$privilege_key] = $this->is_granted($role->name, $resource->name, $privilege_key);
				}
				$permissions_array[$i] = array_merge($permissions_array[$i], $privileges);
				$i++;
			}
		}

		return $permissions_array;
	}

	/**
	 * Get debug array
	 *
	 * @return array
	 */
	public function get_debug_array()
	{
		return $this->debug === TRUE ? $this->debug_array : [];
	}

}
