# Twig Components

A Symfony 4 bundle for the [Twig Components](https://github.com/redantnl/twig-components) library.

This bundle allows you to create robust, reusable and automatically documented
Twig components using the new `component` tag. Twig components allow you to quickly
build and maintain your own UI toolkit, where each button, table or card has to be
designed only once and can be used throughout your entire application.

For more information about Twig Components, see [the documentation](https://github.com/redantnl/twig-components/blob/master/README.md).

## Setup and Usage

You can install this bundle through Composer:

```console
$ composer require redant/twig-components-bundle
```

When this bundle is enabled in your Symfony bundle configuration, if will search
all your template directories for defined components, every time the container is
compiled.

This includes:
- The `templates/components` folder (main application templates)
- Every installed bundle's `Resources/views/components` folder (if defined)

### Global variable

If you don't like the name of the global variable that defines the components,
use the `twig_components.global_variable` parameter to change this:

```yaml
# app/config/twig.yaml

twig_components:
    global_variable: 'ui'
```

This will register the button component as `ui.button()`.

**Note**: If you set the prefix to `false`, no Twig global will be registered for
defined components. You can then only use the `render_component` function.

### Namespaces

If you define your components in subdirectories of the `components/` directory, the
additional directories will namespace your component.

For example, a component defined in `components/ui/button.html.twig`
will be accessible as `component.ui.button({})` or via
`render_component('ui.button', {})`.

## Generate documentation

You can generate a static HTML file with documentation using the supplied
`twig:components:generate-docs` command. 

```console
Description:
  Generate documentation for Twig components

Usage:
  twig:component:generate-docs [options] <path>

Arguments:
  path                  Output directory

Options:
  --title=TITLE         Title for the generated documentation [default: "Twig components"]
  --generic             Disregard twig_component.global_variable settings and only show render_component() examples```
```

## License

This library is licensed under the MIT License - see the LICENSE file for details.