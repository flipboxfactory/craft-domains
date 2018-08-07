Changelog
=========
## 1.1.1 - 2018-08-06
### Fixed
- When saving records, duplicate unique validation was occurring.

## 1.1.0 - 2018-07-17
### Fixed
- Domains were not saving to an element upon new element creation

### Changed
- `DomainsQuery::fieldId` is now `DomainsQuery::field` and now accepts a field object
- `DomainsQuery::elementId` is now `DomainsQuery::element` and now accepts an element object

## 1.0.0 - 2018-04-25
### Changed
- Updated dependencies
- Replacing field 'limit' with 'min' and 'max'

### Fixed
- Validators were throwing an exception due to how attributes names were getting passed.

## 1.0.0-rc.2 - 2018-03-27
### Changed
- Dependencies now rely on `Sortable Associations` package.
- Lots of classes
- Table structure (no migration, please uninstall/reinstall)

## 1.0.0-rc.1 - 2018-03-01
### Changed
- More refactoring and optimizations

## 1.0.0-rc - 2018-02-28
### Changed
- Refactored various classes.  No migrations needed, but external references be advised as
no deprecations were issued.

## 1.0.0-beta - 2017-11-06
Initial release.
