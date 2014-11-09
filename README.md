## Warden

> **Note:** This project is currently unstable and under heavy development.

Warden is a role-based access control ([RBAC](http://en.wikipedia.org/wiki/Role-based_access_control)) system for [Laravel 5](http://laravel.com) and it's [Eloquent ORM](http://laravel.com/docs/eloquent).

## @todo

There is still a lot to do, below is a table summarizing what is currently planned and what has already been done.

| Status | Action/Method      | Description                                                        |
|:------:|:------------------ |:------------------------------------------------------------------ |
| ✕      |                    | **Write tests!!**                                                  |
| ✔      | `hasRole()`        | Check that the user belongs to the specified role.                 |
| ✔      | `is{Role}()`       | Magic method helper. eg. `isOwner()`.                              |
| ✔      | `addRole()`        | Add the user to the specified role.                                |
| ✕      | `removeRole()`     | Remove the user from the specified role.                           |
| ✔      | `may()`            | Check if the user IS allowed to perform the specified action.      |
| ✔      | `may{Action}()`    | Magic method helper. eg. `mayAccessSite()`                         |
| ✔      | `mayNot()`         | Check if the user IS NOT allowed to perform the specified action.  |
| ✔      | `mayNot{Action}()` | Magic method helper. eg. `mayNotAccessSite()`.                     |
| ✕      | `permit()`         | Add a user-level permission override.                              |
| ✕      | `restrict()`       | Remove a user-level permission override.                           |
| ✕      |                    | Allow facade methods to specify a user.                            |
| ✕      |                    | Update migrations with indexes and foreign keys.                   |

## License

Warden is an open-source package shared under the [MIT license](http://opensource.org/licenses/MIT).
