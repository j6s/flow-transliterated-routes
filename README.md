# Flow Transliterated Routes

Small package that ties `behat/transliterator` into the default behaviour of `IdentityRoutePart` of the Flow Framework to create smart international slugs.

## Example `Routes.yaml`

```yaml
- name: 'My.News:DetailPage'
  uriPattern: '{article}'
  routeParts:
    article:
      handler: 'J6s\TransliteratedRoutes\IdentityRoutePart'
      options:
        objectType: 'My\News\Domain\Model\Article'
        uriPattern: '{name}-{urlIdentifier}'
        replacements:
          '&': 'and'
          '|': 'or'
```

## Configuration 

All configuration from the default `IdentityRoutePart` has been retained with the notable difference, that they are shifted into the `options` key.

Additionally the following options exist:

* `additionalReplacements`: Map of replacement that uses the character that should be replaced and it's replacement as a value. In order to be backwards-compatible with Flows default `IdentityRoutePart` this defaults to replacements for german special characters. If you add replacements of your own and whish to retain german special characters, you have to add them to the map manually.
