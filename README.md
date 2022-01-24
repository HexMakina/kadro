[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/HexMakina/kadro/badges/quality-score.png?b=main)](https://scrutinizer-ci.com/g/HexMakina/kadro/?branch=main)
<img src="https://img.shields.io/badge/PHP-7.3-brightgreen" alt="PHP 7.3 Required" />
[![License](http://poser.pugx.org/hexmakina/kadro/license)](https://packagist.org/packages/hexmakina/kadro)
[![Latest Stable Version](http://poser.pugx.org/hexmakina/kadro/v)](https://packagist.org/packages/hexmakina/kadro)
# kadro
PHP MVC framework for building web applications

## 1. Auth
### Operators
### Permissions
### ACL

## 2. Model
### Router
### PSR-3 Logger
### PSR-4 Autoloader
### PSR-11 Container

## 3. Views

## 4. Controllers

### Base

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

## Installation script
