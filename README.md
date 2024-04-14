[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/HexMakina/kadro/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/HexMakina/kadro/?branch=main)
<img src="https://img.shields.io/badge/PHP-7.4-brightgreen" alt="PHP 7.4 Required" />
<img src="https://img.shields.io/badge/PSR-3-brightgreen" alt="PSR-3 Compliant" />
<img src="https://img.shields.io/badge/PSR-4-brightgreen" alt="PSR-4 Compliant" />
<img src="https://img.shields.io/badge/PSR-11-brightgreen" alt="PSR-11 Compliant" />
<img src="https://img.shields.io/badge/PSR-12-brightgreen" alt="PSR-12 Compliant" />
[![License](http://poser.pugx.org/hexmakina/kadro/license)](https://packagist.org/packages/hexmakina/kadro)
[![Latest Stable Version](http://poser.pugx.org/hexmakina/kadro/v)](https://packagist.org/packages/hexmakina/kadro)
# kadro
PHP MVC framework for building web applications

# install 
`composer require hexmakina/kadro`

then run `vendor/hexmakina/kadro/install.php` to initialise the application with base tables and data

for instance: 

`php vendor/hexmakina/kadro/install.php -db DATABASE_NAME -u DATABASE_USER -p DATABASE_PASSWORD`

or, if the password contains a blank space

`php vendor/hexmakina/kadro/install.php -db DATABASE_NAME -u DATABASE_USER -p "DATABASE_PASSWORD"`

it creates a default root user "root" with password "root"

## 1. Auth
### Operator
### Permission
### ACL

## 2. Models
### PSR-3 Logger
### PSR-4 Autoloader

## 3. Views

## 4. Controllers
### Base
### Displays
### Kadro
### ORM
### Reception

### Router
### Chainlings & Traitor
### Container (PSR-11 )
### Errors


Base
- Routing
- Container & Invoker
- Logger & Errors


- Hopper
- LeMarchand
- Traitor
- LogLaddy

handles errors, routing, logging & container

#### Execution & Return
to properly execute a controller method, f.i. `public function doSomething()`, a call is to be made: `$controller->execute('doSomething')`, allowing for hooks to be executed as well.

The return value of `doSomething()` will be returned, if no errors occured during the Hooks and Traitor part.

#### Hooks
when running `$controller->execute('doSomething')` , the following hooks will be called:
```
$this->prepare()
$this->before_doSomething()
$this->doSomething()
$this->after_doSomething()
$this->conclude()
```

there is no need to implement any of the calls as Base will check for their existence first
regarding prepare() and conclude(), Base has a default implementation returning true  

#### Traitor
Base uses the [Traitor](https://github.com/HexMakina/Traitor) trait, and calls the controller's trait-compatible methods before the controller's own methods.

Given a controller using Trait1 and Trait2, the method call list will be the following:
```
$this->Trait1_Traitor_prepare()
$this->Trait2_Traitor_prepare()
$this->prepare()

$this->Trait1_Traitor_before_doSomething()
$this->Trait2_Traitor_before_doSomething()
$this->before_doSomething()

$this->Trait1_Traitor_doSomething()
$this->Trait2_Traitor_doSomething()
$this->doSomething()

$this->Trait1_Traitor_after_doSomething()
$this->Trait2_Traitor_after_doSomething()
$this->after_doSomething()

$this->Trait1_Traitor_conclude()
$this->Trait2_Traitor_conclude()
$this->conclude()
```

## 5. Routing

kadro requires a 'home' route:
- GET ''

kadro reserves the following routes
```
return [
  // --- auth
  ['GET', 'checkin', 'Reception::checkin', 'checkin'],
  ['GET', 'checkout', 'Reception::checkout', 'checkout'],
  ['POST', 'identify', 'Reception::identify', 'identify'],
  ['GET', 'operator/[*:username]/toggle/active', 'Operator::change_active', 'operator_change_active'],
  ['GET', 'operator/[*:username]/change-acl/[i:permission_id]', 'Operator::change_acl', 'acl_toggle'],

  // --- TRADUKO
  ['POST', 'traduko/update_file', 'Traduko::update_file', 'traduko_update_file'],

  // --- LOCALE JSON
  ['GET', 'locale/language_codes.[a:format]', 'Export::otto_languages', 'otto_languages'],

  // --- EXPORT
  ['GET', 'export', 'Export::dashboard', 'export'], // default ExportController is dashboard
  ['GET', 'otto/language_codes.[a:format]/term/[a:search]?', 'Export::otto_languages', 'otto_languages_search'],
  ['GET', 'otto/[a:model]/distinct/[*:field].[a:format]', 'Export::otto_distinct_field', 'otto_distinct_field'],
  ['GET', 'otto/[a:model]/distinct/[*:field].[a:format]/term/[*:search]?', 'Export::otto_distinct_field', 'otto_distinct_field_where'],
  ['GET', 'export/[*:action].[a:format]', 'Export::dynamic_action_call', 'export_action_call'],
];
```
