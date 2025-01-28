# Listable

This Neos package solves one problem: help you list any nodes in Fusion.
The idea is very simple: you often need to display list of things (e.g. news, articles etc), and the concern of listing items should better be separated from the concern of rendering items. This package provides a solid foundation for listing, while allowing you to take care of rendering stuff on your own.

# TL;DR

1. Install the package with composer: `composer require flowpack/listable` [Here it is on packagist](https://packagist.org/packages/flowpack/listable).
2. If you want a paginated list, use `Flowpack.Listable:PaginatedCollection`.
3. If you just want a simple list, use `Flowpack.Listable:Collection` (or just `Neos.Fusion:Collection`!).
4. If you need a list with a header and an archive link, wrap you list into `Flowpack.Listable:List`
5. For each of your nodetypes create a new Fusion object of type NodeTypeName + '.Short', or manually define a rendering object.
6. Rely on public API keys when overriding settings.

# Fusion objects

Keys documented here are considered public API and would be treated with semantic versioning in mind. Extend all other properties at your own risk.

## Flowpack.Listable:Collection

This object is just a simple convienince wrapper around `Neos.Fusion:Collection`, use it if you want to save a few keystrokes.
It wraps the list with UL and LI tags with a provided name and also set `Flowpack.Listable:ContentCaseShort` as a default for itemRenderer.

Configuration options:

| Setting | Description | Defaults |
|---------|-------------|----------|
| collection | An instance of `ElasticSearchQueryBuilder`, `FlowQuery` object or an `array` of nodes | 'to-be-set' |
| listClass | Classname of UL tag | '' |
| itemClass | Classname of LI tag wrapping each item | '' |
| itemRenderer | Object used for rendering child items. Within it you get two context vars set: `node` and `iterator` | 'Flowpack.Listable:ContentCaseShort' |
| itemName | Name of the the node context variable | 'node' |
| iterationName | Name of the the iterator context variable | 'iteration' |

Example:

```
prototype(My.Custom:Object) < prototype(Flowpack.Listable:Collection) {
  collection = ${q(site).find('[instanceof Something.Custom:Here]').sort('date', 'DESC').slice(0, 6).get()}
  listClass = 'MyList'
  itemClass = 'MyList-item'
}
```

It would use the object `Something.Custom:Here.Short` for rendering each item.

Make sure to correctly configure the cache.

## Flowpack.Listable:PaginatedCollection

This object allows you to paginate either **ElasticSearch** results, FlowQuery result objects or pure Node arrays.

Configuration options:

| Setting | Description | Defaults |
|---------|-------------|----------|
| collection | An instance of `ElasticSearchQueryBuilder`, `FlowQuery` object or an `array` of nodes | 'to-be-set' |
| itemsPerPage | Number of items per page when using pagination | 24 |
| maximumNumberOfLinks | Number of page links in pagination | 15 |
| listRenderer | Object used for rendering the list. | 'Flowpack.Listable:Collection' |
| showPreviousNextLinks| Boolean value used to decide whether the previous and next links should be added| false |

When used with ElasticSearch, build the query, but don't execute it, the object will do it for you:

```
prototype(My.Custom:Object) < prototype(Flowpack.Listable:PaginatedCollection) {
  collection = ${Search.query(site).nodeType('Something.Custom:Here').sortDesc('date')}
  itemsPerPage = 12
  prototype(Flowpack.Listable:Collection) {
    listClass = 'MyPaginatedList'
    itemClass = 'MyPaginatedList-item'
  }
}
```

If you have additional URL parameters (e.g for a date filter) you have to register the argument and change the cache entryDiscriminator in order work accordingly.
HINT: Do not forget to register a corresponding route for your custom argument.

```
prototype(My.Custom:Object) < prototype(Flowpack.Listable:PaginatedCollection) {
  ...

  prototype(Flowpack.Listable:PaginationParameters) {
    date = ${request.arguments.data}
  }

  @cache {
    entryDiscriminator = ${request.arguments.currentPage + request.arguments.date}
  }

}
```

This object is configured by default to `dynamic` cache mode for pagination to work. All you have to do is add correct `entryTags` and you are all set.

## Flowpack.Listable:List

There's often a need to render a list with a header and an archive link.
This object takes your list and wraps it with just that.

Configuration options:

| Setting | Description | Defaults |
|---------|-------------|----------|
| wrapClass | Class of the div that wraps the whole object | '' |
| listTitle | Title of the list | '' |
| listTitleClass | Class of the list title | '' |
| archiveLink | Nodepath for the archive link | '' |
| archiveLinkTitle | Title of the archive link | '' |
| archiveLinkClass | Classname of the archive link | '' |
| archiveLinkAdditionalParams | AdditionalParams of the archive link, e.g. `@context.archiveLinkAdditionalParams = ${{archive: 1}}` | {} |
| list | A list that this object should wrap | `value` |

Example:

```
prototype(My.Custom:Object) < prototype(Flowpack.Listable:PaginatedCollection) {
  @process.list = Flowpack.Listable:List {
    listTitle = 'My List'
    archiveLink = '~/page-path-or-identifier'
    archiveLinkTitle = 'See all news'
  }
  collection = ${q(site).find('[instanceof Something.Custom:Here]').sort('date', 'DESC').slice(0, 6).get()}
}
```

## Flowpack.Listable:Pagination

You can also use pagination standalone from the `PaginatedCollection`.

Configuration options:

| Setting | Description | Defaults |
|---------|-------------|----------|
| totalCount | A total count of items | 'to-be-set' |
| itemsPerPage | Number of items per page | 24 |
| maximumNumberOfLinks | A maximum number of links | 15 |
| class | A class around pagination | 'Pagination' |
| itemClass | A total count of items | 'Pagination-item' |
| currentItemClass | A class for a current item | 'isCurrent' |
| currentPage | Current page, starting with 1 | `${request.arguments.currentPage || 1}` |
| showPreviousNextLinks| Boolean value used to decide whether the previous and next links should be added| false |

# FlowQuery Helpers you can use

## filterByDate

Filter nodes by properties of type date.

## filterByReference

Filter nodes by properties of type reference or references.

## sortRecursiveByIndex

Sort nodes by their position in the node tree. Please use with care, as this can become a very expensive operation, if you operate on bigger subtrees.

Example:

    ${q(node).children("main").sortRecursiveByIndex('DESC').get()}
