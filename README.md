# CodeIgniter3-Zend2-Rbac-library

## Basic usage

In a controller / MY_Contoller
```
// Load the library
$this->load->library('zend2_rbac');

// Init from database
$this->zend2_rbac->init();
```

In a controller method etc.
```
// Users roles (get from db)
$roles = [
	'administrators',
	'members'
];
// Check permission
$allowed = $this->zend2_rbac->any_granted($roles, 'api/v1/countries', 'read');

if ($allowed === false)
{
	// Deny access
}
```

## Manual example

```
// Load the library
$this->load->library('zend2_rbac');

// Init manually

$super_users = new Zend\Permissions\Rbac\Role('super_users');
$administrators  = new Zend\Permissions\Rbac\Role('administrators');
$members = new Zend\Permissions\Rbac\Role('members');
$guests = new Zend\Permissions\Rbac\Role('guests');

$super_users->addChild($administrators);
$administrators->addChild($members);
$members->addChild($guests);

$this->rbac->addRole($super_users);

$this->rbac->getRole('administrators')->addPermission('api/v1/countries.create');
$this->rbac->getRole('administrators')->addPermission('api/v1/countries.read');
$this->rbac->getRole('administrators')->addPermission('api/v1/countries.update');
$this->rbac->getRole('administrators')->addPermission('api/v1/countries.delete');

$this->rbac->getRole('guests')->addPermission('api/v1/countries.read');

```