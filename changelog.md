# Changelog

## 3.0.0 - 2023-02-07

### Changed

* **Minimum PHP version is now 8.1**
* Test workflow updated to run on PHP 8.1 and 8.2 on newer actions
* Testbench and Collision updated to latest versions
* Minor code cleanup

## 2.0.2 - 2021-06-26

### Added

* Tests
* GitHub test workflow

## 2.0.1 - 2021-02-04

### Changed

* Removed `callable` type-hints

## 2.0.0 - 2021-02-03

### Added

* TapProcessor
* Inverse conditions on interruptible processor

### Changed

* Code re-organized
* Updated readme with missing documentation and new documentation

## 1.0.0 - 2018-06-05

### Added

* Add strict typing.

### Changed

* Now requires PHP 7.1 or newer.
* Pipeline requires processor as first argument.
* Processor requires payload as first argument.

## 0.3.0 - 2016-10-13

* A pipeline now has a processor which is responsible for the stage invoking.

## 0.2.2 - 2016-03-23

### Fixed

* #17 - use `call_user_func` instead of invoking a variable.

## 0.2.1 - 2015-12-06

### Altered

* Cloning is used to create the new pipeline [performance]
* Stages are callable, so no need to wrap them in closures.

## 0.2.0 - 2015-12-04

### Changed

* Stages are now anything that satisfies the `callable` type-hint.

## 0.1.0 - 2015-06-25

Initial release
